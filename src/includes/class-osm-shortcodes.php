<?php

class OSM_Shortcodes {
    public function __construct() {
        add_shortcode( 'osm_programme', [ $this, 'render_programme' ] );
        add_shortcode( 'osm_events', [ $this, 'render_events' ] );
    }

    public function render_programme( $atts ) {
        try {
            // Get the attributes
            $atts = shortcode_atts( [ 'sectionid' => '', 'futureonly' => false ], $atts );
            $futureonly = filter_var( $atts['futureonly'], FILTER_VALIDATE_BOOLEAN );

            // Check if the section ID is numeric
            if ( ! is_numeric( $atts['sectionid'] ) ) {
                throw new Exception( 'The section ID provided is missing or invalid.' );
            }

            // Check the section exists
            $sections = OSM_API::get_sections();
            if ( ! isset( $sections[ $atts['sectionid'] ] ) ) {
                throw new Exception( 'The section ID provided does not exist.' );
            }

            // Check if the section is enabled
            $enabled_sections = get_option( 'osm_enabled_sections', [] );
            if ( ! isset( $enabled_sections[ $atts['sectionid'] ] ) ) {
                throw new Exception( 'Sorry, we\'re unable to display the programme for this section, please check back later.' );
            }

            // Get the current term
            $termid = OSM_API::get_current_term( $atts['sectionid'] );
            $programme = OSM_API::get_programme( $atts['sectionid'], $termid )['items'];

            // Filter the programme if future only is set
            if ( $futureonly ) {
                $programme = array_filter( $programme, function ( $item ) {
                    // Check the meeting date is a valid date that can be converted to a timestamp and if so filter
                    if ( isset( $item['meetingdate'] ) && strtotime( $item['meetingdate'] ) ) {
                        return strtotime( $item['meetingdate'] ) >= strtotime( date( 'Y-m-d' ) );
                    }
                } );
            }

            // Check if the programme is empty
            if ( empty( $programme ) ) {
                throw new Exception( $futureonly ? 'Sorry, we couldn\'t find upcoming meetings, please check back later.' : 'Sorry, no programme is currently available, please check back later.' );
            }

            // Render the programme
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/programme.php';
            return ob_get_clean();
        } catch (Exception $error) {
            // Set the error message
            $message = $error->getMessage();

            // Render the error message
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/error.php';
            return ob_get_clean();
        }
    }

    public function render_events( $atts ) {
        try {
            // Get the attributes
            $atts = shortcode_atts( [ 'sectionid' => '', 'futureonly' => false ], $atts );
            $futureonly = filter_var( $atts['futureonly'], FILTER_VALIDATE_BOOLEAN );

            // Check if the section ID is numeric
            if ( ! is_numeric( $atts['sectionid'] ) ) {
                throw new Exception( 'The section ID provided is missing or invalid.' );
            }

            // Check the section exists
            $sections = OSM_API::get_sections();
            if ( ! isset( $sections[ $atts['sectionid'] ] ) ) {
                throw new Exception( 'The section ID provided does not exist.' );
            }

            // Check if the section is enabled
            $enabled_sections = get_option( 'osm_enabled_sections', [] );
            if ( ! isset( $enabled_sections[ $atts['sectionid'] ] ) ) {
                throw new Exception( 'Sorry, we\'re unable to display the events for this section, please check back later.' );
            }

            // Get the current term
            $termid = OSM_API::get_current_term( $atts['sectionid'] );
            $events = OSM_API::get_events( $atts['sectionid'], $termid )['items'];

            // Filter the events if future only is set
            if ( $futureonly ) {
                $events = array_filter( $events, function ( $item ) {
                    // Check the event date is a valid date that can be converted to a timestamp and if so filter
                    if ( isset( $item['eventdate'] ) && strtotime( $item['eventdate'] ) ) {
                        return strtotime( $item['eventdate'] ) >= strtotime( date( 'Y-m-d' ) );
                    } elseif ( isset( $item['startdate_g'] ) && strtotime( $item['startdate_g'] ) ) {
                        return strtotime( $item['startdate_g'] ) >= strtotime( date( 'Y-m-d' ) );
                    }
                } );
            }

            // Check if the events are empty
            if ( empty( $events ) ) {
                throw new Exception( $futureonly ? 'Sorry, we couldn\'t find upcoming events, please check back later.' : 'Sorry, no events are currently available, please check back later.' );
            }

            // Render the events
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/events.php';
            return ob_get_clean();
        } catch (Exception $error) {
            // Set the error message
            $message = $error->getMessage();

            // Render the error message
            ob_start();
            include OSM_TEMPLATES_DIR . '/shortcode/error.php';
            return ob_get_clean();
        }
    }
}