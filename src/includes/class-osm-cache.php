<?php

class OSM_Cache {
    /**
     * Get a cached value.
     *
     * @param string $key Cache key.
     * @return mixed Cached value or false if not found or expired.
     */
    public static function get( $key ) {
        $cache = get_option( "osm_cached_{$key}" );

        if ( $cache && isset( $cache['expires'] ) && $cache['expires'] > time() ) {
            return $cache['value'];
        }

        return false;
    }

    /**
     * Set a cache value.
     *
     * @param string $key Cache key.
     * @param mixed $value Value to cache.
     * @param int $ttl Time-to-live in seconds.
     * @return void
     */
    public static function set( $key, $value, $ttl = 3600 ) {
        $expires = time() + $ttl;

        update_option( "osm_cached_{$key}", [
            'value'   => $value,
            'expires' => $expires,
        ] );
    }

    /**
     * Delete a cached value.
     *
     * @param string $key Cache key.
     * @return void
     */
    public static function delete( $key ) {
        delete_option( "osm_cached_{$key}" );
    }

    /**
     * Clear all cached data.
     *
     * @return void
     */
    public static function clear_all() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'osm_cached_%'" );
    }
}