<?php
/** Dependency-free regression tests; no account credentials or network requests. */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'ABSPATH', __DIR__ );
define( 'OSM_PLUGIN_DIR', dirname( __DIR__ ) . '/src/' );
define( 'OSM_PLUGIN_URI', 'https://example.test/wp-content/plugins/online-scout-manager/' );
define( 'OSM_ASSETS_URI', OSM_PLUGIN_URI . 'assets' );
define( 'OSM_TEMPLATES_DIR', OSM_PLUGIN_DIR . 'templates' );
define( 'OSM_PLUGIN_VERSION', '1.1.1' );
$options = [];
$requests = [];
$hooks = [];
$registered_blocks = [];
$scripts = [];
$fail_section = null;
$http_failure = null;
$fixtures = [
    [ 'sectionid' => 123, 'groupname' => 'Test Group', 'sectionname' => 'Scouts <safe>', 'section' => 'scouts' ],
    [ 'sectionid' => 456, 'groupname' => 'Test Group', 'sectionname' => 'Cubs', 'section' => 'cubs' ],
    [ 'sectionid' => 789, 'groupname' => 'Test Group', 'sectionname' => 'Private section', 'section' => 'scouts' ],
];
function add_shortcode( $tag, $callback ) { $GLOBALS['hooks'][ $tag ] = $callback; }
function add_action( $tag, $callback, ...$args ) { $GLOBALS['hooks'][ $tag ][] = $callback; $GLOBALS['hook_arguments'][ $tag ][] = $args; }
function add_filter( $tag, $callback, ...$args ) { add_action( $tag, $callback ); }
/** Apply synthetic WordPress filters without any external callbacks. */
function apply_filters( $tag, $value, ...$args ) { foreach ( $GLOBALS['hooks'][ $tag ] ?? [] as $callback ) { $value = $callback( $value, ...$args ); } return $value; }

