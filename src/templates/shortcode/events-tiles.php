<?php
declare(strict_types=1);
/** Public event tiles retain the existing date/time and location allowlist. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="osm-activity-tiles osm-activity-tiles--<?php echo esc_attr( $layout ); ?>">
    <?php foreach ( $events as $event ):
        $start = trim( OSM_Shortcodes::format_date( $event['startdate_g'] ?? $event['startdate'] ?? $event['eventdate'] ?? '', $date_format ) . ' ' . OSM_Shortcodes::format_time( $event['starttime'] ?? '', $time_format ) );
        $end = trim( OSM_Shortcodes::format_date( $event['enddate_g'] ?? $event['enddate'] ?? '', $date_format ) . ' ' . OSM_Shortcodes::format_time( $event['endtime'] ?? '', $time_format ) );
        $location = OSM_Shortcodes::text( $event['location'] ?? '' );
    ?>
        <article class="osm-activity-tile">
            <h3><?php echo esc_html( OSM_Shortcodes::text( $event['name'] ?? '' ) ?: 'Event' ); ?></h3>
            <div class="osm-activity-body"><dl class="osm-activity-details">
                <?php foreach ( [ 'Starts' => $start, 'Ends' => $end, 'Location' => $location ] as $label => $value ): ?>
                    <?php if ( '' !== $value ): ?><div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endif; ?>
                <?php endforeach; ?>
            </dl></div>
        </article>
    <?php endforeach; ?>
</div>
