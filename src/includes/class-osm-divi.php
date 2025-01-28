<?php

class OSM_Divi {
    public function __construct() {
        add_action( 'et_builder_ready', [ $this, 'register_divi_modules' ] );
    }

    /**
     * Register custom Divi modules.
     */
    public function register_divi_modules() {
        if ( ! class_exists( 'ET_Builder_Module' ) ) {
            return; // Divi is not available
        }

        require_once OSM_PLUGIN_DIR . 'includes/modules/class-divi-programme.php';
        require_once OSM_PLUGIN_DIR . 'includes/modules/class-divi-events.php';

        // Register Divi Modules
        new Divi_Programme_Module();
        new Divi_Events_Module();
    }
}
