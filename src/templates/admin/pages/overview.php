<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$programme_count = count( $enabled_sections );
$points_count = count( array_filter( $public_patrol_sections ) );
$recipient_count = count( $notification_recipients );
?>

<div class="osm-overview-columns">
    <section class="postbox osm-settings-tile" aria-labelledby="osm-overview-title">
        <h2 id="osm-overview-title">Overview</h2>
        <p>Your OSM application is connected. Choose the sections and information you want to display, then add a shortcode or Gutenberg block to your pages.</p>
        <dl class="osm-overview-summary">
            <div><dt>Programme and events</dt><dd><?php echo esc_html( $programme_count . ( 1 === $programme_count ? ' section enabled' : ' sections enabled' ) ); ?></dd></div>
            <div><dt>Section Points</dt><dd><?php echo esc_html( $points_count . ( 1 === $points_count ? ' section enabled' : ' sections enabled' ) ); ?></dd></div>
            <div><dt>Waiting list</dt><dd><?php echo $waiting_list['section_id'] ? 'Configured' : 'Disabled'; ?></dd></div>
            <div><dt>Notifications</dt><dd><?php echo esc_html( $recipient_count . ( 1 === $recipient_count ? ' user selected' : ' users selected' ) ); ?></dd></div>
        </dl>
        <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=online-scout-manager&tab=sections' ) ); ?>">Manage sections</a> <a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=online-scout-manager&tab=documentation' ) ); ?>">View documentation</a></p>
    </section>
    <div class="osm-settings-column">
<section class="postbox osm-settings-tile" aria-labelledby="osm-api-usage-title">
                <h2 id="osm-api-usage-title">API usage</h2>
                <table class="widefat striped"><tbody>
                    <tr><th scope="row">Requests used</th><td><?php echo null !== $api_usage ? esc_html( $api_usage['used'] ) : 'Unavailable'; ?></td></tr>
                    <tr><th scope="row">Remaining</th><td><?php echo null !== $api_usage ? esc_html( $api_usage['remaining'] . ' / ' . $api_usage['limit'] ) : 'Unavailable'; ?></td></tr>
                    <tr><th scope="row">Resets in</th><td><?php if ( null !== $api_usage ): $reset_seconds = max( 0, $api_usage['reset_at'] - time() ); ?><span data-osm-reset-seconds="<?php echo esc_attr( $reset_seconds ); ?>"><?php echo $reset_seconds > 0 ? esc_html( (string) (int) ceil( $reset_seconds / 60 ) . ' min' ) : 'Awaiting next OSM response'; ?></span><?php else: ?>Unavailable<?php endif; ?></td></tr>
                </tbody></table>
                <p class="description">Last reported by OSM; updated when the plugin makes an API request. Cached displays do not use requests. <?php if ( null === $api_usage ): ?>OSM has not yet returned complete quota headers.<?php else: ?>Counts remain the last reported values until the next response.<?php endif; ?></p>
            </section>
<section class="postbox osm-settings-tile" aria-labelledby="osm-help-title"><h2 id="osm-help-title">Help and feedback</h2><p><a href="https://github.com/alantiller/osm-for-wordpress" target="_blank" rel="noopener noreferrer">Report a bug or request a feature on GitHub</a>.</p></section>
    </div>
</div>
