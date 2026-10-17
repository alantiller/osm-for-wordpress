<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<ul class="osm-patrols">
    <?php foreach ( $patrols as $patrol ): ?>
        <li class="osm-patrol-card"><div class="osm-patrol-heading"><span class="osm-patrol-name"><?php echo esc_html( OSM_Shortcodes::text( $patrol['name'] ?? '' ) ); ?></span><span class="osm-patrol-points"><?php echo isset( $patrol['points'] ) ? esc_html( $patrol['points'] . ' points' ) : 'Points unavailable'; ?></span></div>
            <?php if ( ! empty( $patrol['members'] ) ): ?>
                <ul class="osm-patrol-members"><?php foreach ( $patrol['members'] as $member_name ): ?><li><?php echo esc_html( $member_name ); ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
