<?php

class OSM_Shortcodes {
    public function __construct() {
        add_shortcode( 'osm_programme', [ $this, 'render_programme' ] );
        add_shortcode( 'osm_events', [ $this, 'render_events' ] );
    }

    public function render_programme( $atts ) {
        $atts = shortcode_atts( [ 'sectionid' => '', 'futureonly' => false ], $atts );
        $futureonly = filter_var( $atts['futureonly'], FILTER_VALIDATE_BOOLEAN );

        // Check if the section ID is numeric
        if ( ! is_numeric( $atts['sectionid'] ) ) {
            $message = 'The section ID provided is missing or invalid.';
        }

        // Check the section exists
        $sections = OSM_API::get_sections();
        if ( ! isset( $sections[ $atts['sectionid'] ] ) ) {
            $message = 'The section ID provided does not exist.';
        }

        // Check if the section is enabled
        $enabled_sections = get_option( 'osm_enabled_sections', [] );
        if ( ! isset( $enabled_sections[ $atts['sectionid'] ] ) ) {
            $message = 'Sorry, we\'re unable to display the programme for this section, please check back later.';
        }

        if ( ! isset( $message ) ) {
            $termid = OSM_API::get_current_term( $atts['sectionid'] );
            $programme = OSM_API::get_programme( $atts['sectionid'], $termid );
            
            if ( $futureonly ) {
                $programme = array_filter($programme['items'], function ($item) {
                    if (!isset($item['meetingdate'])) {
                        return false;
                    }
                    return strtotime($item['meetingdate']) >= strtotime(date('Y-m-d'));
                });
                $no_results = "Sorry, we couldn't find upcoming meetings, please check back later.";
            } else {
                $programme = $programme['items'];
                $no_results = "Sorry, no programme is currently available, please check back later.";
            }
            
            // Check if the programme is empty
            if ( empty( $programme ) ) {
                $message = $no_results;
            }
        }
        
        // Return an error message if there is one
        if ( isset( $message ) ) {
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/error.php';
            return ob_get_clean();
        } else {
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/programme.php';
            return ob_get_clean();
        }
    }

    public function render_events( $atts ) {
        $atts = shortcode_atts( [ 'sectionid' => '', 'futureonly' => false ], $atts );
        $futureonly = filter_var( $atts['futureonly'], FILTER_VALIDATE_BOOLEAN );

        // Check if the section ID is numeric
        if ( ! is_numeric( $atts['sectionid'] ) ) {
            $message = 'The section ID provided is missing or invalid.';
        }

        // Check the section exists
        $sections = OSM_API::get_sections();
        if ( ! isset( $sections[ $atts['sectionid'] ] ) ) {
            $message = 'The section ID provided does not exist.';
        }

        // Check if the section is enabled
        $enabled_sections = get_option( 'osm_enabled_sections', [] );
        if ( ! isset( $enabled_sections[ $atts['sectionid'] ] ) ) {
            $message = 'Sorry, we\'re unable to display the events for this section, please check back later.';
        }

        if ( ! isset( $message ) ) {
            $termid = OSM_API::get_current_term( $atts['sectionid'] );
            $events = OSM_API::get_events( $atts['sectionid'], $termid );

            if ( $futureonly ) {
                $events = array_filter( $events['items'], function ( $item ) {
                    return strtotime( $item['eventdate'] ) >= time();
                } );
                $no_results = "Sorry, we couldn't find upcoming events, please check back later.";
            } else {
                $events = $events['items'];
                $no_results = "Sorry, no events are currently available, please check back later.";
            }

            // Check if the events are empty
            if ( empty( $events ) ) {
                $message = $no_results;
            }
        }
        
        // Return an error message if there is one
        if ( isset( $message ) ) {
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/error.php';
            return ob_get_clean();
        } else {
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/events.php';
            return ob_get_clean();
        }
    }
}