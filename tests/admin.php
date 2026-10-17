<?php
/** Admin authorization and cache-isolation checks using the shared offline fixtures. */
require __DIR__ . '/run.php';
require OSM_PLUGIN_DIR . 'includes/class-osm-admin.php';
$admin_allowed = false;
$checked_nonce = null;
$stop_after_nonce = true;
$privacy_text = '';
function current_user_can( $capability ) { return 'manage_options' === $capability && $GLOBALS['admin_allowed']; }
function wp_die( ...$args ) { throw new RuntimeException( 'Access denied' ); }
function check_admin_referer( $nonce ) {
    $GLOBALS['checked_nonce'] = $nonce;
    if ( $GLOBALS['stop_after_nonce'] ) { throw new RuntimeException( 'Nonce gate reached' ); }
}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
/** List synthetic WordPress notification accounts for the admin tile. */
function get_users( $args ) { return array_values( $GLOBALS['test_users'] ?? [] ); }
/** Render a native disabled control state. */
function disabled( $value ) { if ( $value ) { echo 'disabled'; } }
function checked( $checked, $current = true, $echo = true ) {
    $value = (bool) $checked === (bool) $current ? 'checked="checked"' : '';
    if ( $echo ) { echo $value; }
    return $value;
}
function wp_nonce_field( $nonce ) { echo '<input type="hidden" name="_wpnonce" value="test-nonce">'; }
function submit_button( $label ) { echo '<button>' . esc_html( $label ) . '</button>'; }
function wp_add_privacy_policy_content( $title, $content ) { $GLOBALS['privacy_text'] = $content; }
$admin = new OSM_Admin();
expect( [ 10, 0 ] === end( $GLOBALS['hook_arguments']['admin_post_osm_save_privacy'] ), 'WordPress action arguments cannot override the privacy nonce default' );
$actions = [
    'save_auth' => 'osm_auth_nonce',
    'save_sections' => 'osm_sections_nonce',
    'save_notifications' => 'osm_notifications_nonce',
    'save_section_points' => 'osm_section_points_nonce',
    'save_waiting_list' => 'osm_waiting_list_nonce',
    'clear_waiting_list' => 'osm_clear_waiting_list_nonce',
    'save_privacy' => 'osm_privacy_nonce',
    'save_public_data' => 'osm_public_data_nonce',
    'save_advanced_options' => 'osm_advanced_options_nonce',
    'purge_cache' => 'osm_purge_nonce',
    'reset_configuration' => 'osm_reset_nonce',
    'reset_auth' => 'osm_reset_auth_nonce',
];
foreach ( $actions as $method => $nonce ) {
    $before = $options;
    $checked_nonce = null;
    try { $admin->$method(); expect( false, 'unauthorized ' . $method . ' denied' ); }
    catch ( RuntimeException $error ) { expect( 'Access denied' === $error->getMessage(), 'unauthorized ' . $method . ' denied' ); }
    expect( null === $checked_nonce && $before === $options, 'capability checked before nonce/mutation for ' . $method );
}
$admin_allowed = true;
foreach ( $actions as $method => $nonce ) {
    $before = $options;
    $checked_nonce = null;
    try { $admin->$method(); expect( false, 'nonce gate ' . $method ); }
    catch ( RuntimeException $error ) { expect( 'Nonce gate reached' === $error->getMessage() && $nonce === $checked_nonce, 'correct nonce checked for ' . $method ); }
    expect( $before === $options, 'nonce precedes mutation for ' . $method );
}
$wpdb = new class {
    public $options = 'wp_options';
    public $query = '';
    public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
    public function prepare( $query, $value ) { return str_replace( '%s', "'" . $value . "'", $query ); }
    public function get_col( $query ) {
        $this->query = $query;
        return array_values( array_filter( array_keys( $GLOBALS['options'] ), function ( $key ) { return str_starts_with( $key, 'osm_cached_' ); } ) );
    }
};
OSM_Cache::set( 'old_account_programme', [ 'private' => 'old data' ] );
$options['osmXcachedYunrelated'] = 'keep';
OSM_Cache::clear_all();
expect( false === OSM_Cache::get( 'old_account_programme' ), 'old account cache invalidated through WordPress option API' );
expect( 'keep' === $options['osmXcachedYunrelated'], 'cache purge preserves unrelated wildcard-matching options' );
expect( str_contains( $wpdb->query, 'osm\\_cached\\_%' ) && str_starts_with( $wpdb->query, 'SELECT option_name' ), 'cache prefix escaped and option names selected for invalidation' );
$options['osm_cached_expired'] = [ 'value' => 'secret', 'expires' => time() - 1 ];
expect( false === OSM_Cache::get( 'expired' ) && ! array_key_exists( 'osm_cached_expired', $options ), 'expired cache removed instead of retained' );
/** Capture the standard WordPress Settings submenu registration. */
function add_options_page( ...$args ) { $GLOBALS['settings_registration'] = $args; }
$admin->add_admin_page();
expect( 'Online Scout Manager' === $settings_registration[1] && 'online-scout-manager' === $settings_registration[3], 'plugin registers under WordPress Settings with its full name' );
/**
 * Render one allowlisted server-side settings page without leaking output buffers.
 * @param OSM_Admin $admin Settings controller under the isolated capability fixture.
 * @param string $tab Requested page, including legacy aliases or unavailable pages.
 * @return string Captured administrator HTML.
 */
