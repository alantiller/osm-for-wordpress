<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" aria-labelledby="osm-auth-title">
				<h2 id="osm-auth-title">Authentication</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_auth' ) ); ?>" autocomplete="off">
					<?php wp_nonce_field( 'osm_auth_nonce' ); ?>
					<p><label for="osm_client_id">Client ID</label><input type="text" id="osm_client_id" name="osm_client_id" value="" autocomplete="off" class="regular-text" placeholder="<?php echo esc_attr( $client_id ? 'Configured; leave blank to keep' : '' ); ?>"></p>
					<p><label for="osm_client_secret">Client Secret</label><input type="password" id="osm_client_secret" name="osm_client_secret" value="" autocomplete="new-password" class="regular-text" placeholder="<?php echo esc_attr( $client_secret ? 'Configured; leave blank to keep' : '' ); ?>"></p>
					<?php submit_button( 'Save & Authenticate' ); ?>
				</form>
				<h2 id="osm-auth-help-title">OSM application setup</h2>
                <ol>
                    <li>Log in to <a href="https://www.onlinescoutmanager.co.uk/" target="_blank" rel="noopener noreferrer">Online Scout Manager</a>.</li>
                    <li>Expand <strong>Settings</strong> at the bottom of the menu.</li>
                    <li>Select <strong>My Account Details</strong>.</li>
                    <li>Click <strong>Developer Tools</strong> on the left-hand side.</li>
                    <li>Click <strong>Create Application</strong>.</li>
                    <li>Provide a name for your application and click <strong>Save</strong>.</li>
                    <li>Type <strong>I am a developer</strong> into the Confirmation field and click <strong>Reveal Credentials</strong>.</li>
                    <li>Your <strong>Client ID</strong> and <strong>Client Secret</strong> are displayed once only. Make a secure note of both.</li>
                    <li>Close the window, then click <strong>Edit</strong> on the application you created.</li>
                    <li>Tick <strong>Client Credentials Grant</strong> and click <strong>Save</strong>.</li>
                    <li>Return to this page and paste your Client ID and Client Secret into the fields above.</li>
                    <li>Click <strong>Save &amp; Authenticate</strong>, then choose your website sections on Sections and your waiting-list section on Waiting List.</li>
                </ol>
                <p>The plugin requests all four scopes by default using your Client ID and Client Secret:</p>
                <ul><?php foreach ( explode( ' ', OSM_API::get_scopes() ) as $scope ): ?><li><code><?php echo esc_html( $scope ); ?></code></li><?php endforeach; ?></ul>
                <p>These allow programme and event reads, patrol/member reads and waiting-list member creation. Your OSM application must have access to the sections you select.</p>
			</section>
