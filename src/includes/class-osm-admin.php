<?php

class OSM_Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'admin_post_osm_save_auth', [ $this, 'save_auth' ] );
        add_action( 'admin_post_osm_save_sections', [ $this, 'save_sections' ] );
        add_action( 'admin_post_osm_purge_cache', [ $this, 'purge_cache' ] );
        add_action( 'admin_post_osm_reset_configuration', [ $this, 'reset_configuration' ] );
        add_action( 'admin_post_osm_save_advanced_options', [ $this, 'save_advanced_options' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
    }

    public function add_admin_page() {
        add_menu_page( 'OSM Settings', 'OSM Settings', 'manage_options', 'osm-for-wordpress', [ $this, 'render_admin_page' ], 'dashicons-admin-tools' );
    }

    public function render_admin_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        $client_id = get_option( 'osm_client_id' );
        $client_secret = get_option( 'osm_client_secret' );
        $enabled_sections = get_option( 'osm_enabled_sections', [] );
        $advanced_options = [
            'osm_date_format' => OSM_Options::get_date_format() ?? '',
            'osm_time_format' => OSM_Options::get_time_format() ?? '',
        ];

        include OSM_TEMPLATES_DIR . '/admin/settings.php';
    }

    public function save_auth() {
        check_admin_referer( 'osm_auth_nonce' );

        $client_id = sanitize_text_field( $_POST['osm_client_id'] );
        $client_secret = sanitize_text_field( $_POST['osm_client_secret'] );

        update_option( 'osm_client_id', $client_id );
        update_option( 'osm_client_secret', $client_secret );

        try {
            OSM_API::authorize(true);
            set_transient( 'osm_admin_notice', [ 'type' => 'success', 'message' => 'Authentication saved and verified successfully.' ], 10 );
        } catch ( Exception $e ) {
            set_transient( 'osm_admin_notice', [ 'type' => 'error', 'message' => 'Failed to authenticate: ' . $e->getMessage() ], 10 );
        }

        wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress' ) );
        exit;
    }

    public function save_sections() {
        check_admin_referer( 'osm_sections_nonce' );

        $enabled_sections = array_map( 'sanitize_text_field', $_POST['osm_enabled_sections'] ?? [] );

        // Cache current term for each section
        foreach ( $enabled_sections as $sectionid => $value ) {
            OSM_API::get_current_term( $sectionid );
        }

        update_option( 'osm_enabled_sections', $enabled_sections );

        set_transient( 'osm_admin_notice', [ 'type' => 'success', 'message' => 'Sections updated successfully.' ], 10 );

        wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress' ) );
        exit;
    }

    public function save_advanced_options() {
        check_admin_referer( 'osm_advanced_options_nonce' );

        $date_format = sanitize_text_field( $_POST['osm_date_format'] );
        $time_format = sanitize_text_field( $_POST['osm_time_format'] );

        try {
            if ( ! empty( $date_format ) ) { OSM_Options::set_date_format( $date_format ); }
            if ( ! empty( $time_format ) ) { OSM_Options::set_time_format( $time_format ); }

            set_transient( 'osm_admin_notice', [ 'type' => 'success', 'message' => 'Advanced options saved successfully.' ], 10 );
    
            wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress&tab=advanced_options' ) );
            exit;
        } catch ( Exception $e ) {
            set_transient( 'osm_admin_notice', [ 'type' => 'error', 'message' => 'Failed to save advanced options: ' . $e->getMessage() ], 10 );
            wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress&tab=advanced_options' ) );
            exit;
        }
    }

    public function purge_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'osm_cached_%'" );

        set_transient( 'osm_admin_notice', [ 'type' => 'success', 'message' => 'Cache purged successfully.' ], 10 );

        wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress' ) );
        exit;
    }

    public function reset_configuration() {
        check_admin_referer( 'osm_reset_nonce' );

        delete_option( 'osm_client_id' );
        delete_option( 'osm_client_secret' );
        delete_option( 'osm_enabled_sections' );

        // Delete cached current term options
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'osm_current_term_%'" );

        set_transient( 'osm_admin_notice', [ 'type' => 'success', 'message' => 'Configuration reset successfully.' ], 10 );

        wp_redirect( admin_url( 'admin.php?page=osm-for-wordpress' ) );
        exit;
    }

    public function display_admin_notices() {
        if ( $notice = get_transient( 'osm_admin_notice' ) ) {
            $class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
            printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
            delete_transient( 'osm_admin_notice' );
        }
    }
}