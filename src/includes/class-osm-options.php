<?php

class OSM_Options {
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