<?php
/**
 * Plugin Name: Online Scout Manager
 * Description: Display Online Scout Manager programmes, events, patrol names and waiting lists, using shortcodes and Gutenberg blocks with multiple-section support.
 * Version: 1.1.1
 * Author: Alan Tiller
 * Author URI: https://www.alantiller.com
 * Text Domain: online-scout-manager
 * License: MIT
 * Requires at least: 6.7
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'OSM_PLUGIN_FILE', __FILE__ );
define( 'OSM_PLUGIN_VERSION', '1.1.1' );
define( 'OSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OSM_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'OSM_TEMPLATES_DIR', OSM_PLUGIN_DIR . 'templates' );
define( 'OSM_ASSETS_URI', OSM_PLUGIN_URI . 'assets' );

// Autoload classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'OSM_') === 0) {
        $filename = OSM_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        if (file_exists($filename)) {
            require_once $filename;
        }
    }
});

// Initialize plugin
class OSM_For_WordPress {
    private static $_instance;

    /** Return the single plugin instance. @return self Shared plugin coordinator. */
    public static function instance() {
        if ( ! isset( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /** Load integrations and register asset hooks once. @return void */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /** Share the public renderer and register Gutenberg blocks and waiting-list handlers. @return void */
    private function load_dependencies(): void {
        new OSM_Admin();
        $shortcodes = new OSM_Shortcodes();
        new OSM_Blocks( $shortcodes );
        new OSM_Waiting_List();
    }

    /** Register public and administrator asset callbacks. @return void */
    private function init_hooks(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /** Load shared public table/form styles. @return void */
    public function enqueue_frontend_assets(): void {
        wp_enqueue_style( 'osm-frontend-style', OSM_ASSETS_URI . '/css/frontend.css', [], (string) filemtime( OSM_PLUGIN_DIR . 'assets/css/frontend.css' ) );
    }

    /**
     * Load native settings enhancements only on this plugin's administrator page.
     * @param string $hook_suffix WordPress admin page hook name.
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix = '' ): void {
        if ( 'settings_page_online-scout-manager' !== $hook_suffix ) {
            return;
        }
        wp_enqueue_style( 'osm-admin-style', OSM_ASSETS_URI . '/css/admin.css', [], (string) filemtime( OSM_PLUGIN_DIR . 'assets/css/admin.css' ) );
        wp_enqueue_style( 'osm-frontend-style', OSM_ASSETS_URI . '/css/frontend.css', [], (string) filemtime( OSM_PLUGIN_DIR . 'assets/css/frontend.css' ) );
        wp_enqueue_script( 'osm-admin-script', OSM_ASSETS_URI . '/js/admin.js', [], (string) filemtime( OSM_PLUGIN_DIR . 'assets/js/admin.js' ), true );
    }
}

// Initialize plugin
OSM_For_WordPress::instance();
