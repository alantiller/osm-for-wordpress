<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" aria-labelledby="osm-display-title">
                <h2 id="osm-display-title">Display options</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_advanced_options' ) ); ?>">
                    <?php wp_nonce_field( 'osm_advanced_options_nonce' ); ?>
                    <p><label for="osm_date_format">Date format</label><input type="text" id="osm_date_format" name="osm_date_format" value="<?php echo esc_attr( $advanced_options['osm_date_format'] ); ?>" placeholder="d M Y" class="regular-text"></p>
                    <p><label for="osm_time_format">Time format</label><input type="text" id="osm_time_format" name="osm_time_format" value="<?php echo esc_attr( $advanced_options['osm_time_format'] ); ?>" placeholder="H:i" class="regular-text"></p>
                    <p class="description">Use <a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" rel="noopener noreferrer">PHP date and time formats</a>.</p>
                    <?php submit_button( 'Save display options' ); ?>
                </form>
            </section>
<section class="postbox osm-settings-tile" id="osm-maintenance" aria-labelledby="osm-maintenance-title">
                <h2 id="osm-maintenance-title">Cache and reset</h2>
                <p>Clear cached data to fetch fresh information from OSM.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_purge_cache' ) ); ?>"><?php wp_nonce_field( 'osm_purge_nonce' ); ?><p><button type="submit" class="button">Purge cache</button></p></form>
                <h3>Reset configuration</h3><p>Removes credentials, section selections, Section Points choices, waiting-list access, display options and cached data.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_reset_configuration' ) ); ?>"><?php wp_nonce_field( 'osm_reset_nonce' ); ?><p><button type="submit" class="button">Reset configuration</button></p></form>

            </section>
<section class="postbox osm-settings-tile" id="osm-reset-auth" aria-labelledby="osm-reset-auth-title">
    <h2 id="osm-reset-auth-title">Reset Auth</h2>
    <p>Remove your saved Client ID, Client Secret and cached API data. You will return to Authentication to reconnect your application.</p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_reset_auth' ) ); ?>">
        <?php wp_nonce_field( 'osm_reset_auth_nonce' ); ?>
        <?php submit_button( 'Reset Auth' ); ?>
    </form>
</section>

