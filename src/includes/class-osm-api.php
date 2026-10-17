<?php

/**
 * Signal that OSM created a child but could not confirm a later details update.
 *
 * Callers must not retry member creation after this exception. The public form
 * asks the applicant to contact the group, without exposing OSM identifiers.
 */
class OSM_Waiting_List_Partial_Exception extends RuntimeException {}

/** Distinguish a missing current section term from transport/authentication failures. */
class OSM_Current_Term_Exception extends RuntimeException {}

class OSM_API {
    private static $access_token = null;
    private static $token_scope = null;

    /**
     * Return the four scopes requested by the shared Client Credentials connection.
     * @return string Space-separated programme, event and member read scopes plus member write.
     */
    public static function get_scopes() {
        return 'section:programme:read section:event:read section:member:read section:member:write';
    }

    /**
     * Read the last complete quota reported by OSM without making a request.
     * @return array{limit:int,used:int,remaining:int,reset_at:int,reported_at:int}|null Last snapshot, or null before OSM supplies valid headers.
     */
    public static function get_api_usage(): ?array {
        $usage = get_option( 'osm_api_usage', null );
        if ( ! is_array( $usage ) ) { return null; }
        foreach ( [ 'limit', 'remaining', 'reset_at', 'reported_at' ] as $field ) {
            if ( ! isset( $usage[ $field ] ) || ! is_int( $usage[ $field ] ) || $usage[ $field ] < 0 ) { return null; }
        }
        if ( $usage['limit'] < 1 || $usage['remaining'] > $usage['limit'] ) { return null; }
        return [ 'limit' => $usage['limit'], 'used' => $usage['limit'] - $usage['remaining'], 'remaining' => $usage['remaining'], 'reset_at' => $usage['reset_at'], 'reported_at' => $usage['reported_at'] ];
    }

