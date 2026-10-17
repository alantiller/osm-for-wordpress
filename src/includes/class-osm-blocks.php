<?php
declare(strict_types=1);

/** WordPress blocks share the same permission checks and output as shortcodes. */
class OSM_Blocks {
    /** @var object|null Shared renderer exposing the public render_* methods. */
    private static ?object $renderer = null;

    /**
     * Reuse a shared renderer and register block initialisation with WordPress.
     *
     * @param object|null $renderer Shared shortcode renderer; created when omitted.
     * @return void
     */
    public function __construct( ?object $renderer = null ) {
        self::$renderer = $renderer ?: new OSM_Shortcodes();
        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    /**
     * Register compiled editor assets and the four dynamic block metadata files.
     *
     * @return void
     */
    public function register_blocks(): void {
        $asset_path = OSM_PLUGIN_DIR . 'build/osm-blocks-editor.asset.php';
        if ( file_exists( $asset_path ) ) {
            $asset = include $asset_path;
            wp_register_script(
                'osm-blocks-editor',
                OSM_PLUGIN_URI . 'build/osm-blocks-editor.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );
            wp_register_style(
                'osm-blocks-editor',
                OSM_PLUGIN_URI . 'build/osm-blocks-editor.css',
                [],
                $asset['version']
            );
            wp_register_style(
                'osm-frontend-style',
                OSM_ASSETS_URI . '/css/frontend.css',
                [],
                (string) filemtime( OSM_PLUGIN_DIR . 'assets/css/frontend.css' )
            );
            wp_set_script_translations( 'osm-blocks-editor', 'online-scout-manager' );
        }

        foreach ( [ 'programme', 'events', 'patrols', 'waiting-list' ] as $block ) {
            register_block_type( OSM_PLUGIN_DIR . 'build/blocks/' . $block );
        }
    }

    /**
     * Render a known dynamic block with the shared section and privacy checks.
     *
     * Saved single-section aliases remain supported by the shared renderer. An
     * empty waiting-list selection uses the configured waiting-list section.
     *
     * @param string $type Renderer suffix: programme, events, patrols or waiting_list.
     * @param array<string, mixed> $attributes Saved block attributes.
     * @return string Rendered HTML, or an empty string for unknown block kinds.
     */
    public static function render( string $type, array $attributes ): string {
        if ( ! in_array( $type, [ 'programme', 'events', 'patrols', 'waiting_list' ], true ) ) {
            return '';
        }
        if ( ! self::$renderer ) {
            self::$renderer = new OSM_Shortcodes();
        }

        // Editor and authenticated REST previews must not submit application forms.
        $editor_request = ( function_exists( 'is_admin' ) && is_admin() )
            || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
        if ( $editor_request && function_exists( 'current_user_can' )
            && ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
            $attributes['_osm_preview'] = true;
        }

        $method = 'render_' . $type;
        return self::$renderer->$method( $attributes );
    }
}
