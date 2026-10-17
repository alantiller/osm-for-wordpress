<?php
/** Administrator layout with authenticated navigation; page names are allowlisted by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap osm-admin">
    <h1>Online Scout Manager</h1>
    <?php if ( $api_error ): ?><div class="notice notice-error"><p><?php echo esc_html( $api_error ); ?> Your saved settings have been kept.</p></div><?php endif; ?>
    <?php $tab_icons = [ 'overview' => 'dashboard', 'sections' => 'groups', 'section_points' => 'awards', 'waiting_list' => 'clipboard', 'notifications' => 'bell', 'advanced' => 'admin-generic', 'authentication' => 'lock', 'documentation' => 'book-alt' ]; ?>
    <div class="osm-admin-layout">
        <nav class="osm-settings-nav" aria-label="Online Scout Manager settings">
            <?php foreach ( $tabs as $tab => $label ): ?>
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=online-scout-manager&tab=' . $tab ) ); ?>" <?php if ( $active_tab === $tab ): ?>aria-current="page"<?php endif; ?>><span class="dashicons dashicons-<?php echo esc_attr( $tab_icons[ $tab ] ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $label ); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <main class="osm-settings-column" id="osm-admin-content">
            <?php
            $page_templates = [ 'overview' => 'overview.php', 'sections' => 'sections.php', 'section_points' => 'section_points.php', 'waiting_list' => 'waiting_list.php', 'notifications' => 'notifications.php', 'advanced' => 'advanced.php', 'authentication' => 'authentication.php', 'documentation' => 'documentation.php' ];
            include OSM_TEMPLATES_DIR . '/admin/pages/' . $page_templates[ $active_tab ];
            ?>
        </main>

    </div>
</div>
