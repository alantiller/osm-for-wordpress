<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="osm-table-card" role="region" aria-label="Events" tabindex="0">
<table class="osm-public-table">
    <thead>
        <tr>
            <th scope="col">Start Date/Time</th>
            <th scope="col">End Date/Time</th>
            <th scope="col">Name</th>
            <th scope="col">Location</th>
        </tr>
    </thead>
    <?php foreach ( $section_groups ?? [ [ 'items' => $events ] ] as $section_group ): ?>
    <tbody>
        <?php if ( isset( $section_group['heading'] ) ): ?><tr class="osm-table-section"><th scope="rowgroup" colspan="4">Section: <?php echo esc_html( $section_group['heading'] ); ?></th></tr><?php endif; ?>
        <?php if ( ! empty( $section_group['message'] ) ): ?><tr><td colspan="4"><?php echo esc_html( $section_group['message'] ); ?></td></tr><?php endif; ?>
        <?php foreach ( $section_group['items'] as $event ): ?>
            <tr>
                <td><?php echo esc_html( OSM_Shortcodes::format_date( $event['startdate_g'] ?? $event['startdate'] ?? $event['eventdate'] ?? '', $date_format ) ) . ' ' . esc_html( OSM_Shortcodes::format_time( $event['starttime'] ?? '', $time_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::format_date( $event['enddate_g'] ?? $event['enddate'] ?? '', $date_format ) ) . ' ' . esc_html( OSM_Shortcodes::format_time( $event['endtime'] ?? '', $time_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $event['name'] ?? '' ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $event['location'] ?? '' ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endforeach; ?>
</table>
</div>
