<?php

/**
 * Render callback for the OSM Programme block.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function render_osm_programme_block( $attributes ) {
    $sectionid = esc_attr( $attributes['sectionid'] ?? '' );
    $futureonly = $attributes['futureonly'] ? 'true' : 'false';

    return do_shortcode( '[osm_programme sectionid="' . $sectionid . '" futureonly="' . $futureonly . '"]' );
}