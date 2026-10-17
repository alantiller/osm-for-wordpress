<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<table class="osm-public-table">
    <thead>
        <tr>
            <th>Start Date/Time</th>
            <th>End Date/Time</th>
            <th>Name</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $events as $event ): ?>
            <tr>
                <td><?php echo esc_html( OSM_Shortcodes::format_date( $event['startdate_g'] ?? $event['startdate'] ?? $event['eventdate'] ?? '', $date_format ) ) . ' ' . esc_html( OSM_Shortcodes::format_time( $event['starttime'] ?? '', $time_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::format_date( $event['enddate_g'] ?? $event['enddate'] ?? '', $date_format ) ) . ' ' . esc_html( OSM_Shortcodes::format_time( $event['endtime'] ?? '', $time_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $event['name'] ?? '' ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $event['location'] ?? '' ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
