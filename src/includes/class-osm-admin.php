<?php
declare(strict_types=1);

/** Manage the plugin's administrator-only configuration and privacy controls. */
class OSM_Admin {
    /** Register protected settings and privacy-help hooks. @return void */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'admin_post_osm_save_auth', [ $this, 'save_auth' ] );
        add_action( 'admin_post_osm_save_sections', [ $this, 'save_sections' ] );
        add_action( 'admin_post_osm_save_section_points', [ $this, 'save_section_points' ] );
        add_action( 'admin_post_osm_save_notifications', [ $this, 'save_notifications' ] );
        add_action( 'admin_post_osm_save_waiting_list', [ $this, 'save_waiting_list' ] );
        add_action( 'admin_post_osm_clear_waiting_list', [ $this, 'clear_waiting_list' ] );
        // admin_post can supply an empty action argument; preserve the handler's nonce default.
        add_action( 'admin_post_osm_save_privacy', [ $this, 'save_privacy' ], 10, 0 );
        add_action( 'admin_post_osm_save_public_data', [ $this, 'save_public_data' ] );
        add_action( 'admin_post_osm_purge_cache', [ $this, 'purge_cache' ] );
        add_action( 'admin_post_osm_reset_configuration', [ $this, 'reset_configuration' ] );
        add_action( 'admin_post_osm_save_advanced_options', [ $this, 'save_advanced_options' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
        add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
    }

    /** Register the administrator-only settings page. @return void */
    public function add_admin_page(): void {
        add_menu_page( 'OSM Settings', 'OSM Settings', 'manage_options', 'online-scout-manager', [ $this, 'render_admin_page' ], 'dashicons-admin-tools' );
    }

    /**
     * Authorise the current administrator before reading or changing configuration.
     *
     * @param string|null $nonce Nonce action to verify for a settings mutation, or null for a read.
     * @return void WordPress terminates the request if either check fails.
     */
    private function require_admin( ?string $nonce = null ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to manage OSM settings.', '', [ 'response' => 403 ] );
        }
        if ( $nonce ) {
            check_admin_referer( $nonce );
        }
    }

    /**
     * Read a scalar text field without accepting array-shaped request data.
     *
     * @param string $key Name of the submitted field.
     * @return string Sanitised text, or an empty string when missing or malformed.
     */
    private function posted_text( string $key ): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This helper is called only after the save action verifies its own nonce.
        return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
    }

    /**
     * Store a short-lived notice and finish the settings request with a safe redirect.
     *
     * @param string $type Notice type: success or error.
     * @param string $message Administrator-facing text with no credentials or member data.
     * @param string $tab Known settings tab, optionally followed by a local fragment.
     * @return void This method terminates the request after redirecting.
     */
    private function finish( string $type, string $message, string $tab = 'general' ): void {
        set_transient( 'osm_admin_notice', [ 'type' => $type, 'message' => $message ], 30 );
        wp_safe_redirect( admin_url( 'admin.php?page=online-scout-manager&tab=' . $tab ) );
        exit;
    }

    /**
     * Read the selected waiting-list destination without loading application data.
     * @return array{section_id:int} Validated saved destination, or zero when unconfigured.
     */
    private function get_waiting_list_settings(): array {
        $section_id = get_option( 'osm_waiting_list_section_id', 0 );
        return [ 'section_id' => false !== filter_var( $section_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ? (int) $section_id : 0 ];
    }

    /**
     * Render native WordPress settings, loading section data only where it is useful.
     *
     * Legacy settings bookmarks open the corresponding General or Configuration page. API failures
     * leave saved configuration intact and are shown as administrator notices.
     *
     * @return void Outputs the settings template without exposing saved credentials.
     */
    public function render_admin_page(): void {
        $this->require_admin();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab navigation; values are allowlisted and never mutate settings.
        $tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        $aliases = [ 'section_points_options' => 'configuration', 'patrol_points_options' => 'configuration', 'privacy_options' => 'configuration', 'public_data' => 'configuration', 'advanced_options' => 'configuration', 'shortcodes' => 'general', 'sections' => 'general' ];
        $tab = $aliases[ $tab ] ?? $tab;
        $active_tab = in_array( $tab, [ 'general', 'configuration', 'authentication' ], true ) ? $tab : 'general';
        $client_id = get_option( 'osm_client_id' );
        $client_secret = get_option( 'osm_client_secret' );
        $enabled_sections = array_filter( (array) get_option( 'osm_enabled_sections', [] ) );
        $public_patrol_sections = (array) get_option( 'osm_public_patrol_sections', [] );
        $waiting_list = $this->get_waiting_list_settings();
        $section_members = [];
        $member_errors = [];
        $advanced_options = [
            'osm_date_format' => OSM_Options::get_date_format() ?? '',
            'osm_time_format' => OSM_Options::get_time_format() ?? '',
        ];
        $sections = [];
        $current_terms = [];
        $api_error = '';
        if ( $client_id && $client_secret && in_array( $active_tab, [ 'general', 'configuration' ], true ) ) {
            try {
                $sections = OSM_API::get_sections();
            } catch ( Throwable $error ) {
                $api_error = $error->getMessage();
            }
            if ( 'general' === $active_tab ) {
                foreach ( $enabled_sections + array_filter( $public_patrol_sections ) as $section_id => $enabled ) {
                    try {
                        $current_terms[ $section_id ] = OSM_API::get_current_term( $section_id );
                    } catch ( Throwable $error ) {
                        // Waiting-list sections may legitimately have no current term.
                        $current_terms[ $section_id ] = 'No current term';
                    }
                }
            }
        }
        if ( 'configuration' === $active_tab ) {
            foreach ( array_filter( $public_patrol_sections ) as $section_id => $enabled ) {
                if ( ! isset( $sections[ $section_id ] ) ) { continue; }
                try { $section_members[ $section_id ] = OSM_API::get_section_members_for_admin( (int) $section_id ); }
                catch ( Throwable $error ) { $member_errors[ $section_id ] = 'Members could not be loaded. Saved exclusions are kept below; try again later.'; }
            }
        }
        $waiting_list_sections = $sections;
        // Put known waiting-list types first, while keeping other accessible sections selectable.
        uasort( $waiting_list_sections, static function ( $first_section, $second_section ) {
            $first_waiting = 'waitinglist' === preg_replace( '/[^a-z]/', '', strtolower( $first_section['section'] ) );
            $second_waiting = 'waitinglist' === preg_replace( '/[^a-z]/', '', strtolower( $second_section['section'] ) );
            return (int) $second_waiting <=> (int) $first_waiting;
        } );
        $notification_users = 'configuration' === $active_tab ? get_users( [ 'fields' => [ 'ID', 'display_name', 'user_email' ], 'orderby' => 'display_name' ] ) : [];
        $notification_recipients = OSM_Notifications::get_recipient_ids();
        $notification_delivery = (array) get_option( 'osm_notification_last_delivery', [] );
        $api_usage = OSM_API::get_api_usage();
        include OSM_TEMPLATES_DIR . '/admin/settings.php';
    }

    /**
     * Verify submitted credentials, restoring the old account on authentication failure.
     *
     * Blank fields retain existing secrets. A successful account change clears all
     * publication choices, including the globally selected waiting-list form.
     *
     * @return void Redirects with a status notice after the authorised request.
     */
    public function save_auth(): void {
        $this->require_admin();
        check_admin_referer( 'osm_auth_nonce' );
        $previous_id = get_option( 'osm_client_id', '' );
        $previous_secret = get_option( 'osm_client_secret', '' );
        $client_id = $this->posted_text( 'osm_client_id' ) ?: $previous_id;
        $client_secret = $this->posted_text( 'osm_client_secret' ) ?: $previous_secret;
        if ( ! $client_id || ! $client_secret ) {
            $this->finish( 'error', 'Enter both a Client ID and Client Secret.', 'authentication' );
        }
        $previous_usage = get_option( 'osm_api_usage', null );
        delete_option( 'osm_api_usage' );
        OSM_Cache::clear_all();
        OSM_API::clear_authorization();
        update_option( 'osm_client_id', $client_id, false );
        update_option( 'osm_client_secret', $client_secret, false );
        try {
            OSM_API::authorize( true );
            if ( $client_id !== $previous_id || $client_secret !== $previous_secret ) {
                foreach ( [ 'osm_enabled_sections', 'osm_public_patrol_sections', 'osm_section_points_settings', 'osm_waiting_list_oauth', 'osm_waiting_list_oauth_generation', 'osm_waiting_list_urls', 'osm_waiting_list_section_id', 'osm_waiting_list_form_url', 'osm_waiting_list_mode' ] as $option ) {
                    delete_option( $option );
                }
            }
            $this->finish( 'success', 'Authentication saved and verified. Enable the sections you want to publish.', 'authentication' );
        } catch ( Exception $error ) {
            if ( null !== $previous_usage ) { update_option( 'osm_api_usage', $previous_usage, false ); } else { delete_option( 'osm_api_usage' ); }
            OSM_Cache::clear_all();
            OSM_API::clear_authorization();
            update_option( 'osm_client_id', $previous_id, false );
            update_option( 'osm_client_secret', $previous_secret, false );
            $this->finish( 'error', 'Authentication failed. Previous credentials have been retained. ' . $error->getMessage(), 'authentication' );
        }
    }

    /**
     * Save independent programme/event and Section Points publication switches.
     *
     * Only accessible section IDs are accepted. Name policies and exclusions remain
     * intact when a display is switched off. Waiting-list configuration is independent.
     * @return void Redirects to General after capability and nonce verification.
     */
    public function save_sections(): void {
        $this->require_admin();
        check_admin_referer( 'osm_sections_nonce' );
        try {
            $sections = OSM_API::get_sections();
            $choices = [];
            foreach ( [ 'osm_enabled_sections', 'osm_public_patrol_sections' ] as $option ) {
                $submitted = isset( $_POST[ $option ] ) && is_array( $_POST[ $option ] ) ? map_deep( wp_unslash( $_POST[ $option ] ), 'sanitize_text_field' ) : [];
                $choices[ $option ] = [];
                foreach ( $sections as $section_id => $section ) {
                    if ( '1' === ( $submitted[ $section_id ] ?? '' ) ) { $choices[ $option ][ $section_id ] = 1; }
                }
            }
            foreach ( $choices as $option => $value ) { update_option( $option, $value, false ); }
        } catch ( Throwable $error ) {
            $this->finish( 'error', 'Sections were not saved. Please check your OSM connection.', 'general' );
        }
        $this->finish( 'success', 'Sections updated successfully.', 'general' );
    }

    /**
     * Save one accessible section's name formats and member exclusions.
     *
     * A failed validation changes nothing. Missing-roster exclusion IDs are submitted
     * separately so an OSM outage or member leaving the roster cannot silently clear them.
     * @return void Redirects to Section Points after the protected settings mutation.
     */
    public function save_section_points(): void {
        $this->require_admin();
        check_admin_referer( 'osm_section_points_nonce' );
        try {
            $section_id = filter_var( $this->posted_text( 'osm_section_id' ), FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
            if ( false === $section_id || ! isset( OSM_API::get_sections()[ $section_id ] ) ) { throw new InvalidArgumentException( 'Choose an accessible section.' ); }
            $excluded = isset( $_POST['osm_excluded_members'] ) ? map_deep( wp_unslash( $_POST['osm_excluded_members'] ), 'sanitize_text_field' ) : [];
            if ( ! is_array( $excluded ) ) { throw new InvalidArgumentException( 'Choose valid excluded members.' ); }
            $retained = preg_split( '/[\s,]+/', $this->posted_text( 'osm_other_excluded_members' ), -1, PREG_SPLIT_NO_EMPTY );
            OSM_Options::set_section_points( $section_id, $this->posted_text( 'osm_firstname_display' ), $this->posted_text( 'osm_lastname_display' ), array_merge( $excluded, $retained ) );
        } catch ( Throwable $error ) {
            $this->finish( 'error', 'Section Points settings were not saved. Check the section, name formats and member IDs.', 'configuration#osm-section-points' );
        }
        $this->finish( 'success', 'Section Points settings saved.', 'configuration#osm-section-points' );
    }

    /**
     * Save notification recipients selected from existing WordPress accounts.
     *
     * Empty selection disables mail. A malformed or unavailable account retains the old
     * configuration rather than partially saving. Only user IDs are persisted.
     * @return void Redirects to Configuration after capability and nonce verification.
     */
    public function save_notifications(): void {
        $this->require_admin();
        check_admin_referer( 'osm_notifications_nonce' );
        $submitted = isset( $_POST['osm_notification_user_ids'] ) ? map_deep( wp_unslash( $_POST['osm_notification_user_ids'] ), 'sanitize_text_field' ) : [];
        $ids = [];
        if ( ! is_array( $submitted ) ) { $this->finish( 'error', 'Choose valid WordPress users.', 'configuration#osm-notifications' ); }
        foreach ( $submitted as $value ) {
            $user_id = is_scalar( $value ) ? filter_var( $value, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) : false;
            $user = false !== $user_id ? get_userdata( $user_id ) : false;
            if ( ! $user || ! is_email( $user->user_email ) ) { $this->finish( 'error', 'A selected user is unavailable or has no valid email. Your previous selection was kept.', 'configuration#osm-notifications' ); }
            $ids[] = $user_id;
        }
        update_option( 'osm_notification_user_ids', array_values( array_unique( $ids ) ), false );
        $this->finish( 'success', $ids ? 'Notification recipients saved.' : 'Notifications disabled.', 'configuration#osm-notifications' );
    }

    /**
     * Save the waiting-list section selected from authenticated OSM section labels.
     * @return void Redirects to the waiting-list tile; invalid or inaccessible IDs retain the previous setting.
     */
    public function save_waiting_list(): void {
        $this->require_admin();
        check_admin_referer( 'osm_waiting_list_nonce' );
        $submitted_id = isset( $_POST['osm_waiting_list_section_id'] ) && is_string( $_POST['osm_waiting_list_section_id'] ) ? sanitize_text_field( wp_unslash( $_POST['osm_waiting_list_section_id'] ) ) : '';
        if ( '' !== $submitted_id && false === filter_var( $submitted_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) {
            $this->finish( 'error', 'Choose a valid waiting-list section.', 'configuration#osm-waiting-list' );
        }
        $section_id = (int) $submitted_id;
        if ( $section_id > 0 ) {
            try { $sections = OSM_API::get_sections(); }
            catch ( Throwable $error ) { $this->finish( 'error', 'Unable to check OSM section access. Your selection has been kept.', 'configuration#osm-waiting-list' ); }
            if ( ! isset( $sections[ $section_id ] ) ) {
                $this->finish( 'error', 'Choose a section available to your OSM application.', 'configuration#osm-waiting-list' );
            }
        }
        update_option( 'osm_waiting_list_section_id', $section_id, false );
        $this->finish( 'success', 'Waiting list section saved.', 'configuration#osm-waiting-list' );
    }

    /**
     * Disable public applications by removing the selected destination.
     *
     * Previously rendered forms are also refused by the submission and API guards.
     * @return void Redirects to the waiting-list tile after capability and nonce checks.
     */
    public function clear_waiting_list(): void {
        $this->require_admin( 'osm_clear_waiting_list_nonce' );
        delete_option( 'osm_waiting_list_section_id' );
        $this->finish( 'success', 'Waiting list disabled.', 'configuration#osm-waiting-list' );
    }

    /**
     * Save publication choices and independent full/initial/hidden name limits.
     * @param string $nonce Action nonce, including the legacy public-data action.
     * @return void Redirects to Configuration; invalid modes are not saved.
     */
    public function save_privacy( string $nonce = 'osm_privacy_nonce' ): void {
        $this->require_admin();
        check_admin_referer( $nonce );
        $enabled = array_filter( (array) get_option( 'osm_enabled_sections', [] ) );
        $submitted = isset( $_POST['osm_public_patrol_sections'] ) && is_array( $_POST['osm_public_patrol_sections'] ) ? map_deep( wp_unslash( $_POST['osm_public_patrol_sections'] ), 'sanitize_text_field' ) : [];
        $public = [];
        foreach ( $enabled as $section_id => $value ) {
            if ( '1' === ( $submitted[ $section_id ] ?? '' ) ) { $public[ (int) $section_id ] = 1; }
        }
        try {
            OSM_Options::set_name_display( $this->posted_text( 'osm_firstname_display' ) ?: 'hidden', $this->posted_text( 'osm_lastname_display' ) ?: 'hidden' );
        } catch ( InvalidArgumentException $error ) {
            $this->finish( 'error', $error->getMessage(), 'configuration#osm-section-points' );
        }
        update_option( 'osm_public_patrol_sections', $public, false );
        delete_option( 'osm_patrol_scope' );
        OSM_API::clear_authorization();
        $this->finish( 'success', 'Section Points settings saved.', 'configuration#osm-section-points' );
    }

    /**
     * Retain the old authenticated save action for already-open settings pages.
     *
     * Legacy waiting-list URL options are preserved for explicit-section displays;
     * new waiting-list configuration is managed in Configuration.
     *
     * @return void Delegates to the protected Privacy save handler and redirects.
     */
    public function save_public_data(): void {
        $this->save_privacy( 'osm_public_data_nonce' );
    }

    /**
     * Validate and save display date/time formats using the existing options service.
     *
     * @return void Redirects to Configuration with a success or validation notice.
     */
    public function save_advanced_options(): void {
        $this->require_admin();
        check_admin_referer( 'osm_advanced_options_nonce' );
        try {
            OSM_Options::set_date_format( $this->posted_text( 'osm_date_format' ) );
            OSM_Options::set_time_format( $this->posted_text( 'osm_time_format' ) );
            $this->finish( 'success', 'Display options saved successfully.', 'configuration' );
        } catch ( Exception $error ) {
            $this->finish( 'error', 'Display options were not saved. ' . $error->getMessage(), 'configuration' );
        }
    }

    /**
     * Clear plugin API caches and tokens without changing configured settings.
     *
     * @return void Redirects to General after the administrator and nonce checks.
     */
    public function purge_cache(): void {
        $this->require_admin();
        check_admin_referer( 'osm_purge_nonce' );
        OSM_Cache::clear_all();
        OSM_API::clear_authorization();
        $this->finish( 'success', 'Cache purged successfully.', 'configuration#osm-maintenance' );
    }

    /**
     * Remove credentials, publication consent, waiting-list settings, formats, and caches.
     *
     * @return void Redirects to the unconfigured General page after nonce verification.
     */
    public function reset_configuration(): void {
        $this->require_admin();
        check_admin_referer( 'osm_reset_nonce' );
        OSM_Cache::clear_all();
        OSM_API::clear_authorization();
        foreach ( [ 'osm_client_id', 'osm_client_secret', 'osm_enabled_sections', 'osm_public_patrol_sections', 'osm_section_points_settings', 'osm_api_usage', 'osm_notification_user_ids', 'osm_notification_last_delivery', 'osm_waiting_list_oauth', 'osm_waiting_list_oauth_generation', 'osm_waiting_list_urls', 'osm_waiting_list_section_id', 'osm_waiting_list_form_url', 'osm_waiting_list_mode', 'osm_patrol_scope', 'osm_option_date_format', 'osm_option_time_format', 'osm_option_firstname_display', 'osm_option_lastname_display' ] as $option ) {
            delete_option( $option );
        }
        $this->finish( 'success', 'Configuration and cached data reset successfully.' );
    }

    /**
     * Add editable explanatory text to WordPress's privacy policy guide.
     *
     * @return void Does nothing on WordPress versions without the privacy guide API.
     */
    public function add_privacy_policy_content(): void {
        if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
            wp_add_privacy_policy_content( 'Online Scout Manager', '<p>Our website can display programme, event and approved patrol names from Online Scout Manager (OSM); patrol member names are hidden, shown in full or reduced to initials according to our Section Points settings. Other member details are excluded. Our website waiting-list form sends applications directly to OSM. The website form processes the child’s name/date of birth and the adult’s name/email, then sends them, optional parent notes and volunteering answers to OSM without storing application fields in WordPress. Selected WordPress users may receive a notification email that contains no applicant fields. Opaque submission keys and hourly hashed connection identifiers help prevent duplicate submissions and abuse; cleanup is scheduled through WordPress cron. Our organisation must explain its purposes, lawful basis, recipients and retention arrangements in its privacy notice.</p>' );
        }
    }

    /**
     * Display and consume the next administrator notice with HTML escaping.
     *
     * @return void Outputs nothing for non-administrators or absent/malformed notices.
     */
    public function display_admin_notices(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $notice = get_transient( 'osm_admin_notice' );
        if ( is_array( $notice ) && isset( $notice['type'], $notice['message'] ) ) {
            $class = 'success' === $notice['type'] ? 'notice-success' : 'notice-error';
            printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
            delete_transient( 'osm_admin_notice' );
        }
    }
}
