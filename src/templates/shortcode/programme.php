<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<table class="osm-public-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Title</th>
            <th>Summary</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $programme as $item ): ?>
            <tr>
                <td style="white-space: nowrap;"><?php echo esc_html( OSM_Shortcodes::format_date( $item['meetingdate'] ?? '', $date_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $item['title'] ?? '' ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $item['notesforparents'] ?? '' ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
