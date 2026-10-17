<?php
declare(strict_types=1);
/** Public programme tiles use the same approved fields and formatting as the table. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="osm-activity-tiles osm-activity-tiles--<?php echo esc_attr( $layout ); ?>">
    <?php foreach ( $programme as $item ): ?>
        <article class="osm-activity-tile">
            <h3><?php echo esc_html( OSM_Shortcodes::text( $item['title'] ?? '' ) ?: 'Programme activity' ); ?></h3>
            <div class="osm-activity-body">
                <?php $date = OSM_Shortcodes::format_date( $item['meetingdate'] ?? '', $date_format ); ?>
                <?php if ( $date ): ?><dl><dt>Date</dt><dd><?php echo esc_html( $date ); ?></dd></dl><?php endif; ?>
                <?php if ( ! empty( $item['notesforparents'] ) ): ?><p><?php echo esc_html( OSM_Shortcodes::text( $item['notesforparents'] ) ); ?></p><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
