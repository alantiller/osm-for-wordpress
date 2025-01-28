<?php

class Divi_Programme_Module extends ET_Builder_Module {
    public $slug       = 'osm_programme_module';
    public $vb_support = 'on';

    public function init() {
        $this->name = esc_html__( 'OSM Programme', 'osm-for-wordpress' );
    }

    public function get_fields() {
        return [
            'sectionid' => [
                'label'           => esc_html__( 'Section ID', 'osm-for-wordpress' ),
                'type'            => 'text',
                'description'     => esc_html__( 'Enter the Section ID.', 'osm-for-wordpress' ),
                'toggle_slug'     => 'main_content',
            ],
            'futureonly' => [
                'label'           => esc_html__( 'Future Only', 'osm-for-wordpress' ),
                'type'            => 'yes_no_button',
                'options'         => [
                    'yes' => esc_html__( 'Yes', 'osm-for-wordpress' ),
                    'no'  => esc_html__( 'No', 'osm-for-wordpress' ),
                ],
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render( $attrs, $content = null, $render_slug ) {
        $sectionid = $this->props['sectionid'];
        $futureonly = $this->props['futureonly'] === 'yes';

        return do_shortcode( '[osm_programme sectionid="' . esc_attr( $sectionid ) . '" futureonly="' . esc_attr( $futureonly ) . '"]' );
    }
}