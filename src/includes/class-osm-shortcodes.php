<?php

declare(strict_types=1);

/** Shared public rendering for shortcodes and all supported page builders. */
class OSM_Shortcodes {
    /**
     * Register the four public shortcodes on WordPress's shared renderer.
     *
     * @return void
     */
    public function __construct() {
        add_shortcode( 'osm_programme', [ $this, 'render_programme' ] );
        add_shortcode( 'osm_events', [ $this, 'render_events' ] );
        add_shortcode( 'osm_patrols', [ $this, 'render_patrols' ] );
        add_shortcode( 'osm_waiting_list', [ $this, 'render_waiting_list' ] );
    }

    /**
     * Parse section IDs, preserving the legacy attribute and selection order.
     *
     * @param mixed $atts Shortcode or builder attributes; sectionids takes precedence over sectionid.
     * @return list<int> Unique positive section IDs, limited to 50 input entries.
     * @throws InvalidArgumentException If any ID is missing, malformed or outside PHP's integer range.
     */
    public static function section_ids( mixed $atts ): array {
        $atts = is_array( $atts ) ? $atts : [];
        $value = $atts['sectionids'] ?? '';
        if ( '' === $value || [] === $value ) {
            $value = $atts['sectionid'] ?? '';
        }
        if ( ! is_array( $value ) && ! is_scalar( $value ) ) {
            throw new InvalidArgumentException( 'Please select valid section IDs.' );
        }
        $values = is_array( $value ) ? $value : explode( ',', (string) $value );
        if ( empty( $values ) || count( $values ) > 50 ) {
            throw new InvalidArgumentException( 'Please select between 1 and 50 sections.' );
        }
        $ids = [];
        foreach ( $values as $id ) {
            if ( ! is_scalar( $id ) || ! preg_match( '/^[1-9][0-9]*$/D', trim( (string) $id ) ) ) {
                throw new InvalidArgumentException( 'The section IDs provided are missing or invalid.' );
            }
            $id = trim( (string) $id );
            $max = (string) PHP_INT_MAX;
            if ( strlen( $id ) > strlen( $max ) || ( strlen( $id ) === strlen( $max ) && strcmp( $id, $max ) > 0 ) ) {
                throw new InvalidArgumentException( 'The section IDs provided are invalid.' );
            }
            $ids[] = (int) $id;
        }
        return array_values( array_unique( $ids ) );
    }

    /**
     * Resolve a programme/event presentation without changing its data or permissions.
     *
     * Explicit shortcode/block attributes win. Theme functions can select a default
     * through osm_display_layout for renders with no explicit choice.
     * @param array<string,mixed> $atts Render attributes; layout accepts table, grid or vertical; tiles is a legacy alias for grid.
     * @param string $type Programme or events, supplied by the renderer.
     * @return string Allowlisted template layout.
     * @throws InvalidArgumentException If the requested layout is unsupported.
     */
    private static function display_layout( array $atts, string $type ): string {
        $layout = $atts['layout'] ?? '';
        if ( '' === $layout ) { $layout = apply_filters( 'osm_display_layout', 'table', $type, $atts ); }
        if ( ! in_array( $layout, [ 'table', 'grid', 'vertical', 'tiles' ], true ) ) { throw new InvalidArgumentException( 'Choose table, grid tiles or vertical tiles.' ); }
        return 'tiles' === $layout ? 'grid' : $layout;
    }

    /**
     * Render current-term programme displays, containing failures as public messages.
     *
     * @param mixed $atts Section attributes, optional futureonly/combine flags and layout (table, grid or vertical).
     * @return string Escaped programme HTML or a public validation/unavailability message.
     */
    public function render_programme( mixed $atts ): string {
        return $this->render_sections( 'programme', $atts );
    }

    /**
     * Render current-term event displays for one or more enabled sections.
     *
     * @param mixed $atts Section attributes, optional futureonly/combine flags and layout (table, grid or vertical).
     * @return string Escaped event HTML; API failures never expose their response bodies.
     */
    public function render_events( mixed $atts ): string {
        return $this->render_sections( 'events', $atts );
    }

    /**
     * Render patrol names only where each section's publication setting allows it.
     *
     * @param mixed $atts Section selections from a shortcode or page builder.
     * @return string Escaped patrol lists or public availability messages, never member records.
     */
    public function render_patrols( mixed $atts ): string {
        // Publication/name changes must not leave a cached roster on a public page.
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        if ( ! headers_sent() ) { nocache_headers(); }
        return $this->render_sections( 'patrols', $atts );
    }