function render_admin_html( OSM_Admin $admin, string $tab ): string {
    $_GET['tab'] = $tab;
    ob_start();
    try { $admin->render_admin_page(); return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$options = [];
$before = count( $requests );
foreach ( [ 'overview', 'sections', 'section_points', 'waiting_list', 'notifications', 'advanced', 'authentication', 'documentation', 'general', 'configuration' ] as $tab ) {
    $html = render_admin_html( $admin, $tab );
    expect( str_contains( $html, 'id="osm-auth-title"' ) && substr_count( $html, 'aria-current="page"' ) === 1, 'unconfigured request shows Authentication only: ' . $tab );
    expect( ! str_contains( $html, 'id="osm-sections-title"' ) && ! str_contains( $html, 'osm_reset_auth' ), 'unconfigured pages cannot expose other controls: ' . $tab );
}
expect( $before === count( $requests ), 'unconfigured settings never call OSM' );
foreach ( explode( ' ', OSM_API::get_scopes() ) as $scope ) { expect( str_contains( $html, $scope ), 'authentication explains scope: ' . $scope ); }
expect( str_contains( $html, 'I am a developer' ) && str_contains( $html, 'displayed once only' ), 'detailed authentication instructions retained' );
$admin->add_privacy_policy_content();
expect( str_contains( $privacy_text, 'without storing application fields in WordPress' ), 'privacy guide retains application handling description' );
$options['osm_client_id'] = 'test-client';
$options['osm_client_secret'] = 'test-secret';
$options['osm_enabled_sections'] = [ 123 => 1 ];
$options['osm_public_patrol_sections'] = [ 123 => 1 ];
$options['osm_waiting_list_section_id'] = 789;
$html = render_admin_html( $admin, 'overview' );
foreach ( [ 'Overview', 'Sections', 'Section Points', 'Waiting List', 'Notifications', 'Advanced', 'Documentation' ] as $label ) { expect( str_contains( $html, '>' . $label . '</span>' ), 'authenticated navigation includes ' . $label ); }
expect( ! str_contains( $html, '>Authentication</span>' ) && ! str_contains( $html, 'osm_save_auth' ), 'authenticated navigation hides credentials' );
expect( str_contains( $html, 'osm-api-usage-title' ) && ! str_contains( $html, 'osm_save_sections' ), 'Overview contains usage and status rather than section controls' );
expect( str_contains( render_admin_html( $admin, 'authentication' ), 'id="osm-overview-title"' ), 'direct Authentication request after connection returns to Overview' );
$html = render_admin_html( $admin, 'sections' );
expect( str_contains( $html, 'osm_save_sections' ) && str_contains( $html, 'Show Section Points' ) && ! str_contains( $html, 'osm_firstname_display' ), 'Sections page owns the independent display switches' );
$html = render_admin_html( $admin, 'section_points' );
expect( str_contains( $html, 'osm-points-preview' ) && str_contains( $html, 'osm_excluded_members' ) && ! str_contains( $html, 'osm_save_waiting_list' ), 'Section Points keeps preview/exclusions separate from Waiting List' );
expect( str_contains( render_admin_html( $admin, 'public_data' ), 'id="osm-section-points"' ), 'legacy public-data alias opens Section Points after authentication' );
$html = render_admin_html( $admin, 'waiting_list' );
expect( str_contains( $html, 'value="789" selected="selected"' ) && str_contains( $html, 'osm_clear_waiting_list' ), 'Waiting List keeps its independent section selection and clear action' );
$html = render_admin_html( $admin, 'advanced' );
expect( str_contains( $html, 'osm_reset_auth' ) && str_contains( $html, 'osm_purge_cache' ) && str_contains( $html, 'osm_date_format' ), 'Advanced owns Reset Auth, maintenance and display formats' );
expect( str_contains( render_admin_html( $admin, 'documentation' ), '[osm_programme' ), 'Documentation owns shortcode guidance' );
OSM_API::clear_authorization();
$http_failure = new WP_Error();
$html = render_admin_html( $admin, 'advanced' );
expect( str_contains( $html, 'id="osm-auth-title"' ) && ! str_contains( $html, 'osm_reset_auth' ), 'failed credential verification gates navigation back to Authentication' );
$http_failure = null;
OSM_API::clear_authorization();
$stop_after_nonce = false;
/** Intercept the handler redirect before its exit. */
function wp_safe_redirect( $url ) { throw new RuntimeException( 'Redirect: ' . $url ); }
/** Store a synthetic notice without a database. */
function set_transient( $key, $value, $ttl ) { $GLOBALS['notices'][ $key ] = $value; }
$options['osm_section_points_settings'] = [ 456 => [ 'firstname' => 'initial', 'lastname' => 'initial', 'excluded_members' => [ 999 ] ] ];
$_POST = [ 'osm_enabled_sections' => [ 456 => '1', 9999 => '1' ], 'osm_public_patrol_sections' => [ 123 => '1', 9999 => '1' ] ];
try { $admin->save_sections(); } catch ( RuntimeException $error ) {}
expect( [ 456 => 1 ] === $options['osm_enabled_sections'] && [ 123 => 1 ] === $options['osm_public_patrol_sections'], 'section switches save independently and reject inaccessible IDs' );
expect( [999] === OSM_Options::get_excluded_members(456), 'changing section switches retains exclusions' );
$_POST = [ 'osm_section_id' => '123', 'osm_firstname_display' => 'full', 'osm_lastname_display' => 'initial', 'osm_excluded_members' => [ '1001' ], 'osm_other_excluded_members' => '7001, 7002' ];
try { $admin->save_section_points(); } catch ( RuntimeException $error ) {}
expect( [1001,7001,7002] === OSM_Options::get_excluded_members(123), 'member checkboxes and absent-roster exclusions saved together' );
expect( [999] === OSM_Options::get_excluded_members(456), 'saving one section preserves every other section policy' );
$before_settings = $options['osm_section_points_settings'];
$_POST['osm_section_id'] = '99999';
try { $admin->save_section_points(); } catch ( RuntimeException $error ) {}
expect( $before_settings === $options['osm_section_points_settings'], 'inaccessible section cannot change policies' );
$roster = OSM_API::get_section_members_for_admin(123);
expect( [ [ 'id' => 1001, 'name' => 'Private Child Fixture', 'firstname' => 'Private Child', 'lastname' => 'Fixture', 'patrol' => 'Eagles', 'points' => '12' ] ] === $roster, 'administrator roster exposes only identity and patrol labels needed for exclusions and preview' );
$admin_allowed = false;
$before = count($requests);
try { OSM_API::get_section_members_for_admin(123); expect(false,'unauthorised roster blocked'); }
catch (RuntimeException $error) { expect($before === count($requests),'member listing requires administrator capability before HTTP'); }
$admin_allowed = true;
$_GET['tab'] = 'section_points';
$http_failure = new WP_Error();
ob_start(); $admin->render_admin_page(); $html = ob_get_clean();
$http_failure = null;
expect( str_contains($html,'1001, 7001, 7002') && str_contains($html,'Members could not be loaded'), 'roster failure retains all saved exclusions in the editable fallback' );
expect( ! str_contains($html, '<details') && ! str_contains($html, '<summary'), 'buttons and configuration stay visible without folding controls' );
$test_users = [ 10 => (object) [ 'ID' => 10, 'display_name' => 'Test Leader', 'user_email' => 'leader@example.test' ] ];
$_POST = [ 'osm_notification_user_ids' => [ '10' ] ];
try { $admin->save_notifications(); } catch ( RuntimeException $error ) {}
expect( [10] === OSM_Notifications::get_recipient_ids(), 'only selected existing user IDs saved as notification recipients' );
$_POST = [ 'osm_notification_user_ids' => [ '9999' ] ];
try { $admin->save_notifications(); } catch ( RuntimeException $error ) {}
expect( [10] === OSM_Notifications::get_recipient_ids(), 'invalid user cannot replace notification settings' );
$_POST = [];
try { $admin->save_notifications(); } catch ( RuntimeException $error ) {}
expect( [] === OSM_Notifications::get_recipient_ids(), 'empty selection disables notifications' );
$previous_options = $options;
try { $admin->clear_waiting_list(); } catch ( RuntimeException $error ) {
    expect( str_contains( $error->getMessage(), 'waiting_list#osm-waiting-list' ), 'clear returns to the waiting-list tile' );
}
unset( $previous_options['osm_waiting_list_section_id'] );
expect( $previous_options === $options, 'clear removes only the selected section and preserves other settings' );
$before_reset = $options;
try { $admin->reset_auth(); } catch ( RuntimeException $error ) { expect( str_contains( $error->getMessage(), 'tab=authentication' ), 'Reset Auth returns to authentication' ); }
expect( ! isset( $options['osm_client_id'], $options['osm_client_secret'] ), 'Reset Auth removes credentials' );
expect( $before_reset['osm_section_points_settings'] === $options['osm_section_points_settings'], 'Reset Auth retains name formats and exclusions' );
expect( str_contains( render_admin_html( $admin, 'sections' ), 'id="osm-auth-title"' ), 'Reset Auth immediately restores the authentication-only state' );
echo "Passed {$passed} total checks including admin authorization and cache isolation.\n";
