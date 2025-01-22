<?php

class OSM_API {
    private static $access_token = null;

    /**
     * Authorize and retrieve an access token.
     */
    public static function authorize($force = false) {
        if ( self::$access_token && ! $force ) {
            return self::$access_token;
        }

        $cached_token = OSM_Cache::get( 'access_token' );
        if ( $cached_token && ! $force ) {
            self::$access_token = $cached_token;
            return $cached_token;
        }

        $client_id = get_option( 'osm_client_id' );
        $client_secret = get_option( 'osm_client_secret' );

        if ( ! $client_id || ! $client_secret ) {
            throw new Exception( 'Client ID or Secret not set in OSM Settings.' );
        }

        $response = self::make_request(
            'https://www.onlinescoutmanager.co.uk/oauth/token',
            'POST',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'scope'         => 'section:programme:read section:event:read',
            ],
            false
        );

        if ( isset( $response['access_token'] ) ) {
            $access_token = $response['access_token'];
            $expires_in = $response['expires_in'] ?? 3600;

            OSM_Cache::set( 'access_token', $access_token, $expires_in - 60 );
            self::$access_token = $access_token;

            return $access_token;
        }

        throw new Exception( 'Authorization failed. Please check your credentials.' );
    }

    /**
     * Get a list of sections from the API.
     *
     * @return array
     */
    public static function get_sections() {
        $cache_key = 'osm_sections';
        $cached_sections = OSM_Cache::get( $cache_key );

        // Return cached sections if available
        if ( $cached_sections ) {
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
            $carry[$section['sectionid']] = [
                'groupname' => $section['groupname'],
                'sectionname' => $section['sectionname'],
                'section' => $section['section'],
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
     * Retrieve the current term for a section.
     * 
     * @param int $sectionid
     * @return int
     */
    public static function get_current_term( $sectionid ) {
        $cache_key = "current_term_{$sectionid}";
        $cached_termid = OSM_Cache::get( $cache_key );

        if ( $cached_termid ) {
            return $cached_termid;
        }

        $token = self::authorize();
        $terms = self::make_request(
            'https://www.onlinescoutmanager.co.uk/api.php?action=getTerms',
            'GET',
            [],
            $token
        );

        if ( isset( $terms[ $sectionid ] ) ) {
            $today = date( 'Y-m-d' );
            foreach ( $terms[ $sectionid ] as $term ) {
                if ( $term['startdate'] <= $today && $term['enddate'] >= $today ) {
                    OSM_Cache::set( $cache_key, $term['termid'], 86400 ); // Cache for 24 hours
                    return $term['termid'];
                }
            }
        }

        throw new Exception( "No current term found for section ID: $sectionid" );
    }

    /**
     * Retrieve the programme for a section and term.
     */
    public static function get_programme( $sectionid, $termid ) {
        $cache_key = "programme_{$sectionid}_{$termid}";
        $cached_programme = OSM_Cache::get( $cache_key );

        if ( $cached_programme ) {
            return $cached_programme;
        }

        $token = self::authorize();
        $programme = self::make_request(
            "https://www.onlinescoutmanager.co.uk/programme.php?action=getProgramme&sectionid={$sectionid}&termid={$termid}",
            'GET',
            [],
            $token
        );

        if ( $programme ) {
            OSM_Cache::set( $cache_key, $programme, 86400 ); // Cache for 24 hours
        }

        return $programme;
    }

    /**
     * Retrieve events for a section and term.
     */
    public static function get_events( $sectionid, $termid ) {
        $cache_key = "events_{$sectionid}_{$termid}";
        $cached_events = OSM_Cache::get( $cache_key );

        if ( $cached_events ) {
            return $cached_events;
        }

        $token = self::authorize();
        $events = self::make_request(
            "https://www.onlinescoutmanager.co.uk/ext/events/summary/?action=get&sectionid={$sectionid}&termid={$termid}",
            'GET',
            [],
            $token
        );

        if ( $events ) {
            OSM_Cache::set( $cache_key, $events, 86400 ); // Cache for 24 hours
        }

        return $events;
    }

    /**
     * Make an authenticated API request.
     */
    private static function make_request( $url, $method = 'GET', $body = [], $token = false ) {
        $args = [
            'method'      => $method,
            'timeout'     => 30,
            'headers'     => [],
            'body'        => $body ? http_build_query( $body ) : null,
        ];

        if ( $token ) {
            $args['headers']['Authorization'] = "Bearer $token";
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'API request failed: ' . $response->get_error_message() );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $data['error'] ) ) {
            throw new Exception( 'API error: ' . $data['error'] );
        }

        return $data;
    }
}