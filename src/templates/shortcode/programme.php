<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="osm-table-card" role="region" aria-label="Programme" tabindex="0">
<table class="osm-public-table">
    <thead>
        <tr>
            <th scope="col">Date</th>
            <th scope="col">Title</th>
            <th scope="col">Summary</th>
        </tr>
    </thead>
    <?php foreach ( $section_groups ?? [ [ 'items' => $programme ] ] as $section_group ): ?>
    <tbody>
        <?php if ( isset( $section_group['heading'] ) ): ?><tr class="osm-table-section"><th scope="rowgroup" colspan="3">Section: <?php echo esc_html( $section_group['heading'] ); ?></th></tr><?php endif; ?>
        <?php if ( ! empty( $section_group['message'] ) ): ?><tr><td colspan="3"><?php echo esc_html( $section_group['message'] ); ?></td></tr><?php endif; ?>
        <?php foreach ( $section_group['items'] as $item ): ?>
            <tr>
                <td><?php echo esc_html( OSM_Shortcodes::format_date( $item['meetingdate'] ?? '', $date_format ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $item['title'] ?? '' ) ); ?></td>
                <td><?php echo esc_html( OSM_Shortcodes::text( $item['notesforparents'] ?? '' ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endforeach; ?>
</table>
</div>
