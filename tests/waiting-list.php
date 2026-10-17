<?php
declare(strict_types=1);
/** Offline Client Credentials and application lifecycle regression checks; no real submissions. */
require __DIR__ . '/run.php';
$transients = [];
$allowed = true;
$session = 'test-session';
$scheduled = [];
class Test_Redirect extends RuntimeException {
    public function __construct( public string $url ) { parent::__construct( 'Redirect intercepted' ); }
}
/** Stub an atomic option insert without accepting duplicates. */
function add_option( $key, $value, ...$args ) { if ( array_key_exists( $key, $GLOBALS['options'] ) ) { return false; } $GLOBALS['options'][ $key ] = $value; return true; }
/** Store only synthetic transient fixtures. */
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; }
/** Retrieve a synthetic transient or false. */
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
/** Delete a synthetic transient. */
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
/** Model the administrator capability gate. */
function current_user_can( $capability ) { return $GLOBALS['allowed'] && 'manage_options' === $capability; }
/** Stop unauthorized requests without terminating the test runner. */
function wp_die( ...$args ) { throw new RuntimeException( 'Access denied' ); }
/** Model a valid administrator form nonce. */
function check_admin_referer( $nonce ) {}
/** Supply the isolated administrator identity. */
function get_current_user_id() { return 42; }
/** Supply a changeable WordPress login session. */
function wp_get_session_token() { return $GLOBALS['session']; }
/** Build the isolated WordPress administrator URL. */
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
/** Build an OAuth redirect with normal query encoding. */
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
/** Intercept redirects before exit so their targets can be verified. */
function wp_redirect( $url ) { throw new Test_Redirect( $url ); }
/** Intercept local WordPress redirects. */
function wp_safe_redirect( $url ) { throw new Test_Redirect( $url ); }
/** Provide a deterministic, test-only signing secret. */
function wp_salt( $scheme ) { return 'isolated-test-signing-secret'; }
/** Create the synthetic form nonce. */
function wp_create_nonce( $action ) { return 'test-form-nonce'; }
/** Refuse invalid form nonces. */
function wp_verify_nonce( $nonce, $action ) { return 'test-form-nonce' === $nonce; }
/** Record cleanup events without invoking WordPress cron. */
function wp_schedule_single_event( $time, $hook, $args ) { $GLOBALS['scheduled'][] = compact( 'time', 'hook', 'args' ); }
$waiting = new OSM_Waiting_List();
$options['osm_client_id'] = 'test-client';
$options['osm_client_secret'] = 'test-secret';
$options['osm_waiting_list_section_id'] = 789; // Deliberately not feed-enabled.
OSM_API::clear_authorization();
$write_requests = 0;
$contact_fail = false;
$request_responder = function ( $url, $args ) use ( &$write_requests, &$contact_fail ) {
    parse_str( $args['body'] ?? '', $body );
    if ( str_contains( $url, '/oauth/token' ) ) {
        expect( 'client_credentials' === $body['grant_type'] && OSM_API::get_scopes() === $body['scope'], 'waiting-list authentication uses Client Credentials with all four scopes' );
        $data = [ 'access_token' => 'shared-token', 'expires_in' => 3600 ];
    } elseif ( str_contains( $url, 'action=newMember' ) ) {
        $write_requests++;
        expect( 'Bearer shared-token' === $args['headers']['Authorization'] && 789 === (int) $body['sectionid'], 'write request uses the shared token and configured destination' );
        expect( array_keys( $body ) === [ 'firstname', 'lastname', 'dob', 'started', 'startedsection', 'sectionid', 'originating_section_id' ], 'only the minimal member field allowlist is transmitted' );
        $data = [ 'result' => 'ok', 'scoutid' => 123456 ];
    } elseif ( str_contains( $url, 'action=update' ) ) {
        expect( str_contains( $url, '/ext/customdata/?action=update&section_id=789' ) && 'false' === $body['associated_is_section'] && 123456 === (int) $body['associated_id'], 'adult update uses the custom-data route and explicit section/member context' );
        expect( 'Test Adult' === $body['data']['firstname'] && 'Fixture' === $body['data']['lastname'], 'both adult names sent with the email' );
        expect( 1 === (int) $body['group_id'] && 'parent@example.test' === $body['data']['email1'], 'adult contact saved on the confirmed member only' );
        if ( $contact_fail ) { return [ 'body' => 'sensitive@example.test', 'response' => [ 'code' => 503 ], 'headers' => [] ]; }
        $data = [ 'status' => true, 'error' => null, 'data' => [ 'firstname' => 'Test Adult', 'lastname' => 'Fixture', 'email1' => 'parent@example.test' ] ];
    } else { throw new RuntimeException( 'Unexpected write fixture URL' ); }
    return [ 'body' => json_encode( $data ), 'response' => [ 'code' => 200 ], 'headers' => [] ];
};
$html = $renderer->render_waiting_list( [] );
expect( str_contains( $html, 'class="osm-application-form"' ) && ! str_contains( $html, 'shared-token' ), 'configured section enables the native form without a separate connection' );
expect( str_contains( $renderer->render_waiting_list( [ '_osm_preview' => true ] ), 'fieldset disabled' ), 'builder preview disables native submission fields' );
$application = [ 'firstname' => 'Test Child', 'lastname' => 'Fixture', 'dob' => '2020-01-02', 'parent_firstname' => 'Test Adult', 'parent_lastname' => 'Fixture', 'email' => 'parent@example.test', 'privacy_acknowledged' => '1' ];
expect( [] === OSM_Waiting_List::validate_application( $application )['errors'], 'valid minimal application passes' );
expect( OSM_Waiting_List::validate_application( array_merge( $application, [ 'privacy_acknowledged' => '', 'dob' => '2026-02-30', 'email' => 'bad' ] ) )['errors'] !== [], 'privacy acknowledgment, real date and email are validated server-side' );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
$credentials = OSM_Waiting_List::form_credentials();
$_POST = array_merge( $application, [ 'osm_waiting_list_submit' => '1', '_osm_nonce' => 'bad', '_osm_token' => $credentials['token'] ] );
$waiting->handle_submission();
expect( 0 === $write_requests, 'invalid nonce prevents all member writes' );
$_POST['_osm_nonce'] = $credentials['nonce'];
$_POST['website'] = 'bot';
$waiting->handle_submission();
expect( 0 === $write_requests, 'honeypot prevents all member writes' );
unset( $_POST['website'] );
$waiting->handle_submission();
expect( 1 === $write_requests && str_contains( $renderer->render_waiting_list( [] ), 'application has been added' ), 'valid submission creates one child and confirms contact success' );
$waiting->handle_submission();
expect( 1 === $write_requests && str_contains( $renderer->render_waiting_list( [] ), 'already sent or attempted' ), 'repeated POST cannot create duplicate members' );
$contact_fail = true;
$_POST['_osm_token'] = OSM_Waiting_List::form_credentials()['token'];
$waiting->handle_submission();
$html = $renderer->render_waiting_list( [] );
expect( 2 === $write_requests && str_contains( $html, 'Your child was added' ) && ! str_contains( $html, 'sensitive@example.test' ) && ! str_contains( $html, 'class="osm-application-form"' ), 'partial contact failure is honest, sanitised and cannot automatically resubmit' );
for ( $attempt = 0; $attempt < 4; $attempt++ ) {
    $_POST['_osm_token'] = OSM_Waiting_List::form_credentials()['token'];
    $waiting->handle_submission();
}
expect( 5 === $write_requests && str_contains( $renderer->render_waiting_list( [] ), 'Too many applications' ), 'sixth hourly attempt is blocked atomically before member creation' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.2';
$_POST['_osm_token'] = OSM_Waiting_List::form_credentials()['token'] . 'tampered';
$waiting->handle_submission();
expect( 5 === $write_requests, 'tampered signed form token is rejected before member creation' );
foreach ( array_merge( $options, $transients ) as $key => $value ) {
    expect( ! str_contains( json_encode( $value ), 'parent@example.test' ) && ! str_contains( json_encode( $value ), 'Test Child' ) && ! str_contains( json_encode( $value ), '192.0.2.1' ), 'application fields and raw connection IP never persisted' );
}
/** Capture AJAX success/error payloads instead of terminating the runner. */
class Test_Json_Response extends RuntimeException {
    /** @param bool $success Whether WordPress would send a successful response. @param array<string,mixed> $data Synthetic payload. */
    public function __construct( public bool $success, public array $data ) { parent::__construct( 'JSON response intercepted' ); }
}
/** @param array<string,mixed> $data Error payload. @param int $status HTTP status. @return never */
function wp_send_json_error( $data, $status ) { throw new Test_Json_Response( false, $data ); }
/** @param array<string,mixed> $data Public credentials. @return never */
function wp_send_json_success( $data ) { throw new Test_Json_Response( true, $data ); }
$credentials = OSM_Waiting_List::form_credentials();
delete_option( 'osm_waiting_list_section_id' );
try { $waiting->prepare(); } catch ( Test_Json_Response $response ) { expect( ! $response->success, 'clearing the section disables fresh AJAX credentials' ); }
$_POST['_osm_token'] = $credentials['token'];
$waiting->handle_submission();
expect( 5 === $write_requests, 'clearing the section disables previously rendered submissions' );
expect( ! str_contains( $renderer->render_waiting_list( [] ), 'class="osm-application-form"' ), 'clearing the section hides the application form' );
try { OSM_API::create_waiting_list_member( 789, $application ); expect( false, 'unconfigured direct API write refused' ); }
catch ( RuntimeException $error ) { expect( 5 === $write_requests, 'unconfigured direct API write refused before HTTP' ); }
$options['osm_waiting_list_section_id'] = 789;
$test_privacy_url = '';
try { $waiting->prepare(); } catch ( Test_Json_Response $response ) { expect( $response->success && isset( $response->data['token'], $response->data['nonce'] ), 'configured section supplies fresh credentials without a separate connection' ); }
expect( str_contains( $renderer->render_waiting_list( [] ), 'class="osm-application-form"' ), 'selecting a section re-enables the form without a separate connection or policy-page prerequisite' );
echo "Passed {$passed} total checks including Client Credentials and waiting-list lifecycle.\n";
