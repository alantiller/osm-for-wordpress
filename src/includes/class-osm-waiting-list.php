<?php
declare(strict_types=1);

/**
 * Process minimal direct applications through OSM. Application form
 * fields are processed only in memory; no application fields are persisted,
 * logged or included in notification emails by this plugin. Opaque anti-abuse keys expire through cron.
 */
class OSM_Waiting_List {
    /** @var array<string,mixed>|null Request-local result; applicant fields are never persisted. */
    private static ?array $result = null;

    /** Register public submission/token handlers and opaque-key cleanup. @return void */
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'handle_submission' ] );
        add_action( 'wp_ajax_osm_waiting_list_prepare', [ $this, 'prepare' ] );
        add_action( 'wp_ajax_nopriv_osm_waiting_list_prepare', [ $this, 'prepare' ] );
        add_action( 'osm_waiting_list_cleanup', [ $this, 'cleanup' ] );
    }

    /**
     * Delete an expired opaque submission/rate key scheduled by this class.
     * @param string $key Internal option name; other option prefixes are refused.
     * @return void
     */
    public function cleanup( string $key ): void {
        if ( str_starts_with( $key, 'osm_waiting_claim_' ) || str_starts_with( $key, 'osm_waiting_rate_' ) ) {
            delete_option( $key );
        }
    }

    /**
     * Create a short-lived signed form token, independent of cached page HTML.
     * @return array{nonce:string,token:string} Public nonce and signed random, expiring token.
     */
    public static function form_credentials(): array {
        $payload = bin2hex( random_bytes( 24 ) ) . '.' . ( time() + 3600 ) . '.' . (int) get_option( 'osm_waiting_list_section_id', 0 );
        return [ 'nonce' => wp_create_nonce( 'osm_waiting_list_submit' ), 'token' => $payload . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) ) ];
    }

    /** Return fresh credentials to visitors without handling applicant data. @return void Sends JSON and terminates. */
    public function prepare(): void {
        nocache_headers();
        if ( (int) get_option( 'osm_waiting_list_section_id', 0 ) < 1 ) {
            wp_send_json_error( [ 'message' => 'The waiting list is currently unavailable.' ], 503 );
        }
        wp_send_json_success( self::form_credentials() );
    }

    /**
     * Validate applicant fields without storing them or exposing raw request content.
     * @param array<string,mixed> $input Untrusted submitted form fields.
     * @return array{values:array<string,string>,errors:list<string>} Sanitised fields and safe validation messages.
     */
    public static function validate_application( array $input ): array {
        $values = [];
        $errors = [];
        $labels = [ 'firstname' => 'Child’s first name', 'lastname' => 'Child’s last name', 'parent_firstname' => 'Adult’s first name', 'parent_lastname' => 'Adult’s last name', 'email' => 'Adult’s email' ];
        foreach ( $labels as $field => $label ) {
            $raw = $input[ $field ] ?? '';
            $values[ $field ] = is_string( $raw ) ? sanitize_text_field( wp_unslash( $raw ) ) : '';
            if ( '' === $values[ $field ] || strlen( $values[ $field ] ) > ( 'email' === $field ? 254 : 120 ) ) {
                $errors[] = $label . ' is required and must be within the allowed length.';
            }
        }
        // Keep old already-open forms working, but never let a legacy value bypass incomplete new fields.
        $has_parts = isset( $input['dob_day'] ) || isset( $input['dob_month'] ) || isset( $input['dob_year'] );
        $legacy_date = is_string( $input['dob'] ?? null ) && preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $input['dob'] ) ? explode( '-', $input['dob'] ) : [];
        foreach ( [ 'day' => 2, 'month' => 1, 'year' => 0 ] as $part => $legacy_index ) {
            $raw = $has_parts ? ( $input[ 'dob_' . $part ] ?? '' ) : ( $legacy_date[ $legacy_index ] ?? '' );
            $values[ 'dob_' . $part ] = is_string( $raw ) ? sanitize_text_field( wp_unslash( $raw ) ) : '';
        }
        $values['dob'] = '';
        if ( preg_match( '/^[0-9]{1,2}$/D', $values['dob_day'] ) && preg_match( '/^[0-9]{1,2}$/D', $values['dob_month'] ) && preg_match( '/^[0-9]{4}$/D', $values['dob_year'] )
            && checkdate( (int) $values['dob_month'], (int) $values['dob_day'], (int) $values['dob_year'] ) ) {
            $values['dob'] = sprintf( '%04d-%02d-%02d', (int) $values['dob_year'], (int) $values['dob_month'], (int) $values['dob_day'] );
        }
        if ( '' === $values['dob'] || $values['dob'] > current_time( 'Y-m-d' ) || $values['dob'] < '1900-01-01' ) {
            $errors[] = 'Enter a valid date of birth using day, month and four-digit year, not in the future.';
        }
        $raw_note = $input['parent_note'] ?? '';
        $values['parent_note'] = is_string( $raw_note ) ? sanitize_textarea_field( wp_unslash( $raw_note ) ) : '';
        if ( ! is_string( $raw_note ) || preg_match_all( '/./us', $values['parent_note'] ) > 2000 ) { $errors[] = 'Keep additional notes within 2,000 characters.'; }
        $raw_help = $input['willing_to_help'] ?? '';
        $values['willing_to_help'] = is_string( $raw_help ) ? sanitize_text_field( wp_unslash( $raw_help ) ) : '';
        if ( ! is_string( $raw_help ) || ! in_array( $values['willing_to_help'], [ '', 'yes', 'no', 'discuss' ], true ) ) { $errors[] = 'Choose a valid answer to the parent or carer help question.'; }
        if ( ! is_email( $values['email'] ) ) {
            $errors[] = 'Enter a valid adult email address.';
        }
        if ( '1' !== ( $input['privacy_acknowledged'] ?? '' ) ) {
            $errors[] = 'Read and acknowledge the privacy notice before applying.';
        }
        return compact( 'values', 'errors' );
    }

    /**
     * Atomically consume a signed form token and limit attempts per hashed IP/hour.
     * Only opaque keys and expiry timestamps are stored; no form fields or raw IPs.
     * @param string $token Signed form token belonging to the current destination.
     * @return void
     * @throws RuntimeException If invalid, expired, already used or rate limited.
     */
    private static function claim_submission( string $token ): void {
        $parts = explode( '.', $token );
        if ( count( $parts ) !== 4 || ! preg_match( '/^[a-f0-9]{48}$/D', $parts[0] ) || ! ctype_digit( $parts[1] )
            || (int) $parts[1] < time() || (int) $parts[1] > time() + 3600 || $parts[2] !== (string) (int) get_option( 'osm_waiting_list_section_id', 0 )
            || ! hash_equals( hash_hmac( 'sha256', implode( '.', array_slice( $parts, 0, 3 ) ), wp_salt( 'nonce' ) ), $parts[3] ) ) {
            throw new RuntimeException( 'The form expired. Reload the page and try again.' );
        }
        $claim = 'osm_waiting_claim_' . hash( 'sha256', $token );
        if ( ! add_option( $claim, time() + 86400, '', false ) ) {
            throw new RuntimeException( 'This application was already sent or attempted. Contact the group before applying again.' );
        }
        wp_schedule_single_event( time() + 86400, 'osm_waiting_list_cleanup', [ $claim ] );
        // REMOTE_ADDR is the trusted server peer; untrusted forwarding headers are ignored.
        $peer = is_string( $_SERVER['REMOTE_ADDR'] ?? null ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        $bucket = hash_hmac( 'sha256', $peer . ':' . (int) floor( time() / 3600 ), wp_salt( 'nonce' ) );
        for ( $slot = 0; $slot < 5; $slot++ ) {
            $rate_key = 'osm_waiting_rate_' . $bucket . '_' . $slot;
            if ( add_option( $rate_key, time() + 3600, '', false ) ) {
                wp_schedule_single_event( time() + 7200, 'osm_waiting_list_cleanup', [ $rate_key ] );
                return;
            }
        }
        throw new RuntimeException( 'Too many applications have been attempted from this connection. Please try later or contact the group.' );
    }

    /**
     * Process same-page POSTs once; retain validation values only for this response.
     * A partial/uncertain API result must never trigger automatic retries or deletion.
     * @return void Public results are rendered by the shortcode on the same page.
     */
    public function handle_submission(): void {
        if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) || ! isset( $_POST['osm_waiting_list_submit'] ) ) {
            return;
        }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        self::$result = [ 'message' => '', 'errors' => [], 'values' => [], 'complete' => false ];
        if ( (int) get_option( 'osm_waiting_list_section_id', 0 ) < 1 ) {
            self::$result['message'] = 'The waiting list is currently unavailable. Please contact the group.';
            return;
        }
        $nonce = is_string( $_POST['_osm_nonce'] ?? null ) ? sanitize_text_field( wp_unslash( $_POST['_osm_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'osm_waiting_list_submit' ) || ! empty( $_POST['website'] ) ) {
            self::$result['message'] = 'The form could not be verified. Reload the page and try again.';
            return;
        }
        $validated = self::validate_application( $_POST );
        self::$result = array_merge( self::$result, $validated );
        if ( $validated['errors'] ) {
            return;
        }
        try {
            $token = is_string( $_POST['_osm_token'] ?? null ) ? sanitize_text_field( wp_unslash( $_POST['_osm_token'] ) ) : '';
            self::claim_submission( $token );
        } catch ( RuntimeException $error ) {
            self::$result['message'] = $error->getMessage();
            self::$result['values'] = [];
            return;
        }
        try {
            OSM_API::create_waiting_list_member( (int) get_option( 'osm_waiting_list_section_id', 0 ), $validated['values'] );
            OSM_Notifications::send_waiting_list_notification( false );
            self::$result = [ 'message' => 'Your application has been added to the waiting list. The group will contact you about next steps.', 'complete' => true, 'errors' => [], 'values' => [] ];
        } catch ( OSM_Waiting_List_Partial_Exception $error ) {
            OSM_Notifications::send_waiting_list_notification( true );
            self::$result = [ 'message' => $error->getMessage(), 'complete' => true, 'errors' => [], 'values' => [] ];
        } catch ( Throwable $error ) {
            self::$result = [ 'message' => 'We could not confirm receipt of your application. Please contact the group before applying again.', 'complete' => true, 'errors' => [], 'values' => [] ];
        }
    }
    /**
     * Render the direct application form for the saved waiting-list selection.
     * @param mixed $sectionids Optional legacy IDs; when supplied they must match the configured destination.
     * @param bool $preview Whether an editor is requesting an inert preview.
     * @return string Escaped form HTML or request-local outcome text.
     * @throws RuntimeException If the selected destination is missing, mismatched or inaccessible.
     */
    public static function render( $sectionids, bool $preview = false ): string {
        $section_id = self::section_id( get_option( 'osm_waiting_list_section_id', 0 ) );
        if ( ! is_array( $sectionids ) || $section_id < 1 || ( $sectionids && array_values( array_map( [ self::class, 'section_id' ], $sectionids ) ) !== [ $section_id ] ) ) {
            throw new RuntimeException( 'Select a section on the Waiting List settings page.' );
        }
        $sections = OSM_API::get_sections();
        if ( ! isset( $sections[ $section_id ] ) ) { throw new RuntimeException( 'The waiting-list section is unavailable.' ); }
        $privacy_url = get_privacy_policy_url();
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        if ( ! headers_sent() ) { nocache_headers(); }
        wp_enqueue_script( 'osm-waiting-list', OSM_ASSETS_URI . '/js/waiting-list.js', [], OSM_PLUGIN_VERSION, true );
        $credentials = self::form_credentials();
        $result = self::$result ?? [ 'message' => '', 'errors' => [], 'values' => [], 'complete' => false ];
        ob_start();
        try { include OSM_TEMPLATES_DIR . '/shortcode/waiting-list-form.php'; return ob_get_contents(); }
        finally { ob_end_clean(); }
    }

    /**
     * Normalize a positive, non-overflowing section identifier.
     * @param mixed $value Stored setting or legacy shortcode section ID.
     * @return int Positive ID, or zero for invalid data.
     */
    private static function section_id( $value ): int {
        return ( is_string( $value ) || is_int( $value ) ) && false !== filter_var( $value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ? (int) $value : 0;
    }
}
