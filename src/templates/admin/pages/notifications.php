<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" id="osm-notifications" aria-labelledby="osm-notifications-title">
                <h2 id="osm-notifications-title">Notifications</h2>
                <p>Notify selected WordPress users when an application creates a child in OSM, including incomplete contact or notes saves. Each user receives a separate email with a link to OSM; applicant details are excluded.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_notifications' ) ); ?>">
                    <?php wp_nonce_field( 'osm_notifications_nonce' ); ?>
                    <fieldset><legend><strong>Notify these users</strong></legend>
                    <?php foreach ( $notification_users as $notification_user ): ?><p><label><input type="checkbox" name="osm_notification_user_ids[]" value="<?php echo esc_attr( $notification_user->ID ); ?>" <?php checked( in_array( (int) $notification_user->ID, $notification_recipients, true ) ); ?>> <?php echo esc_html( $notification_user->display_name . ' (' . $notification_user->user_email . ')' ); ?></label></p><?php endforeach; ?>
                    </fieldset>
                    <p class="description">Leave all users unticked to disable notifications. Delivery uses WordPress email (wp_mail) and your site’s existing mail setup.</p>
                    <?php submit_button( 'Save notifications' ); ?>
                </form>
                <?php if ( isset( $notification_delivery['sent'], $notification_delivery['failed'] ) ): ?><p role="status">Last notification: <?php echo esc_html( (int) $notification_delivery['sent'] ); ?> accepted by WordPress mail; <?php echo esc_html( (int) $notification_delivery['failed'] ); ?> failed or unavailable recipients. Mail acceptance does not confirm inbox delivery.</p><?php endif; ?>
            </section>
