<?php

class OSM_Options {
    /**
     * Save the independent first/last name display policy for public patrol rosters.
     * @param string $firstname One of hidden, full or initial.
     * @param string $lastname One of hidden, full or initial.
     * @return void
     * @throws InvalidArgumentException If either display mode is unsupported.
     */
    public static function set_name_display( string $firstname, string $lastname ): void {
        foreach ( [ $firstname, $lastname ] as $mode ) {
            if ( ! in_array( $mode, [ 'hidden', 'full', 'initial' ], true ) ) {
                throw new InvalidArgumentException( 'Choose full names, initials or hidden for each name field.' );
            }
        }
        self::set( 'firstname_display', $firstname );
        self::set( 'lastname_display', $lastname );
    }

    /**
     * Read the public name policy, defaulting to no member names.
     * @param int $section_id Section ID, or zero for the legacy global policy.
     * @return array{firstname:string,lastname:string} Validated display modes.
     */
    public static function get_name_display( int $section_id = 0 ): array {
        $sections = (array) get_option( 'osm_section_points_settings', [] );
        $section = is_array( $sections[ $section_id ] ?? null ) ? $sections[ $section_id ] : [];
        $policy = [];
        foreach ( [ 'firstname', 'lastname' ] as $field ) {
            $mode = $section[ $field ] ?? self::get( $field . '_display', 'hidden' );
            $policy[ $field ] = in_array( $mode, [ 'hidden', 'full', 'initial' ], true ) ? $mode : 'hidden';
        }
        return $policy;
    }

    /**
     * Apply the saved name limits before member data leaves the API boundary.
     * @param mixed $firstname Untrusted OSM first name.
     * @param mixed $lastname Untrusted OSM last name.
     * @param int $section_id Section whose name limits apply; zero uses the legacy global policy.
     * @return string Approved display text, or empty when both fields are hidden.
     */
    public static function format_member_name( mixed $firstname, mixed $lastname, int $section_id = 0 ): string {
        $policy = self::get_name_display( $section_id );
        $parts = [];
        foreach ( [ 'firstname' => $firstname, 'lastname' => $lastname ] as $field => $value ) {
            if ( 'hidden' === $policy[ $field ] || ! is_string( $value ) ) { continue; }
            $value = trim( sanitize_text_field( $value ) );
            if ( '' === $value ) { continue; }
            if ( 'initial' === $policy[ $field ] ) {
                $value = preg_match( '/^./us', $value, $letter ) ? $letter[0] . '.' : '';
            }
            if ( '' !== $value ) { $parts[] = $value; }
        }
        return implode( ' ', $parts );
    }

