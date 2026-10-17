<?php
declare(strict_types=1);
/** Date parts, direct OSM notes and selected-user notification regression checks. */
require __DIR__ . '/waiting-list.php';
$parts = array_merge( $application, [ 'dob_day' => '29', 'dob_month' => '02', 'dob_year' => '2020', 'parent_note' => "Sibling attends.\nFriday preferred.", 'willing_to_help' => 'yes' ] );
expect( '2020-02-29' === OSM_Waiting_List::validate_application( $parts )['values']['dob'], 'day/month/year convert to a real ISO leap date' );
foreach ( [ [ 'dob_year' => '2021' ], [ 'dob_day' => '31', 'dob_month' => '04' ], [ 'dob_year' => '2099' ], [ 'dob_day' => '' ], [ 'dob_month' => [] ], [ 'dob_year' => '20' ] ] as $invalid ) {
    expect( [] !== OSM_Waiting_List::validate_application( array_merge( $parts, $invalid ) )['errors'], 'invalid or incomplete date parts cannot fall back to an old ISO date' );
}
expect( [] !== OSM_Waiting_List::validate_application( array_merge( $parts, [ 'parent_note' => str_repeat( 'x', 2001 ) ] ) )['errors'], 'long notes rejected before OSM writes' );
expect( [] !== OSM_Waiting_List::validate_application( array_merge( $parts, [ 'willing_to_help' => 'invented' ] ) )['errors'], 'helping answer is allowlisted' );
$test_users = [
    10 => (object) [ 'ID' => 10, 'display_name' => 'Test Leader', 'user_email' => 'leader@example.test' ],
    11 => (object) [ 'ID' => 11, 'display_name' => 'Test Administrator', 'user_email' => 'admin@example.test' ],
];
$options['osm_notification_user_ids'] = [ 10, 11 ];
$test_mail = [];
$contact_fail = false;
$notes_missing = false;
$note_writes = 0;
$original_responder = $request_responder;
$request_responder = static function ( $url, $args ) use ( $original_responder ) {
    parse_str( $args['body'] ?? '', $body );
    if ( str_contains( $url, 'action=getData' ) ) {
        $data = [ 'status' => true, 'data' => $GLOBALS['notes_missing'] ? [] : [ [ 'group_id' => 7, 'columns' => [ [ 'column_id' => 89, 'varname' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'value' => 'Existing note' ] ] ] ] ];
    } elseif ( str_contains( $url, 'action=updateColumn' ) ) {
        $GLOBALS['note_writes']++;
        expect( 7 === (int) $body['group_id'] && 89 === (int) $body['column_id'] && 123456 === (int) $body['associated_id'], 'notes use discovered section-specific column on the confirmed child' );
        expect( str_contains( $body['value'], 'Existing note' ) && str_contains( $body['value'], 'Sibling attends.' ) && str_contains( $body['value'], 'Parent or carer willing to help: Yes' ), 'existing notes preserved with parent note and helping answer appended' );
        $data = [ 'status' => true, 'data' => [ 'column_id' => 89 ] ];
    } else { return $original_responder( $url, $args ); }
    return [ 'body' => json_encode( $data ), 'response' => [ 'code' => 200 ], 'headers' => [] ];
};
$_SERVER['REMOTE_ADDR'] = '192.0.2.77';
$_POST = array_merge( $parts, [ 'osm_waiting_list_submit' => '1', '_osm_nonce' => 'test-form-nonce', '_osm_token' => OSM_Waiting_List::form_credentials()['token'] ] );
$waiting->handle_submission();
expect( 1 === $note_writes && str_contains( $renderer->render_waiting_list( [] ), 'application has been added' ), 'notes and helping answer complete the direct application' );
expect( 2 === count( $test_mail ) && 'leader@example.test' === $test_mail[0]['to'] && 'admin@example.test' === $test_mail[1]['to'], 'each selected WordPress user receives a separate notification' );
foreach ( $test_mail as $mail ) {
    expect( ! str_contains( $mail['message'], 'Test Child' ) && ! str_contains( $mail['message'], 'parent@example.test' ) && ! str_contains( $mail['message'], 'Sibling attends' ) && ! str_contains( $mail['message'], '2020-02-29' ), 'notification excludes every applicant field' );
}
$waiting->handle_submission();
expect( 2 === count( $test_mail ) && 1 === $note_writes, 'duplicate POST cannot resend notifications or notes' );
$notes_missing = true;
$_POST['_osm_token'] = OSM_Waiting_List::form_credentials()['token'];
$waiting->handle_submission();
expect( str_contains( $renderer->render_waiting_list( [] ), 'could not save the additional notes' ) && 4 === count( $test_mail ), 'missing Notes mapping produces honest partial result and a staff alert' );
$notes_missing = false;
$test_mail_result = false;
$_POST['_osm_token'] = OSM_Waiting_List::form_credentials()['token'];
$waiting->handle_submission();
expect( str_contains( $renderer->render_waiting_list( [] ), 'application has been added' ) && 2 === $options['osm_notification_last_delivery']['failed'], 'mail failure cannot turn a saved OSM application into a failure' );
foreach ( $options as $value ) {
    expect( ! str_contains( json_encode( $value ), 'Sibling attends' ) && ! str_contains( json_encode( $value ), 'Test Child' ), 'optional application data is not persisted in WordPress' );
}
echo "Passed {$passed} total checks including date parts, notes and notification delivery.\n";
