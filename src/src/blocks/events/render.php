<?php

/**
 * Render callback for the OSM Events block.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function render_osm_events_block( $attributes ) {
    $sectionid = esc_attr( $attributes['sectionid'] ?? '' );
    $futureonly = $attributes['futureonly'] ? 'true' : 'false';

    return do_shortcode( '[osm_events sectionid="' . $sectionid . '" futureonly="' . $futureonly . '"]' );
}