    /**
     * Capture complete rate-limit headers, including responses with HTTP errors.
     *
     * OSM reports reset as seconds until renewal. Missing or malformed headers
     * leave the last valid observation intact. No request bodies or credentials are stored.
     * @param array<string,mixed> $response WordPress HTTP response after transport validation.
     * @return void Stores only numeric quota metadata, independently of the feed cache.
     */
    private static function record_api_usage( array $response ): void {
        $values = [];
        foreach ( [ 'limit', 'remaining', 'reset' ] as $field ) {
            $raw = wp_remote_retrieve_header( $response, 'x-ratelimit-' . $field );
            if ( ! is_scalar( $raw ) || false === filter_var( $raw, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0, 'max_range' => PHP_INT_MAX - time() ] ] ) ) { return; }
            $values[ $field ] = (int) $raw;
        }
        if ( $values['limit'] < 1 || $values['remaining'] > $values['limit'] ) { return; }
        update_option( 'osm_api_usage', [ 'limit' => $values['limit'], 'remaining' => $values['remaining'], 'reset_at' => time() + $values['reset'], 'reported_at' => time() ], false );
    }

    /**
     * Forget cached tokens for the shared application connection.
     * @return void Removes in-memory and scope-specific persisted token caches.
     */
    public static function clear_authorization() {
        self::$access_token = null;
        self::$token_scope = null;
        OSM_Cache::delete( 'access_token' );
        OSM_Cache::delete( 'access_token_scope' );
        OSM_Cache::delete( 'access_token_' . md5( self::get_scopes() ) );
        $patrol_scope = get_option( 'osm_patrol_scope', '' );
        if ( is_string( $patrol_scope ) && '' !== $patrol_scope ) {
            OSM_Cache::delete( 'access_token_' . md5( self::get_scopes() . ' ' . $patrol_scope ) );
        }
    }

    /**
     * Obtain a cached or fresh Client Credentials token for all plugin operations.
     * @param bool $force Whether to invalidate cached tokens before requesting one.
     * @param string $additional_scope Legacy scope argument; must already belong to the four default scopes.
     * @return string Server-only bearer token, cached for less than its provider lifetime.
     * @throws Exception If credentials, scope, transport or token validation fails.
     */
    public static function authorize($force = false, $additional_scope = '') {
        $scope = self::get_scopes();
        if ( '' !== $additional_scope ) {
            if ( ! is_string( $additional_scope ) || ! in_array( $additional_scope, explode( ' ', $scope ), true ) ) {
                throw new Exception( 'Unsupported OSM scope.' );
            }
        }
        $token_cache_key = 'access_token_' . md5( $scope );
        if ( $force ) {
            self::clear_authorization();
            OSM_Cache::delete( $token_cache_key );
        }
        if ( self::$access_token && self::$token_scope === $scope ) {
            return self::$access_token;
        }

        $cached_token = OSM_Cache::get( $token_cache_key );
        if ( $cached_token ) {
            self::$access_token = $cached_token;
            self::$token_scope = $scope;
            return $cached_token;
        }

        $client_id = get_option( 'osm_client_id' );
        $client_secret = get_option( 'osm_client_secret' );

        if ( ! $client_id || ! $client_secret ) {
            throw new Exception( 'Client ID or Secret not set in Settings → Online Scout Manager.' );
        }

        $response = self::make_request(
            'https://www.onlinescoutmanager.co.uk/oauth/token',
            'POST',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'scope'         => $scope,
            ],
            false
        );

        if ( ! empty( $response['access_token'] ) && is_string( $response['access_token'] ) ) {
            $access_token = $response['access_token'];
            $expires_in = isset( $response['expires_in'] ) ? (int) $response['expires_in'] : 3600;
            if ( $expires_in <= 0 ) {
                throw new Exception( 'OSM returned an invalid access token lifetime.' );
            }

            $ttl = max( 1, $expires_in - min( 60, (int) floor( $expires_in / 2 ) ) );
            OSM_Cache::set( $token_cache_key, $access_token, $ttl );
            self::$access_token = $access_token;
            self::$token_scope = $scope;

            return $access_token;
        }

        throw new Exception( 'Authorization failed. Please check your credentials.' );
    }

    /**
     * Fetch accessible section labels, excluding campsites and unrelated OSM fields.
     * @return array<int,array{groupname:string,sectionname:string,section:string}> Section metadata keyed by ID.
     * @throws Exception If authorisation, transport or OSM response validation fails.
     */
    public static function get_sections() {
        $cache_key = 'osm_sections';
        $cached_sections = OSM_Cache::get( $cache_key );

        // Return cached sections if available
        if ( is_array( $cached_sections ) ) {
            return $cached_sections;
        }

        // Retrieve sections from the API
        $token = self::authorize();
        $sections = self::make_request(
            'https://www.onlinescoutmanager.co.uk/api.php?action=getUserRoles',
            'GET',
            [],
            $token
        );

        // Convert to associative array and reduce to only necessary fields
        $sections = array_reduce($sections, function ($carry, $section) {
            if ( ! is_array( $section ) || empty( $section['sectionid'] ) || ( ! is_string( $section['sectionid'] ) && ! is_int( $section['sectionid'] ) ) || ! preg_match( '/^[1-9][0-9]*$/', (string) $section['sectionid'] ) || false === filter_var( $section['sectionid'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) {
                return $carry;
            }
            $carry[ (int) $section['sectionid'] ] = [
                'groupname' => is_string( $section['groupname'] ?? null ) ? sanitize_text_field( $section['groupname'] ) : '',
                'sectionname' => is_string( $section['sectionname'] ?? null ) ? sanitize_text_field( $section['sectionname'] ) : '',
                'section' => is_string( $section['section'] ?? null ) ? sanitize_text_field( $section['section'] ) : '',
            ];
            return $carry;
        }, []);

        // Filter out campsite sections
        $sections = array_filter($sections, function ($section) {
            return $section['section'] !== 'campsite';
        });

        // Cache the filtered sections
        if ( $sections ) {
            OSM_Cache::set( $cache_key, $sections, 86400 ); // Cache for 24 hours
        }

        // Return the filtered sections
        return $sections;
    }

    /**
     * Find the current term in the WordPress timezone, caching no later than midnight.
     * @param int|string $sectionid Positive section ID, retained for public API compatibility.
     * @return int Positive OSM term ID.
     * @throws OSM_Current_Term_Exception If OSM returned no current term for this section.
     * @throws Exception If the ID is invalid or OSM is unavailable.
     */
    public static function get_current_term( $sectionid ) {
        $sectionid = self::validate_id( $sectionid );
        $cache_key = "current_term_{$sectionid}";
        $cached_termid = OSM_Cache::get( $cache_key );

        if ( $cached_termid ) {
            try {
                return self::validate_id( $cached_termid );
            } catch ( Exception $error ) {
                OSM_Cache::delete( $cache_key );
            }
        }

        $token = self::authorize();
        $terms = self::make_request(
            'https://www.onlinescoutmanager.co.uk/api.php?action=getTerms',
            'GET',
            [],
            $token
        );

        if ( isset( $terms[ $sectionid ] ) && is_array( $terms[ $sectionid ] ) ) {
            $today = current_time( 'Y-m-d' );
            foreach ( $terms[ $sectionid ] as $term ) {
                if ( ! is_array( $term ) || ! isset( $term['startdate'], $term['enddate'], $term['termid'] ) || ! self::valid_iso_date( $term['startdate'] ) || ! self::valid_iso_date( $term['enddate'] ) ) {
                    continue;
                }
                if ( $term['startdate'] <= $today && $term['enddate'] >= $today ) {
                    try {
                        $termid = self::validate_id( $term['termid'] );
                    } catch ( Exception $error ) {
                        continue;
                    }
                    $now = new DateTimeImmutable( 'now', wp_timezone() );
                    $midnight = $now->modify( 'tomorrow' )->setTime( 0, 0 );
                    OSM_Cache::set( $cache_key, $termid, max( 1, $midnight->getTimestamp() - $now->getTimestamp() ) );
                    return $termid;
                }
            }
        }

        throw new OSM_Current_Term_Exception( 'No current term is available for this section.' );
    }

    /**
     * Return the public programme field allowlist for an enabled caller-selected term.
     * @param int|string $sectionid Positive section ID; publication checks belong to the renderer.
     * @param int|string $termid Positive current term ID.
     * @return array{items:list<array<string,string>>} Cached or fetched public programme rows.
     * @throws Exception If identifiers, authorisation or response validation fails.
     */
    public static function get_programme( $sectionid, $termid ) {
        $sectionid = self::validate_id( $sectionid );
        $termid = self::validate_id( $termid );
        $cache_key = "programme_{$sectionid}_{$termid}";
        $cached_programme = OSM_Cache::get( $cache_key );
        $fields = [ 'meetingdate', 'title', 'notesforparents' ];

        if ( is_array( $cached_programme ) && isset( $cached_programme['items'] ) && is_array( $cached_programme['items'] ) ) {
            $programme = self::public_items( $cached_programme, $fields );
            if ( $programme !== $cached_programme ) {
                OSM_Cache::set( $cache_key, $programme, 86400 );
            }
            return $programme;
        }

        $token = self::authorize();
        $programme = self::make_request(
            "https://www.onlinescoutmanager.co.uk/programme.php?action=getProgramme&sectionid={$sectionid}&termid={$termid}",
            'GET',
            [],
            $token
        );
        $programme = self::public_items( $programme, $fields );

        if ( $programme ) {
            OSM_Cache::set( $cache_key, $programme, 86400 ); // Cache for 24 hours
        }

        return $programme;
    }

    /**
     * Return only event fields needed by the public event table.
     * @param int|string $sectionid Positive section ID; callers must enforce publication settings.
     * @param int|string $termid Positive term ID.
     * @return array{items:list<array<string,string>>} Public event rows without extra API fields.
     * @throws Exception If identifiers, authorisation or response validation fails.
     */
    public static function get_events( $sectionid, $termid ) {
        $sectionid = self::validate_id( $sectionid );
        $termid = self::validate_id( $termid );
        $cache_key = "events_{$sectionid}_{$termid}";
        $cached_events = OSM_Cache::get( $cache_key );
        $fields = [ 'startdate_g', 'startdate', 'eventdate', 'enddate_g', 'enddate', 'starttime', 'endtime', 'name', 'location' ];

        if ( is_array( $cached_events ) && isset( $cached_events['items'] ) && is_array( $cached_events['items'] ) ) {
            $events = self::public_items( $cached_events, $fields );
            if ( $events !== $cached_events ) {
                OSM_Cache::set( $cache_key, $events, 86400 );
            }
            return $events;
        }

        $token = self::authorize();
        $events = self::make_request(
            "https://www.onlinescoutmanager.co.uk/ext/events/summary/?action=get&sectionid={$sectionid}&termid={$termid}",
            'GET',
            [],
            $token
        );
        $events = self::public_items( $events, $fields );

        if ( $events ) {
            OSM_Cache::set( $cache_key, $events, 86400 ); // Cache for 24 hours
        }

        return $events;
    }

    /**
     * Accept a finite decimal patrol score without inventing a missing total.
     * @param mixed $value Untrusted OSM points field.
     * @return string|null Display-safe decimal score, or null when absent/malformed.
     */
    private static function public_points( mixed $value ): ?string {
        if ( ! is_int( $value ) && ! is_float( $value ) && ! is_string( $value ) ) { return null; }
        $value = (string) $value;
        return strlen( $value ) <= 32 && preg_match( '/^-?[0-9]+(?:\.[0-9]+)?$/D', $value ) ? $value : null;
    }

    /**
     * Normalize an OSM member identifier for exclusion matching.
     * @param array<string,mixed> $member Untrusted OSM member record.
     * @return int Positive member ID, or zero when missing or malformed.
     */
    private static function member_id( array $member ): int {
        $id = $member['scoutid'] ?? $member['memberid'] ?? $member['id'] ?? null;
        return ( is_int( $id ) || is_string( $id ) ) && false !== filter_var( $id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ? (int) $id : 0;
    }

    /**
     * List minimal member identities for an administrator's exclusion controls.
     *
     * Publication need not be enabled. Access is checked before any remote request.
     * The roster is request-local; no member names or raw responses are cached.
     * @param int $section_id Accessible section ID to inspect.
     * @return list<array{id:int,name:string,firstname:string,lastname:string,patrol:string,points:?string}> Minimal active youth identities and patrol labels for administrator controls and preview, ordered by name.
     * @throws RuntimeException If the caller lacks administrator capability or section access.
     * @throws Exception If term lookup, authentication or the OSM request fails.
     */
    public static function get_section_members_for_admin( int $section_id ): array {
        if ( ! current_user_can( 'manage_options' ) ) { throw new RuntimeException( 'Administrator access is required.' ); }
        if ( ! isset( self::get_sections()[ $section_id ] ) ) { throw new RuntimeException( 'The selected section is unavailable.' ); }
        $term_id = self::get_current_term( $section_id );
        $response = self::make_request(
            'https://www.onlinescoutmanager.co.uk/ext/members/patrols/?action=getPatrolsWithPeople&sectionid=' . $section_id . '&termid=' . $term_id . '&include_no_patrol=y',
            'GET', [], self::authorize()
        );
        $members = [];
        foreach ( $response as $key => $patrol ) {
            if ( ! is_array( $patrol ) || (int) ( $patrol['patrolid'] ?? $key ) < 0 ) { continue; }
            foreach ( is_array( $patrol['members'] ?? null ) ? $patrol['members'] : [] as $member ) {
                if ( ! is_array( $member ) || ( isset( $member['active'] ) && in_array( $member['active'], [ false, 0, '0' ], true ) ) || ( ! empty( $member['enddate'] ) && is_string( $member['enddate'] ) && $member['enddate'] < current_time( 'Y-m-d' ) ) ) { continue; }
                $member_id = self::member_id( $member );
                if ( $member_id < 1 ) { continue; }
                $parts = [];
                foreach ( [ 'firstname', 'lastname' ] as $field ) {
                    if ( is_string( $member[ $field ] ?? null ) ) { $parts[] = sanitize_text_field( $member[ $field ] ); }
                }
                $members[ $member_id ] = [ 'id' => $member_id, 'name' => trim( implode( ' ', $parts ) ), 'firstname' => is_string( $member['firstname'] ?? null ) ? sanitize_text_field( $member['firstname'] ) : '', 'lastname' => is_string( $member['lastname'] ?? null ) ? sanitize_text_field( $member['lastname'] ) : '', 'patrol' => is_string( $patrol['name'] ?? null ) ? sanitize_text_field( $patrol['name'] ) : '', 'points' => self::public_points( $patrol['points'] ?? null ) ];
            }
        }
        // Stable names make the administrator's safety review easier.
        usort( $members, static fn( array $first, array $second ): int => strnatcasecmp( $first['name'], $second['name'] ) );
        return $members;
    }

    /**
     * Fetch patrols and apply the administrator's name display policy immediately.
     *
     * Only approved display strings leave this method. Raw members, IDs, contact
     * fields, photos and ranks are discarded and no patrol response is cached.
     * Leaders, young leaders, inactive members, excluded IDs and pseudo-patrols are excluded.
     *
     * @param int|string $sectionid Positive section ID with Section Points publication enabled.
     * @return list<array{name:string,points:?string,members?:list<string>}> Public patrol names and optional approved member labels.
     * @throws Exception If publication, term, authorisation or response validation fails.
     */
    public static function get_patrols( $sectionid ): array {
        $section_id = self::validate_id( $sectionid );
        $public = (array) get_option( 'osm_public_patrol_sections', [] );
        if ( empty( $public[ $section_id ] ) ) {
            throw new Exception( 'Section Points has not been enabled for this section.' );
        }
        $term_id = self::get_current_term( $section_id );
        $response = self::make_request(
            'https://www.onlinescoutmanager.co.uk/ext/members/patrols/?action=getPatrolsWithPeople&sectionid=' . $section_id . '&termid=' . $term_id . '&include_no_patrol=n',
            'GET', [], self::authorize()
        );
        $excluded_members = OSM_Options::get_excluded_members( $section_id );
        $patrols = [];
        foreach ( $response as $key => $patrol ) {
            if ( ! is_array( $patrol ) || ! is_string( $patrol['name'] ?? null ) ) { continue; }
            $patrol_id = $patrol['patrolid'] ?? $key;
            if ( false === filter_var( $patrol_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] )
                || ( isset( $patrol['active'] ) && in_array( $patrol['active'], [ false, 0, '0' ], true ) ) ) { continue; }
            $name = sanitize_text_field( $patrol['name'] );
            if ( '' === $name ) { continue; }
            $item = [ 'name' => $name, 'points' => self::public_points( $patrol['points'] ?? null ) ];
            foreach ( is_array( $patrol['members'] ?? null ) ? $patrol['members'] : [] as $member ) {
                if ( ! is_array( $member ) || ( isset( $member['active'] ) && in_array( $member['active'], [ false, 0, '0' ], true ) )
                    || ( ! empty( $member['enddate'] ) && is_string( $member['enddate'] ) && $member['enddate'] < current_time( 'Y-m-d' ) ) ) { continue; }
                $member_id = self::member_id( $member );
                // Unknown IDs cannot safely bypass configured exclusions.
                if ( ( $excluded_members && 0 === $member_id ) || in_array( $member_id, $excluded_members, true ) ) { continue; }
                $label = OSM_Options::format_member_name( $member['firstname'] ?? '', $member['lastname'] ?? '', $section_id );
                if ( '' !== $label ) { $item['members'][] = $label; }
            }
            $patrols[] = $item;
        }
        return $patrols;
    }

    /**
     * Validate a positive OSM identifier without numeric coercion or integer overflow.
     * @param mixed $value Candidate integer or decimal string.
     * @return int Positive identifier.
     * @throws Exception If the value is malformed or outside the supported integer range.
     */
    private static function validate_id( $value ) {
        if ( ( ! is_string( $value ) && ! is_int( $value ) ) || ! preg_match( '/^[1-9][0-9]*$/', (string) $value ) || false === filter_var( $value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) {
            throw new Exception( 'Invalid OSM section or term ID.' );
        }
        return (int) $value;
    }

    /**
     * Check an OSM date without permitting PHP date rollover.
     * @param mixed $value Expected Y-m-d string.
     * @return bool Whether the string denotes a real calendar date.
     */
    private static function valid_iso_date( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value, $parts ) ) {
            return false;
        }
        return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
    }

    /**
     * Remove unrelated or malformed API fields before they reach public-feed caches.
     * @param array<string,mixed> $response Decoded OSM feed response.
     * @param list<string> $fields Field allowlist for the selected public table.
     * @return array{items:list<array<string,string>>} Public rows with scalar text only.
     * @throws Exception If the expected items array is absent.
     */
    private static function public_items( $response, $fields ) {
        if ( ! isset( $response['items'] ) || ! is_array( $response['items'] ) ) {
            throw new Exception( 'OSM did not return the expected public feed.' );
        }
        $items = [];
        foreach ( $response['items'] as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $public_item = [];
            foreach ( $fields as $field ) {
                if ( array_key_exists( $field, $item ) ) {
                    $public_item[ $field ] = ( is_string( $item[ $field ] ) || is_numeric( $item[ $field ] ) ) ? sanitize_text_field( (string) $item[ $field ] ) : '';
                }
            }
            if ( $public_item ) {
                $items[] = $public_item;
            }
        }
        return [ 'items' => $items ];
    }

    /**
     * Create an applicant and their primary adult contact in the configured section.
     *
     * The two OSM writes are not transactional. Callers must claim a one-use
     * submission key before calling and must never automatically retry a write.
     * Only the explicit member/contact field allowlists are transmitted; nothing is cached.
     * Primary Contact 1 uses the custom-data update endpoint with its section_id
     * query context and associated_id bound to the confirmed new member.
     *
     * @param int $section_id Administrator-configured waiting-list section.
     * @param array{firstname:string,lastname:string,dob:string,parent_firstname:string,parent_lastname:string,email:string,parent_note?:string,willing_to_help?:string} $payload Validated minimal application.
     * @return int Confirmed OSM member ID; never display it publicly.
     * @throws OSM_Waiting_List_Partial_Exception If creation succeeded but a contact/notes write failed.
     * @throws Exception If configuration, authorisation, validation or creation fails; receipt may be uncertain.
     */
    public static function create_waiting_list_member( int $section_id, array $payload ): int {
        if ( $section_id < 1 || $section_id !== (int) get_option( 'osm_waiting_list_section_id', 0 ) ) {
            throw new RuntimeException( 'The waiting-list destination is unavailable.' );
        }
        foreach ( [ 'firstname', 'lastname', 'dob', 'parent_firstname', 'parent_lastname', 'email' ] as $field ) {
            if ( ! is_string( $payload[ $field ] ?? null ) || '' === trim( $payload[ $field ] ) || strlen( $payload[ $field ] ) > 254 || sanitize_text_field( $payload[ $field ] ) !== $payload[ $field ] ) {
                throw new InvalidArgumentException( 'Application fields are incomplete.' );
            }
        }
        if ( ! self::valid_iso_date( $payload['dob'] ) || $payload['dob'] > current_time( 'Y-m-d' ) || ! is_email( $payload['email'] ) ) {
            throw new InvalidArgumentException( 'Application date or email is invalid.' );
        }
        $parent_note = $payload['parent_note'] ?? '';
        $willing_to_help = $payload['willing_to_help'] ?? '';
        if ( ! is_string( $parent_note ) || sanitize_textarea_field( $parent_note ) !== $parent_note || preg_match_all( '/./us', $parent_note ) > 2000 || ! is_string( $willing_to_help ) || ! in_array( $willing_to_help, [ '', 'yes', 'no', 'discuss' ], true ) ) {
            throw new InvalidArgumentException( 'Additional application details are invalid.' );
        }
        $token = self::authorize();
        $body = [
            'firstname' => $payload['firstname'], 'lastname' => $payload['lastname'], 'dob' => $payload['dob'],
            'started' => current_time( 'Y-m-d' ), 'startedsection' => current_time( 'Y-m-d' ),
            'sectionid' => $section_id, 'originating_section_id' => $section_id,
        ];
        $term_id = OSM_Cache::get( 'current_term_' . $section_id );
        if ( is_scalar( $term_id ) && false !== filter_var( $term_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) {
            $body['term_id'] = (int) $term_id;
        }
        $created = self::make_request( 'https://www.onlinescoutmanager.co.uk/ext/members/contact/actions/?action=newMember', 'POST', $body, $token );
        $member_id = filter_var( $created['scoutid'] ?? null, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
        if ( false === $member_id || ( isset( $created['result'] ) && 'ok' !== $created['result'] ) ) {
            throw new RuntimeException( 'OSM did not confirm application receipt. Contact the group before applying again.' );
        }
        try {
            // Contact fields belong to the custom-data API; route with section_id in the query.
            // This is a single update of Primary Contact 1, never a second member creation.
            $contact = self::make_request( 'https://www.onlinescoutmanager.co.uk/ext/customdata/?action=update&section_id=' . $section_id, 'POST', [
                'associated_type' => 'member', 'associated_id' => $member_id, 'group_id' => 1,
                'context' => 'members', 'associated_is_section' => 'false',
                'data' => [ 'firstname' => $payload['parent_firstname'], 'lastname' => $payload['parent_lastname'], 'email1' => $payload['email'] ],
            ], $token );
            $contact_confirmed = 'ok' === ( $contact['result'] ?? null )
                || true === ( $contact['status'] ?? null ) || 'ok' === ( $contact['status'] ?? null )
                || true === ( $contact['ok'] ?? null );
            if ( ! $contact_confirmed || ! empty( $contact['errors'] ) ) {
                throw new RuntimeException( 'Contact update was not confirmed.' );
            }
        } catch ( Throwable $error ) {
            // No compensating delete: OSM has already accepted the child's application.
            throw new OSM_Waiting_List_Partial_Exception( 'Your child was added, but we could not save the adult contact details. Please contact the group. Do not apply again.' );
        }
        $help_labels = [ 'yes' => 'Yes', 'no' => 'No', 'discuss' => 'Would like to find out more' ];
        $note = $parent_note;
        if ( '' !== $willing_to_help ) { $note .= ( '' !== $note ? "\n\n" : '' ) . 'Parent or carer willing to help: ' . $help_labels[ $willing_to_help ]; }
        if ( '' !== $note ) {
            try { self::save_waiting_list_notes( $section_id, $member_id, $note, $token ); }
            catch ( Throwable $error ) {
                throw new OSM_Waiting_List_Partial_Exception( 'Your child and adult contact were added, but we could not save the additional notes or helping answer. Please contact the group with those details. Do not apply again.' );
            }
        }
        return $member_id;
    }

    /**
     * Resolve this new member's Notes column and save optional application context.
     *
     * OSM custom columns vary per section. Require exactly one text Notes column;
     * never guess a group/column ID, overwrite another field or cache raw member data.
     * Existing note text is preserved. Call only after child and adult writes succeed.
     * @param int $section_id Selected destination section.
     * @param int $member_id Confirmed newly created member ID.
     * @param string $note Sanitised parent note and optional helping answer.
     * @param string $token Shared application bearer token, retained only on the server.
     * @return void
     * @throws Exception If lookup, mapping or the update cannot be confirmed.
     */
    private static function save_waiting_list_notes( int $section_id, int $member_id, string $note, string $token ): void {
        $url = 'https://www.onlinescoutmanager.co.uk/ext/customdata/?action=getData&section_id=' . $section_id . '&associated_id=' . $member_id . '&associated_type=member&context=members';
        $response = self::make_request( $url, 'GET', [], $token );
        $matches = [];
        foreach ( is_array( $response['data'] ?? null ) ? $response['data'] : [] as $group ) {
            if ( ! is_array( $group ) || false === filter_var( $group['group_id'] ?? null, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) { continue; }
            foreach ( is_array( $group['columns'] ?? null ) ? $group['columns'] : [] as $column ) {
                if ( ! is_array( $column ) ) { continue; }
                $name = is_string( $column['varname'] ?? null ) ? strtolower( $column['varname'] ) : '';
                $label = is_string( $column['label'] ?? null ) ? strtolower( trim( $column['label'] ) ) : '';
                if ( ! in_array( $name, [ 'notes', 'parent_note', 'waiting_list_notes' ], true ) && 'notes' !== $label ) { continue; }
                if ( ! in_array( $column['type'] ?? '', [ 'text', 'textarea', 'string' ], true ) || false === filter_var( $column['column_id'] ?? null, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) { continue; }
                $matches[] = [ 'group_id' => (int) $group['group_id'], 'column_id' => (int) $column['column_id'], 'value' => is_string( $column['value'] ?? null ) ? $column['value'] : '' ];
            }
        }
        if ( 1 !== count( $matches ) ) { throw new RuntimeException( 'The section needs one identifiable text Notes field.' ); }
        $field = $matches[0];
        $saved = self::make_request( 'https://www.onlinescoutmanager.co.uk/ext/customdata/?action=updateColumn&section_id=' . $section_id, 'POST', [
            'associated_type' => 'member', 'associated_id' => $member_id, 'associated_is_section' => 'false',
            'group_id' => $field['group_id'], 'column_id' => $field['column_id'], 'context' => 'members',
            'value' => ( '' !== trim( $field['value'] ) ? $field['value'] . "\n\n" : '' ) . $note,
        ], $token );
        if ( true !== ( $saved['status'] ?? null ) && true !== ( $saved['ok'] ?? null ) && 'ok' !== ( $saved['result'] ?? null ) ) { throw new RuntimeException( 'Notes update was not confirmed.' ); }
        if ( ! empty( $saved['errors'] ) ) { throw new RuntimeException( 'Notes update was rejected.' ); }
    }

    /**
     * Make one authenticated API attempt without following redirects.
     * @param string $url Fixed OSM endpoint assembled by this class.
     * @param string $method GET or POST.
     * @param array<string,mixed> $body Form-encoded request parameters.
     * @param string|false $token Server-only bearer token, or false for a token request.
     * @return array<string|int,mixed> Decoded JSON; raw errors are never propagated.
     * @throws Exception If transport, HTTP, JSON or OSM status validation fails.
     */
    private static function make_request( $url, $method = 'GET', $body = [], $token = false ) {
        $args = [
            'method'      => $method,
            'timeout'     => 30,
            'redirection' => 0,
            'headers'     => [ 'Accept' => 'application/json' ],
        ];

        if ( $body ) {
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['body'] = http_build_query( $body );
        }

        if ( $token ) {
            $args['headers']['Authorization'] = "Bearer $token";
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'Unable to contact OSM. Please try again later.' );
        }

        self::record_api_usage( $response );
        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            if ( 401 === $status ) {
                self::clear_authorization();
            }
            throw new Exception( 429 === $status ? 'OSM request limit reached. Please try again later.' : 'OSM request failed. Check your application permissions.' );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            throw new Exception( 'OSM returned an unexpected response.' );
        }
        if ( ! empty( $data['error'] ) || ( isset( $data['status'] ) && in_array( $data['status'], [ false, 0, '0', 'false', 'error' ], true ) ) || ( isset( $data['ok'] ) && in_array( $data['ok'], [ false, 0, '0', 'false' ], true ) ) ) {
            // Do not expose raw OSM errors, credentials, or personal data to visitors.
            throw new Exception( 'OSM could not complete the request. Check your application permissions.' );
        }

        return $data;
    }
}