    /**
     * Read stored exclusion IDs without loading or persisting member names.
     * @param int $section_id Section owning the exclusions.
     * @return list<int> Unique positive OSM member IDs; absent configuration returns an empty list.
     */
    public static function get_excluded_members( int $section_id ): array {
        $sections = (array) get_option( 'osm_section_points_settings', [] );
        $section = is_array( $sections[ $section_id ] ?? null ) ? $sections[ $section_id ] : [];
        $ids = [];
        foreach ( (array) ( $section['excluded_members'] ?? [] ) as $id ) {
            if ( ( is_int( $id ) || is_string( $id ) ) && false !== filter_var( $id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) { $ids[] = (int) $id; }
        }
        return array_values( array_unique( $ids ) );
    }

    /**
     * Save one section's independent name limits and exclusions after full validation.
     *
     * The administrator handler must first check capability, nonce and section access.
     * Stores only mode strings and member IDs, never a copied member roster.
     * @param int $section_id Positive accessible section ID.
     * @param string $firstname First-name mode: hidden, full or initial.
     * @param string $lastname Last-name mode: hidden, full or initial.
     * @param array<mixed> $excluded_members Positive member IDs to omit before public formatting.
     * @return void Preserves all other sections' configuration.
     * @throws InvalidArgumentException If a section, name mode or member ID is invalid.
     */
    public static function set_section_points( int $section_id, string $firstname, string $lastname, array $excluded_members ): void {
        if ( $section_id < 1 || ! in_array( $firstname, [ 'hidden', 'full', 'initial' ], true ) || ! in_array( $lastname, [ 'hidden', 'full', 'initial' ], true ) ) {
            throw new InvalidArgumentException( 'Choose a section and a valid display mode for each name.' );
        }
        $ids = [];
        foreach ( $excluded_members as $id ) {
            if ( ( ! is_int( $id ) && ! is_string( $id ) ) || false === filter_var( $id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) {
                throw new InvalidArgumentException( 'Excluded members must be positive OSM member IDs.' );
            }
            $ids[] = (int) $id;
        }
        $sections = (array) get_option( 'osm_section_points_settings', [] );
        $sections[ $section_id ] = [ 'firstname' => $firstname, 'lastname' => $lastname, 'excluded_members' => array_values( array_unique( $ids ) ) ];
        update_option( 'osm_section_points_settings', $sections, false );
    }

    /**
     * Set date format.
     * 
     * @param string $format Date format.
     * @return void
     */
    public static function set_date_format( $format ) {
        // Confirm the date format is a valid PHP date format.
        if ( ! empty( $format ) && ! self::validate_datetime_format( $format ) ) {
            throw new Exception( 'Invalid date format.' );
        }

        self::set( 'date_format', $format );
    }

    /**
     * Get date format.
     * 
     * @return string Date format.
     */
    public static function get_date_format() {
        // Get the date format option.
        $date_format = self::get( 'date_format', null );

        // Confirm the date format is a valid PHP date format.
        if ( ! empty( $date_format ) && ! self::validate_datetime_format( $date_format ) ) {
            // If the date format is invalid, use the default.
            $date_format = 'd M Y';
        }

        return $date_format;
    }

    /**
     * Set time format.
     * 
     * @param string $format Time format.
     * @return void
     */
    public static function set_time_format( $format ) {
        // Confirm the time format is a valid PHP date format.
        if ( ! empty( $format ) && ! self::validate_datetime_format( $format ) ) {
            throw new Exception( 'Invalid time format.' );
        }

        self::set( 'time_format', $format );
    }

    /**
     * Get time format.
     * 
     * @return string Time format.
     */
    public static function get_time_format() {
        // Get the time format option.
        $time_format = self::get( 'time_format', null );

        // Confirm the time format is a valid PHP date format.
        if ( ! empty( $time_format ) && ! self::validate_datetime_format( $time_format ) ) {
            // If the time format is invalid, use the default.
            $time_format = 'H:i';
        }

        return $time_format;
    }

    /**
     * Set an option value.
     * 
     * @param string $key Option key.
     * @param mixed $value Option value.
     * @return void
     */
    private static function set( $key, $value ) {
        // Check if the value is null or empty and delete the option if so.
        if ( null === $value || '' === $value ) {
            self::delete( $key );
            return;
        }

        // Update the option value.
        update_option( "osm_option_{$key}", $value );
    }

    /**
     * Get an option value.
     * 
     * @param string $key Option key.
     * @param mixed $default Default value.
     * @return mixed Option value or default.
     */
    private static function get( $key, $default = false ) {
        return get_option( "osm_option_{$key}", $default );
    }

    /**
     * Delete an option value.
     * 
     * @param string $key Option key.
     * @return void
     */
    private static function delete( $key ) {
        delete_option( "osm_option_{$key}" );
    }

    /**
     * Validate a datetime format string.
     * 
     * @param string $format Date format string.
     * @return bool True if the format is valid, false otherwise.
     */
    private static function validate_datetime_format( $format ) {
        try {
            $testDate = new DateTime();
            $testDate->format($format); // Attempt to format with the given string
            return true;
        } catch (Exception $e) {
            // If an exception is thrown, the format is invalid
            return false;
        }
    }
}
