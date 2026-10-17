<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<ul class="osm-patrols">
    <?php foreach ( $patrols as $patrol ): ?>
        <li><?php echo esc_html( OSM_Shortcodes::text( $patrol['name'] ?? '' ) ); ?>
            <?php if ( ! empty( $patrol['members'] ) ): ?>
                <ul><?php foreach ( $patrol['members'] as $member_name ): ?><li><?php echo esc_html( $member_name ); ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
