<?php
declare(strict_types=1);
/** Verify observed quota capture and failure handling without real OSM requests. */
require __DIR__ . '/run.php';
expect( null === OSM_API::get_api_usage(), 'quota unavailable before complete response headers' );
$quota_headers = [ 'x-ratelimit-limit' => '1000', 'x-ratelimit-remaining' => '975', 'x-ratelimit-reset' => '120' ];
$quota_status = 200;
$request_responder = static function ( $url, $args ) {
    return [ 'body' => json_encode( [ 'access_token' => 'quota-fixture-token', 'expires_in' => 3600 ] ), 'response' => [ 'code' => $GLOBALS['quota_status'] ], 'headers' => $GLOBALS['quota_headers'] ];
};
$before = time();
OSM_API::authorize( true );
$usage = OSM_API::get_api_usage();
expect( 25 === $usage['used'] && 975 === $usage['remaining'] && 1000 === $usage['limit'], 'used and remaining derive from OSM quota rather than a local counter' );
expect( $usage['reset_at'] >= $before + 120 && $usage['reset_at'] <= time() + 120, 'OSM relative reset seconds become an absolute deadline' );
$before_requests = count( $requests );
OSM_API::authorize();
expect( $before_requests === count( $requests ) && $usage === OSM_API::get_api_usage(), 'reading usage and reusing cached tokens consume no requests' );
foreach ( [ [], [ 'x-ratelimit-limit' => 'bad' ], [ 'x-ratelimit-limit' => '10', 'x-ratelimit-remaining' => '11', 'x-ratelimit-reset' => '120' ], [ 'x-ratelimit-limit' => '10', 'x-ratelimit-remaining' => '1', 'x-ratelimit-reset' => '-1' ] ] as $quota_headers ) {
    OSM_API::authorize( true );
    expect( $usage === OSM_API::get_api_usage(), 'missing or malformed quota headers preserve last valid observation' );
}
$quota_headers = [ 'x-ratelimit-limit' => '1000', 'x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => '30' ];
$quota_status = 429;
try { OSM_API::authorize( true ); expect( false, 'HTTP 429 must fail authentication' ); }
catch ( Exception $error ) { expect( 0 === OSM_API::get_api_usage()['remaining'], 'quota captured on rate-limited error responses too' ); }
$options['osm_api_usage']['reset_at'] = time() - 1;
expect( 1000 === OSM_API::get_api_usage()['used'], 'expired observation never invents a fresh unused quota' );
echo "Passed {$passed} total checks including API usage.\n";