    /**
     * Render the configured waiting list, retaining explicit legacy ID overrides.
     *
     * The waiting-list service validates its own saved destination, independently
     * of programme/event publication. Empty attributes use the administrator's dropdown.
     *
     * @param mixed $atts Optional sectionid/sectionids overrides; omit them to use settings.
     * @return string Waiting-list HTML or a safe public error message.
     */
    public function render_waiting_list( mixed $atts ): string {
        try {
            $attributes = is_array( $atts ) ? $atts : [];
            $selection = $attributes['sectionids'] ?? '';
            if ( '' === $selection || [] === $selection ) {
                $selection = $attributes['sectionid'] ?? '';
            }
            $section_ids = ( '' === $selection || [] === $selection ) ? [] : self::section_ids( $attributes );
            return OSM_Waiting_List::render( $section_ids, ! empty( $attributes['_osm_preview'] ) );
        } catch ( InvalidArgumentException $error ) {
            return self::error( $error->getMessage() );
        } catch ( Throwable $error ) {
            return self::error( 'The waiting list is currently unavailable. Please contact the group.' );
        }
    }

    /**
     * Fetch and render each public section independently, preserving successful feeds.
     *
     * @param string $type Internal renderer kind: programme, events or patrols.
     * @param mixed $atts Section selections and optional futureonly/layout/combine settings.
     * @return string Section HTML with headings, or one grouped table when combine is true; failures are contained.
     */
    private function render_sections( string $type, mixed $atts ): string {
        $atts = is_array( $atts ) ? $atts : [];
        try {
            $ids = self::section_ids( $atts );
            $layout = 'patrols' === $type ? 'tiles' : self::display_layout( $atts, $type );
        } catch ( Exception $error ) {
            return self::error( $error->getMessage() );
        }
        $enabled = (array) get_option( 'patrols' === $type ? 'osm_public_patrol_sections' : 'osm_enabled_sections', [] );
        if ( ! array_intersect_key( array_filter( $enabled ), array_flip( $ids ) ) ) {
            return self::error( 'The selected section is not available for display.' );
        }
        try {
            $sections = OSM_API::get_sections();
        } catch ( Throwable $error ) {
            return self::error( 'This information is currently unavailable. Please check back later.' );
        }
        $futureonly = filter_var( $atts['futureonly'] ?? false, FILTER_VALIDATE_BOOLEAN );
        $combine = 'patrols' !== $type && 'table' === $layout && filter_var( $atts['combine'] ?? false, FILTER_VALIDATE_BOOLEAN );
        $section_groups = [];
        $date_format = OSM_Options::get_date_format() ?: 'd M Y';
        $time_format = OSM_Options::get_time_format() ?: 'H:i';
        $output = '';
        foreach ( $ids as $sectionid ) {
            $section = ! empty( $enabled[ $sectionid ] ) ? ( $sections[ $sectionid ] ?? [] ) : [];
            $heading = implode( ': ', array_filter( [ self::text( $section['groupname'] ?? '' ), self::text( $section['sectionname'] ?? '' ) ] ) ) ?: 'Section unavailable';
            $html = '';
            $items = [];
            $message = '';
            try {
                if ( empty( $enabled[ $sectionid ] ) || ! isset( $sections[ $sectionid ] ) ) {
                    throw new RuntimeException();
                }
                if ( 'patrols' === $type ) {
                    $patrols = OSM_API::get_patrols( $sectionid );
                    $html = empty( $patrols ) ? self::error( 'No patrols are currently available.' ) : self::template( 'patrols', compact( 'patrols' ) );
                } else {
                    $termid = OSM_API::get_current_term( $sectionid );
                    $response = 'programme' === $type ? OSM_API::get_programme( $sectionid, $termid ) : OSM_API::get_events( $sectionid, $termid );
                    $items = isset( $response['items'] ) && is_array( $response['items'] ) ? $response['items'] : [];
                    $items = array_filter( $items, 'is_array' );
                    if ( $futureonly ) {
                        $today = new DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() );
                        $items = array_filter( $items, function ( $item ) use ( $type, $today ) {
                            $fields = 'programme' === $type ? [ 'meetingdate' ] : [ 'startdate_g', 'startdate', 'eventdate' ];
                            foreach ( $fields as $field ) {
                                $date = self::parse_date( $item[ $field ] ?? '' );
                                if ( $date ) {
                                    return $date >= $today;
                                }
                            }
                            return false;
                        } );
                    }
                    if ( empty( $items ) ) {
                        $message = $futureonly ? 'No upcoming activities are currently available.' : 'No activities are currently available.';
                    } elseif ( ! $combine ) {
                        $programme = $events = $items;
                        $template = 'table' !== $layout ? $type . '-tiles' : $type;
                        $html = self::template( $template, compact( 'programme', 'events', 'date_format', 'time_format', 'layout' ) );
                    }
                }
            } catch ( OSM_Current_Term_Exception $error ) {
                $message = 'No current OSM term is available for this section.';
            } catch ( Throwable $error ) {
                // Do not publish API error bodies, bearer tokens or member information.
                $message = 'This information is currently unavailable. Please check back later.';
            }
            if ( $combine ) {
                // Preserve section order and safe errors inside the same table; never merge permissions.
                $section_groups[] = [ 'heading' => $heading, 'items' => $items, 'message' => $message ];
                continue;
            }
            if ( '' !== $message ) { $html = self::error( $message ); }
            if ( count( $ids ) > 1 ) {
                $output .= '<section class="osm-section"><h3 class="osm-section-title">' . esc_html( $heading ) . '</h3>' . $html . '</section>';
            } else {
                $output .= $html;
            }
        }
        return $combine ? self::template( $type, compact( 'section_groups', 'date_format', 'time_format' ) ) : $output;
    }

    /**
     * Parse OSM ISO/UK dates at midnight in the WordPress timezone.
     *
     * @param mixed $value A Y-m-d or d/m/Y string; other values are rejected.
     * @return DateTimeImmutable|false Parsed date, or false for empty, malformed or impossible dates.
     */
    public static function parse_date( mixed $value ): DateTimeImmutable|false {
        if ( ! is_string( $value ) || '' === $value ) {
            return false;
        }
        foreach ( [ '!Y-m-d', '!d/m/Y' ] as $format ) {
            $date = DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
            $errors = DateTimeImmutable::getLastErrors();
            if ( $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
                return $date;
            }
        }
        return false;
    }

    /**
     * Format an OSM date with WordPress locale and timezone settings.
     *
     * @param mixed $value Date accepted by parse_date().
     * @param string $format PHP date format selected by the administrator.
     * @return string Formatted date, or an empty string for an invalid date.
     */
    public static function format_date( mixed $value, string $format ): string {
        $date = self::parse_date( $value );
        return $date ? wp_date( $format, $date->getTimestamp(), wp_timezone() ) : '';
    }

    /**
     * Format a valid OSM local 24-hour time without accepting arbitrary relative dates.
     *
     * @param mixed $value Time in H:i or H:i:s format.
     * @param string $format PHP time format selected by the administrator.
     * @return string Formatted time, or an empty string when the input is invalid.
     */
    public static function format_time( mixed $value, string $format ): string {
        if ( ! is_string( $value ) || ! preg_match( '/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D', $value ) ) {
            return '';
        }
        $date = DateTimeImmutable::createFromFormat( '!H:i' . ( 2 === substr_count( $value, ':' ) ? ':s' : '' ), $value, wp_timezone() );
        return $date ? wp_date( $format, $date->getTimestamp(), wp_timezone() ) : '';
    }

    /**
     * Convert scalar display values while rejecting stale or malformed structured fields.
     *
     * @param mixed $value An untrusted display value; callers must HTML-escape the result.
     * @return string Scalar text, or an empty string for null, arrays and objects.
     */
    public static function text( mixed $value ): string {
        return is_scalar( $value ) ? (string) $value : '';
    }

    /**
     * Render an internal template and clean up its output buffer even when it throws.
     *
     * @param string $name A trusted template basename supplied only by this class.
     * @param array<string, mixed> $variables Local variables available to the template.
     * @return string Rendered HTML; templates are responsible for output escaping.
     * @throws Throwable If a template cannot render; public callers contain the failure.
     */
    private static function template( string $name, array $variables ): string {
        extract( $variables, EXTR_SKIP );
        ob_start();
        try {
            include OSM_TEMPLATES_DIR . '/shortcode/' . $name . '.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Render a public message using the escaped error template.
     *
     * @param string $message Safe explanatory text without raw API data or credentials.
     * @return string Escaped message HTML.
     */
    private static function error( string $message ): string {
        return self::template( 'error', compact( 'message' ) );
    }
}
