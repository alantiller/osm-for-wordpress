<?php
/**
 * Plugin Name: OSM for WordPress
 * Description: A quick and easy way to display programmes and events from Online Scout Manager on your website, with support for multiple sections.
 * Version: 1.0.1
 * Author: Alan Tiller
 * Author URI: https://www.alantiller.com
 * License: MIT
 * Requires at least: 6.7
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'OSM_PLUGIN_FILE', __FILE__ );
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

    public static function instance() {
        if ( ! isset( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        new OSM_Admin();
        new OSM_Shortcodes();
        new OSM_Divi();
    }

    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style( 'osm-frontend-style', OSM_ASSETS_URI . '/css/frontend.css' );
        wp_enqueue_script( 'osm-frontend-script', OSM_ASSETS_URI . '/js/frontend.js', [ 'jquery' ], null, true );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style( 'osm-admin-style', OSM_ASSETS_URI . '/css/admin.css' );
        wp_enqueue_script( 'osm-admin-script', OSM_ASSETS_URI . '/js/admin.js', [ 'jquery' ], null, true );
    }
}

// Initialize plugin
OSM_For_WordPress::instance();