function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, ...$args ) { $GLOBALS['options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function current_time( $format ) { return 'Y-m-d' === $format ? '2026-10-08' : strtotime( '2026-10-08 12:00:00 UTC' ); }
function wp_timezone() { return new DateTimeZone( 'Europe/London' ); }
function wp_date( $format, $timestamp, $timezone = null ) { return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone ?: wp_timezone() )->format( $format ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_textarea( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function __( $value, $domain = '' ) { return $value; }
/** Preserve multiline synthetic notes while removing tags. */
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
/** Model account lookup using synthetic users only. */
function get_userdata( $id ) { return $GLOBALS['test_users'][ (int) $id ] ?? false; }
/** Capture mail in memory; never send from automated tests. */
function wp_mail( $to, $subject, $message ) { $GLOBALS['test_mail'][] = compact( 'to', 'subject', 'message' ); return $GLOBALS['test_mail_result'] ?? true; }
/** Validate synthetic email addresses. */
function is_email( $email ) { return is_string( $email ) && false !== filter_var( $email, FILTER_VALIDATE_EMAIL ); }
/** Render selected options like WordPress. */
function selected( $selected, $current = true, $echo = true ) {
    $value = (string) $selected === (string) $current ? 'selected="selected"' : '';
    if ( $echo ) { echo $value; }
    return $value;
}
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_url_raw( $url, ...$args ) { return $url; }
function wp_http_validate_url( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ); }
function absint( $value ) { return abs( (int) $value ); }
function is_wp_error( $response ) { return $response instanceof WP_Error; }
class WP_Error { public function get_error_message() { return 'Network unavailable'; } }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][ $header ] ?? ''; }
function wp_remote_request( $url, $args ) {
    $GLOBALS['requests'][] = [ 'url' => $url, 'args' => $args ];
    if ( isset( $GLOBALS['request_responder'] ) ) { return ( $GLOBALS['request_responder'] )( $url, $args ); }
    if ( $GLOBALS['http_failure'] ) { return $GLOBALS['http_failure']; }
    parse_str( parse_url( $url, PHP_URL_QUERY ) ?: '', $query );
    $id = (int) ( $query['sectionid'] ?? 0 );
    if ( $GLOBALS['fail_section'] === $id ) {
        return [ 'body' => json_encode( [ 'error' => 'secret member@example.test' ] ), 'response' => [ 'code' => 403 ], 'headers' => [] ];
    }
    if ( str_contains( $url, '/oauth/token' ) ) {
        $data = [ 'access_token' => 'fake-token', 'expires_in' => 3600 ];
    } elseif ( 'getUserRoles' === ( $query['action'] ?? '' ) ) {
        $data = $GLOBALS['fixtures'];
    } elseif ( 'getTerms' === ( $query['action'] ?? '' ) ) {
        $data = [];
        foreach ( array_column( $GLOBALS['fixtures'], 'sectionid' ) as $section ) { $data[ $section ] = [ [ 'termid' => $section + 1000, 'startdate' => '2000-01-01', 'enddate' => '2099-12-31' ] ]; }
    } elseif ( 'getProgramme' === ( $query['action'] ?? '' ) ) {
        $data = [ 'items' => [
            [ 'meetingdate' => '2026-10-08', 'title' => 'Meeting ' . $id, 'notesforparents' => '<script>alert(1)</script>' ],
            [ 'meetingdate' => '2026-10-07', 'title' => 'Past meeting', 'notesforparents' => '' ],
            [ 'meetingdate' => 'bad date', 'title' => 'Undated meeting' ],
        ] ];
    } elseif ( str_contains( $url, '/ext/events/summary/' ) ) {
        $data = [ 'items' => [
            [ 'startdate' => '08/10/2026', 'enddate' => '08/10/2026', 'starttime' => '19:00', 'endtime' => '20:30', 'name' => 'Tonight', 'location' => 'Hall' ],
            [ 'startdate_g' => '2026-10-07', 'startdate' => '07/10/2026', 'name' => 'Yesterday' ],
            [ 'startdate' => 'broken', 'name' => 'No date' ],
        ] ];
    } elseif ( 'getPatrolsWithPeople' === ( $query['action'] ?? '' ) ) {
        $data = [ 12001 => [ 'patrolid' => 12001, 'name' => 'Eagles', 'points' => '12', 'members' => [ [ 'scoutid' => 1001, 'firstname' => 'Private Child', 'lastname' => 'Fixture', 'email' => 'private@example.test' ] ] ], 12002 => [ 'patrolid' => 12002, 'name' => '<b>Owls</b>', 'points' => '0', 'members' => [] ], -2 => [ 'patrolid' => -2, 'name' => 'Leaders', 'members' => [ [ 'firstname' => 'Private Leader' ] ] ] ];
    } else {
        throw new RuntimeException( 'Unexpected mock request: ' . $url );
    }
    return [ 'body' => json_encode( $data ), 'response' => [ 'code' => 200 ], 'headers' => [] ];
}
function wp_register_script( $handle, $url, $dependencies = [], $version = null, ...$args ) { $GLOBALS['scripts'][ $handle ] = compact( 'url', 'dependencies', 'version' ); }
function wp_enqueue_script( ...$args ) {}
function wp_register_style( ...$args ) {}
function wp_enqueue_style( ...$args ) {}
function wp_set_script_translations( ...$args ) {}
function nocache_headers() {}
function get_privacy_policy_url() { return $GLOBALS['test_privacy_url'] ?? 'https://example.test/privacy'; }
function register_block_type( $path, ...$args ) { $GLOBALS['registered_blocks'][] = $path; }
function get_block_wrapper_attributes( $attrs = [] ) { return 'class="' . esc_attr( $attrs['class'] ?? '' ) . '"'; }

/** Offline KSES boundary stub; real allowlist behaviour is checked against WordPress integration tests. */
function wp_kses( $html, $allowed ) { return $html; }
function wp_kses_data( $html ) { return $html; }
function wp_kses_allowed_html( $context ) { return []; }
function map_deep( $value, $callback ) { if ( is_array( $value ) ) { foreach ( $value as &$item ) { $item = map_deep( $item, $callback ); } return $value; } return $callback( $value ); }
foreach ( [ 'cache', 'options', 'api', 'shortcodes', 'waiting-list', 'notifications', 'output', 'blocks' ] as $class ) {
    require OSM_PLUGIN_DIR . 'includes/class-osm-' . $class . '.php';
}
$passed = 0;
function expect( $condition, $description ) {
    if ( ! $condition ) { throw new RuntimeException( 'FAIL: ' . $description ); }
    $GLOBALS['passed']++;
}
$options['osm_client_id'] = 'test-client';
$options['osm_client_secret'] = 'test-secret';
$options['osm_enabled_sections'] = [ 123 => 1, 456 => 1 ];
$renderer = new OSM_Shortcodes();
expect( count( $hooks ) === 4, 'four shortcodes registered' );
expect( [ 123, 456 ] === OSM_Shortcodes::section_ids( [ 'sectionids' => '123, 456,123' ] ), 'multi-section IDs deduplicated' );
expect( [ 123 ] === OSM_Shortcodes::section_ids( [ 'sectionid' => '123' ] ), 'legacy sectionid preserved' );
expect( [ 123, 456 ] === OSM_Shortcodes::section_ids( [ 'sectionids' => [ '123', 456 ] ] ), 'array attributes accepted' );
foreach ( [ '', '0', '-1', '1e3', '123,', '123][osm_events', '9223372036854775808', [ [ 123 ] ] ] as $invalid ) {
    try { OSM_Shortcodes::section_ids( [ 'sectionids' => $invalid ] ); expect( false, 'invalid IDs rejected' ); }
    catch ( InvalidArgumentException $error ) { expect( true, 'invalid IDs rejected' ); }
}
expect( false === OSM_Shortcodes::parse_date( '31/02/2026' ), 'impossible OSM date rejected' );
expect( '' === OSM_Shortcodes::format_time( 'bad', 'H:i' ), 'invalid time renders safely' );
expect( '19:00' === OSM_Shortcodes::format_time( '19:00:00', 'H:i' ), 'OSM time formats accepted' );
$html = $renderer->render_programme( [ 'sectionids' => '123,456', 'futureonly' => true ] );
expect( str_contains( $html, 'Meeting 123' ) && str_contains( $html, 'Meeting 456' ), 'both sections rendered' );
expect( ! str_contains( $html, '<safe>' ) && substr_count( $html, '<section ' ) === 2, 'multi-section headings safe and grouped' );
expect( ! str_contains( $html, 'Past meeting' ) && ! str_contains( $html, 'Undated meeting' ), 'future programme filter uses local date and rejects undated rows' );
expect( ! str_contains( $html, '<script>' ), 'programme summary escaped' );
$html = $renderer->render_events( [ 'sectionid' => '123', 'futureonly' => 'true' ] );
expect( str_contains( $html, 'Tonight' ) && str_contains( $html, '08 Oct 2026' ) && str_contains( $html, '19:00' ), 'UK OSM dates and times parsed correctly' );
expect( ! str_contains( $html, 'Yesterday' ) && ! str_contains( $html, 'No date' ), 'future event filter handles UK and ISO dates' );
$count = count( $requests );
$mixed_html = $renderer->render_programme( [ 'sectionids' => '123,789' ] );
expect( str_contains( $mixed_html, 'Meeting 123' ) && ! str_contains( $mixed_html, 'Private section' ), 'disabled section does not suppress enabled feeds or reveal disabled names' );
expect( $count === count( $requests ), 'disabled selection rejected before API requests' );
$renderer->render_patrols( [ 'sectionid' => 123 ] );
expect( $count === count( $requests ), 'patrol publication disabled by default' );
$options['osm_public_patrol_sections'] = [ 123 => 1 ];
$patrols = OSM_API::get_patrols( 123 );
expect( [ [ 'name' => 'Eagles', 'points' => '12' ], [ 'name' => 'Owls', 'points' => '0' ] ] === $patrols, 'patrol API returns sanitized names and current points' );
$html = $renderer->render_patrols( [ 'sectionid' => 123 ] );
expect( str_contains( $html, 'Eagles' ) && ! str_contains( $html, 'Private Child' ) && ! str_contains( $html, 'private@example.test' ), 'member details excluded from output' );
expect( ! str_contains( $html, '<b>Owls</b>' ), 'patrol names escaped' );
expect( str_contains( $html, '12 points' ) && str_contains( $html, '0 points' ) && str_contains( $html, 'osm-patrol-heading' ), 'patrol cards display real scores including zero beside their names' );
foreach ( [ [ '-3', '-3' ], [ '2.5', '2.5' ], [ null, null ], [ '<script>99</script>', null ], [ [], null ] ] as [ $fixture_points, $expected_points ] ) {
    $request_responder = static function ( $url, $args ) use ( $fixture_points ) {
        return [ 'body' => json_encode( [ 12001 => [ 'patrolid' => 12001, 'name' => 'Eagles', 'points' => $fixture_points, 'members' => [] ] ] ), 'response' => [ 'code' => 200 ], 'headers' => [] ];
    };
    expect( $expected_points === OSM_API::get_patrols( 123 )[0]['points'], 'malformed scores are withheld while real negative/decimal totals remain accurate' );
    if ( null === $expected_points ) { expect( str_contains( $renderer->render_patrols( [ 'sectionid' => 123 ] ), 'Points unavailable' ), 'absent or malformed points never become an invented zero' ); }
}
unset( $request_responder );
foreach ( [
    [ 'full', 'full', 'Private Child Fixture' ], [ 'full', 'hidden', 'Private Child' ],
    [ 'hidden', 'full', 'Fixture' ], [ 'initial', 'initial', 'P. F.' ],
    [ 'full', 'initial', 'Private Child F.' ], [ 'initial', 'full', 'P. Fixture' ],
] as [ $first_mode, $last_mode, $expected_name ] ) {
    OSM_Options::set_name_display( $first_mode, $last_mode );
    $roster = OSM_API::get_patrols( 123 );
    expect( [ $expected_name ] === $roster[0]['members'], 'patrol API applies configured first/last name formats before returning any member data' );
    expect( ! str_contains( json_encode( $roster ), 'private@example.test' ) && ! str_contains( json_encode( $roster ), 'Private Leader' ), 'formatted rosters exclude contacts and leader pseudo-patrols' );
}
OSM_Options::set_name_display( 'hidden', 'hidden' );
expect( ! isset( OSM_API::get_patrols( 123 )[0]['members'] ), 'both names hidden returns patrol names only' );
OSM_Options::set_section_points( 123, 'initial', 'full', [] );
OSM_Options::set_section_points( 456, 'full', 'initial', [] );
expect( 'P. Fixture' === OSM_API::get_patrols( 123 )[0]['members'][0], 'first section applies its own name formats' );
$options['osm_public_patrol_sections'][456] = 1;
expect( 'Private Child F.' === OSM_API::get_patrols( 456 )[0]['members'][0], 'second section applies independent name formats' );
OSM_Options::set_section_points( 123, 'full', 'full', [ 1001 ] );
expect( ! isset( OSM_API::get_patrols( 123 )[0]['members'] ), 'excluded member omitted before public API output' );
$request_responder = static function ( $url, $args ) {
    return [ 'body' => json_encode( [ [ 'patrolid' => 12001, 'name' => 'Eagles', 'points' => '12', 'members' => [ [ 'firstname' => 'Unidentified', 'lastname' => 'Member' ], [ 'scoutid' => 1001, 'firstname' => 'Excluded', 'lastname' => 'Member' ] ] ] ] ), 'response' => [ 'code' => 200 ], 'headers' => [] ];
};
expect( ! isset( OSM_API::get_patrols( 123 )[0]['members'] ), 'missing member IDs cannot bypass configured exclusions' );
unset( $request_responder );
expect( ! str_contains( $renderer->render_patrols( [ 'sectionids' => '123,456' ] ), 'Private Child Fixture' ), 'multi-section shortcode applies exclusions and section policies' );
expect( ! str_contains( OSM_Blocks::render( 'patrols', [ 'sectionids' => '123' ] ), 'Private Child' ), 'Gutenberg applies the same exclusions' );
$before_settings = $options['osm_section_points_settings'];
try { OSM_Options::set_section_points( 123, 'full', 'full', [ 'bad-id' ] ); expect( false, 'malformed exclusion rejected' ); }
catch ( InvalidArgumentException $error ) { expect( $before_settings === $options['osm_section_points_settings'], 'invalid exclusion cannot partially save settings' ); }
unset( $options['osm_enabled_sections'][123] );
expect( str_contains( $renderer->render_patrols( [ 'sectionids' => '123' ] ), 'Eagles' ), 'Section Points works with programme/events disabled' );
expect( ! str_contains( $renderer->render_programme( [ 'sectionids' => '123' ] ), 'Meeting 123' ), 'programme disabled independently from Section Points' );
$options['osm_enabled_sections'][123] = 1;
unset( $options['osm_public_patrol_sections'][123] );
expect( ! str_contains( $renderer->render_patrols( [ 'sectionids' => '123' ] ), 'Eagles' ), 'disabled Section Points cannot publish through shortcode' );
expect( str_contains( $renderer->render_programme( [ 'sectionids' => '123' ] ), 'Meeting 123' ), 'programme remains available when Section Points disabled' );
$options['osm_public_patrol_sections'][123] = 1;
// Restore legacy-default fixtures for downstream suites.
unset( $options['osm_section_points_settings'] );
$token_scopes = [];
foreach ( $requests as $request ) {
    if ( str_contains( $request['url'], '/oauth/token' ) ) {
        parse_str( $request['args']['body'], $body );
        expect( 'client_credentials' === $body['grant_type'], 'server-side OAuth uses client credentials' );
        $token_scopes[] = $body['scope'];
    }
}
expect( 'section:programme:read section:event:read section:member:read section:member:write' === OSM_API::get_scopes(), 'all four scopes are requested by default' );
expect( [ OSM_API::get_scopes() ] === array_values( array_unique( $token_scopes ) ), 'all operations share the same four scopes without duplicates' );
$before = count( $requests );
OSM_API::authorize( false, 'section:member:read' );
OSM_API::authorize( false, 'section:member:write' );
expect( $before === count( $requests ), 'legacy scope arguments reuse the shared token' );
try { OSM_API::authorize( false, 'section:unknown:write' ); expect( false, 'unsupported scope rejected' ); }
catch ( Exception $error ) { expect( true, 'unsupported scope rejected' ); }
foreach ( $options as $key => $value ) {
    if ( str_starts_with( $key, 'osm_cached_' ) ) {
        expect( ! str_contains( json_encode( $value ), 'Private Child' ), 'member details never cached' );
    }
}
OSM_Cache::delete( 'programme_456_1456' );
$fail_section = 456;
$html = $renderer->render_programme( [ 'sectionids' => '123,456' ] );
expect( str_contains( $html, 'Meeting 123' ) && str_contains( $html, 'currently unavailable' ), 'one section failure preserves other section' );
expect( ! str_contains( $html, 'member@example.test' ), 'raw API error withheld from public' );
$fail_section = null;
foreach ( [
    new WP_Error(),
    [ 'body' => 'private@example.test', 'response' => [ 'code' => 302 ], 'headers' => [] ],
    [ 'body' => 'private@example.test', 'response' => [ 'code' => 401 ], 'headers' => [] ],
    [ 'body' => 'private@example.test', 'response' => [ 'code' => 429 ], 'headers' => [] ],
    [ 'body' => '<html>private@example.test</html>', 'response' => [ 'code' => 200 ], 'headers' => [] ],
    [ 'body' => 'null', 'response' => [ 'code' => 200 ], 'headers' => [] ],
    [ 'body' => '{"error":"private@example.test"}', 'response' => [ 'code' => 200 ], 'headers' => [] ],
] as $failure ) {
    $http_failure = $failure;
    try { OSM_API::get_programme( 123, 9999 ); expect( false, 'API failure rejected' ); }
    catch ( Exception $error ) { expect( ! str_contains( $error->getMessage(), 'private@example.test' ), 'transport and API failures sanitized' ); }
    expect( false === OSM_Cache::get( 'programme_123_9999' ), 'failed API requests never cached' );
}
$http_failure = null;
expect( str_contains( $renderer->render_waiting_list( [] ), 'currently unavailable' ), 'waiting list fails safely until its destination and connection are configured' );
$blocks = new OSM_Blocks( $renderer );
$blocks->register_blocks();
expect( count( $registered_blocks ) === 4, 'four dynamic blocks registered' );
foreach ( $registered_blocks as $path ) {
    $metadata = json_decode( file_get_contents( $path . '/block.json' ), true, 512, JSON_THROW_ON_ERROR );
    expect( isset( $metadata['attributes']['sectionid'], $metadata['attributes']['sectionids'] ), 'saved single-section and new multiple-section blocks supported' );
    expect( is_file( $path . '/render.php' ), 'block render asset shipped' );
}
expect( str_contains( OSM_Blocks::render( 'programme', [ 'sectionids' => '123,456' ] ), 'Meeting 456' ), 'block shares multi-section renderer' );
$attributes = [ 'sectionid' => '123' ];
ob_start();
include OSM_PLUGIN_DIR . 'build/blocks/programme/render.php';
include OSM_PLUGIN_DIR . 'build/blocks/programme/render.php';
$html = ob_get_clean();
expect( substr_count( $html, 'Meeting 123' ) === 2, 'repeated dynamic blocks render without redeclaring PHP functions' );
$buffer_level = ob_get_level();
OSM_Cache::set( 'programme_123_1123', [ 'items' => [ [ 'meetingdate' => [], 'title' => [ 'unexpected' ], 'notesforparents' => [] ] ] ] );
$html = $renderer->render_programme( [ 'sectionid' => '123' ] );
expect( $buffer_level === ob_get_level() && str_contains( $html, '</table>' ), 'malformed cached text/date fields render without leaking output buffers' );
// Exercise the reported legacy comma-separated attribute with distinct section terms.
$original_fixtures = $fixtures;
$original_enabled = $options['osm_enabled_sections'];
$fixtures[] = [ 'sectionid' => 14648, 'groupname' => 'Example group', 'sectionname' => 'First section', 'section' => 'scouts' ];
$fixtures[] = [ 'sectionid' => 6569, 'groupname' => 'Example group', 'sectionname' => 'Second section', 'section' => 'cubs' ];
$options['osm_enabled_sections'] = [ 14648 => 1, 6569 => 1 ];
OSM_Cache::delete( 'osm_sections' );
foreach ( [ 'table', 'tiles', 'grid', 'vertical' ] as $layout ) {
    $html = $renderer->render_programme( [ 'sectionid' => '14648,6569', 'futureonly' => 'true', 'layout' => $layout ] );
    expect( str_contains( $html, 'Meeting 14648' ) && str_contains( $html, 'Meeting 6569' ), 'reported legacy selection renders both sections in ' . $layout );
    expect( ! str_contains( $html, 'Past meeting' ) && ! str_contains( $html, '<script>' ), 'both layouts apply date and escaping policies' );
    $html = $renderer->render_events( [ 'sectionid' => '14648,6569', 'futureonly' => 'true', 'layout' => $layout ] );
    expect( substr_count( $html, 'Tonight' ) === 2 && ! str_contains( $html, 'Yesterday' ), 'events render and filter both selected sections in ' . $layout );
}
foreach ( [ 'programme', 'events' ] as $kind ) {
    $method = 'render_' . $kind;
    foreach ( [ 'tiles' => 'grid', 'grid' => 'grid', 'vertical' => 'vertical' ] as $requested => $expected ) {
        $html = $renderer->$method( [ 'sectionid' => '14648', 'layout' => $requested, 'combine' => true ] );
        expect( str_contains( $html, 'osm-activity-tiles--' . $expected ) && ! str_contains( $html, '<table' ), 'explicit and legacy tile layouts render cards rather than tables, even with combine enabled' );
    }
}
foreach ( [ 14648, 6569 ] as $section_id ) {
    $matching_requests = array_filter( $requests, static fn( $request ) => str_contains( $request['url'], 'sectionid=' . $section_id . '&termid=' . ( $section_id + 1000 ) ) );
    expect( count( $matching_requests ) === 2, 'programme and events use the selected section own term' );
}
foreach ( [ 'programme' => 3, 'events' => 4 ] as $kind => $columns ) {
    $method = 'render_' . $kind;
    $combined = $renderer->$method( [ 'sectionid' => '14648,6569', 'futureonly' => true, 'combine' => 'true' ] );
    expect( substr_count( $combined, '<table' ) === 1 && substr_count( $combined, 'scope="rowgroup" colspan="' . $columns . '"' ) === 2, 'combined ' . $kind . ' shares column headings and labels each section' );
    expect( str_contains( $combined, 'First section' ) && str_contains( $combined, 'Second section' ), 'combined tables retain section identities' );
    expect( ! str_contains( $combined, '<script>' ) && ! str_contains( $combined, 'Yesterday' ) && ! str_contains( $combined, 'Past meeting' ), 'combined tables preserve escaping and future filtering' );
    $tiles = $renderer->$method( [ 'sectionid' => '14648,6569', 'combine' => true, 'layout' => 'tiles' ] );
    expect( ! str_contains( $tiles, '<table' ) && str_contains( $tiles, 'osm-activity-tiles' ), 'combine does not override tile layouts' );
}
$combined = $renderer->render_programme( [ 'sectionid' => '14648,789', 'combine' => true ] );
expect( str_contains( $combined, 'Meeting 14648' ) && str_contains( $combined, 'currently unavailable' ) && ! str_contains( $combined, 'Private section' ), 'combined table preserves successful feeds without disclosing disabled names' );
OSM_Cache::set( 'programme_6569_7569', [ 'items' => [ [ 'meetingdate' => '2026-10-07', 'title' => 'Past-only second section' ] ] ] );
$html = $renderer->render_programme( [ 'sectionid' => '14648,6569', 'futureonly' => 'true' ] );
expect( str_contains( $renderer->render_programme( [ 'sectionid' => '14648,6569', 'futureonly' => true, 'combine' => true ] ), 'No upcoming activities' ), 'combined table includes empty-section messages' );
expect( str_contains( $html, 'Meeting 14648' ) && str_contains( $html, 'No upcoming activities' ), 'past-only second section does not hide the first upcoming programme' );
expect( str_contains( $renderer->render_programme( [ 'sectionid' => '14648,6569', 'futureonly' => 'false' ] ), 'Past-only second section' ), 'disabling the future filter reveals past activities in the same selection' );
add_filter( 'osm_display_layout', static fn( $layout, $type, $atts ) => 'tiles' );
expect( str_contains( $renderer->render_programme( [ 'sectionid' => '14648' ] ), 'osm-activity-tiles' ), 'theme filter can select tiles by default' );
expect( str_contains( $renderer->render_programme( [ 'sectionid' => '14648', 'layout' => 'table' ] ), '<table' ), 'explicit layout overrides the theme default' );
expect( ! str_contains( $renderer->render_programme( [ 'sectionid' => '14648', 'layout' => '../events' ] ), '<table' ), 'invalid layouts cannot select arbitrary templates' );
unset( $hooks['osm_display_layout'] );
OSM_Cache::delete( 'current_term_6569' );
$fixtures = array_filter( $fixtures, static fn( $section ) => $section['sectionid'] !== 6569 );
$html = $renderer->render_programme( [ 'sectionid' => '14648,6569' ] );
expect( str_contains( $html, 'Meeting 14648' ) && str_contains( $html, 'No current OSM term' ), 'a missing term is explained without hiding a successful section' );
$fixtures = $original_fixtures;
$options['osm_enabled_sections'] = $original_enabled;
OSM_Cache::delete( 'osm_sections' );
OSM_API::get_sections();
echo "Passed {$passed} regression checks.\n";
