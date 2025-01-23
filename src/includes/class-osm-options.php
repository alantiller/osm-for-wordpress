<?php

class OSM_Options {
    /**
     * Set date format.
     * 
     * @param string $format Date format.
     * @return void
     */
    public static function set_date_format( $format ) {
        // Confirm the date format is not empty.
        if ( empty( $format ) ) {
            return;
        }

        // Confirm the date format is a valid PHP date format.
        if ( ! self::validate_date_format( $format ) ) {
            return;
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
        $date_format = self::get( 'date_format', 'd M Y' );

        // Confirm the date format is a valid PHP date format.
        if ( ! self::validate_date_format( $date_format ) ) {
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
        // Confirm the time format is not empty.
        if ( empty( $format ) ) {
            return;
        }

        // Confirm the time format is a valid PHP date format.
        if ( ! self::validate_time_format( $format ) ) {
            return;
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
        $time_format = self::get( 'time_format', 'H:i' );

        // Confirm the time format is a valid PHP date format.
        if ( ! self::validate_time_format( $time_format ) ) {
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
     * Validate a date format string.
     * 
     * @param string $format Date format string.
     * @return bool True if the format is valid, false otherwise.
     */
    private static function validate_date_format( $format ) {
        // Attempt to create a DateTime object using the provided format
        try {
            $testDate = DateTime::createFromFormat($format, '2000-01-01');
            $errors = DateTime::getLastErrors();
        
            // If there are no errors or warnings, the format is valid
            return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
        } catch (Exception $e) {
            // Catch any exceptions and treat the format as invalid
            return false;
        }  
    }

    /**
     * Validate a time format string.
     * 
     * @param string $format Time format string.
     * @return bool True if the format is valid, false otherwise.
     */
    private static function validate_time_format( $format ) {
        // Attempt to create a DateTime object using the provided format
        try {
            $testTime = DateTime::createFromFormat($format, '12:34:56');
            $errors = DateTime::getLastErrors();
        
            // If there are no errors or warnings, the format is valid
            return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
        } catch (Exception $e) {
            // Catch any exceptions and treat the format as invalid
            return false;
        }
    }
}