<?php
declare(strict_types=1);

/** Send minimal waiting-list alerts to explicitly selected WordPress accounts. */
class OSM_Notifications {
    /**
     * Return configured recipient IDs without exposing email addresses publicly.
     * @return list<int> Unique positive WordPress user IDs; missing settings disable notifications.
     */
    public static function get_recipient_ids(): array {
        $ids = [];
        foreach ( (array) get_option( 'osm_notification_user_ids', [] ) as $id ) {
            if ( ( is_int( $id ) || is_string( $id ) ) && false !== filter_var( $id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] ) ) { $ids[] = (int) $id; }
        }
        return array_values( array_unique( $ids ) );
    }

    /**
     * Alert selected users after confirmed child creation, including partial saves.
     *
     * Call only after claiming the one-use application token. Recipient addresses are
     * resolved at send time and mailed individually. Applicant names, birth dates,
     * contact details, notes and volunteering answers never enter notification emails.
     * Mail failures must not undo a successful OSM write or invite duplicate applications.
     * @param bool $partial Whether a subsequent OSM contact/notes write was incomplete.
     * @return void Records only delivery counts/time; never retries or throws mail failures.
     */
    public static function send_waiting_list_notification( bool $partial ): void {
        $recipients = self::get_recipient_ids();
        if ( ! $recipients ) { return; }
        $sent = 0;
        $failed = 0;
        $addresses = [];
        $message = "A child has been added to your configured Online Scout Manager waiting list.\n\n";
        $message .= $partial ? "One or more subsequent application details could not be saved. Review the existing record and contact the applicant; do not create a duplicate application.\n\n" : "The application details were saved. Please review the waiting list in OSM.\n\n";
        $message .= "Open Online Scout Manager: https://www.onlinescoutmanager.co.uk/\n\nPersonal application details are intentionally excluded from this notification.";
        foreach ( $recipients as $user_id ) {
            try {
                $user = get_userdata( $user_id );
                if ( ! $user || ! is_email( $user->user_email ) ) { $failed++; continue; }
                $email_key = strtolower( $user->user_email );
                if ( isset( $addresses[ $email_key ] ) ) { continue; }
                $addresses[ $email_key ] = true;
                if ( wp_mail( $user->user_email, 'New OSM waiting-list application', $message ) ) { $sent++; } else { $failed++; }
            } catch ( Throwable $error ) {
                // SMTP/plugin exceptions must never turn a completed OSM write into a failed application.
                $failed++;
            }
        }
        update_option( 'osm_notification_last_delivery', [ 'sent' => $sent, 'failed' => $failed, 'time' => time() ], false );
    }
}
