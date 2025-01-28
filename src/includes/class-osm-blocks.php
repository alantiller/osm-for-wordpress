<?php

class OSM_Blocks {
    public function __construct() {
        // Hook into WordPress initialization to register blocks
        add_action( 'init', [ $this, 'register_blocks' ] );

        // Enqueue block editor assets
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    /**
     * Register OSM blocks using the block.json metadata.
     */
    public function register_blocks() {
        // Register Programme block
        register_block_type( OSM_PLUGIN_DIR . 'build/blocks/programme' );

        // Register Events block
        register_block_type( OSM_PLUGIN_DIR . 'build/blocks/events' );
    }

    /**
     * Enqueue editor assets for OSM blocks.
     */
    public function enqueue_editor_assets() {
        // Enqueue editor assets for the Programme block
        if ( file_exists( OSM_PLUGIN_DIR . 'build/programme-editor.asset.php' ) ) {
            $programme_asset_file = include OSM_PLUGIN_DIR . 'build/programme-editor.asset.php';

            wp_enqueue_script(
                'osm-programme-editor-scripts',
                OSM_ASSETS_URI . '/build/programme-editor.js',
                $programme_asset_file['dependencies'],
                $programme_asset_file['version'],
                true
            );

            wp_enqueue_style(
                'osm-programme-editor-styles',
                OSM_ASSETS_URI . '/build/programme-editor.css',
                [],
                $programme_asset_file['version']
            );
        }

        // Enqueue editor assets for the Events block
        if ( file_exists( OSM_PLUGIN_DIR . 'build/events-editor.asset.php' ) ) {
            $events_asset_file = include OSM_PLUGIN_DIR . 'build/events-editor.asset.php';

            wp_enqueue_script(
                'osm-events-editor-scripts',
                OSM_ASSETS_URI . '/build/events-editor.js',
                $events_asset_file['dependencies'],
                $events_asset_file['version'],
                true
            );

            wp_enqueue_style(
                'osm-events-editor-styles',
                OSM_ASSETS_URI . '/build/events-editor.css',
                [],
                $events_asset_file['version']
            );
        }
    }
}