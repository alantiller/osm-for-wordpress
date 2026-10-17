<?php
/** Native WordPress settings tiles; variables are supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap osm-admin">
    <h1>Online Scout Manager</h1>
    <?php if ( ! $client_id || ! $client_secret ): ?><div class="notice notice-warning"><p>Connect your OSM application in Authentication to load your sections.</p></div><?php endif; ?>
    <?php if ( $api_error ): ?><div class="notice notice-error"><p><?php echo esc_html( $api_error ); ?> Your saved settings have been kept.</p></div><?php endif; ?>
    <nav class="nav-tab-wrapper" aria-label="OSM settings">
        <?php foreach ( [ 'general' => 'General', 'configuration' => 'Configuration', 'authentication' => 'Authentication' ] as $tab => $label ): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=online-scout-manager&tab=' . $tab ) ); ?>" class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>" <?php if ( $active_tab === $tab ): ?>aria-current="page"<?php endif; ?>><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="osm-settings-columns osm-settings-columns--<?php echo esc_attr( $active_tab ); ?>">
    <?php if ( 'general' === $active_tab ): ?>
        <div class="osm-settings-column">
            <section class="postbox osm-settings-tile" aria-labelledby="osm-sections-title">
                <h2 id="osm-sections-title">Sections</h2>
                <p>Choose programme/events and Section Points independently for each section.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_sections' ) ); ?>">
                    <?php wp_nonce_field( 'osm_sections_nonce' ); ?>
                    <div class="osm-section-tiles">
                        <?php foreach ( $sections as $section_id => $section ): $section_enabled = ! empty( $enabled_sections[ $section_id ] ); ?>
                            <section class="postbox osm-settings-tile osm-section-tile" aria-labelledby="osm-section-title-<?php echo esc_attr( $section_id ); ?>">
                                <h3 id="osm-section-title-<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $section['sectionname'] ); ?></h3>
                                <p class="osm-section-meta"><?php echo esc_html( $section['groupname'] ); ?></p>
                                <table class="widefat striped osm-section-rows"><tbody>
                                    <tr><th scope="row"><label for="osm-programme-<?php echo esc_attr( $section_id ); ?>">Show Programme and Events</label></th><td><input id="osm-programme-<?php echo esc_attr( $section_id ); ?>" type="checkbox" name="osm_enabled_sections[<?php echo esc_attr( $section_id ); ?>]" value="1" <?php checked( $section_enabled ); ?>></td></tr>
                                    <tr><th scope="row"><label for="osm-points-<?php echo esc_attr( $section_id ); ?>">Show Section Points</label></th><td><input id="osm-points-<?php echo esc_attr( $section_id ); ?>" type="checkbox" name="osm_public_patrol_sections[<?php echo esc_attr( $section_id ); ?>]" value="1" <?php checked( ! empty( $public_patrol_sections[ $section_id ] ) ); ?>></td></tr>
                                    <tr><th scope="row">Section ID</th><td><code><?php echo esc_html( $section_id ); ?></code></td></tr>
                                    <?php if ( $section_enabled || ! empty( $public_patrol_sections[ $section_id ] ) ): ?><tr><th scope="row">Current term</th><td><?php echo esc_html( $current_terms[ $section_id ] ?? 'Unavailable' ); ?></td></tr><?php endif; ?>
                                </tbody></table>
                            </section>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( $sections ): submit_button( 'Save sections' ); else: ?><p>No sections are available. Check authentication and your OSM application’s section access.</p><?php endif; ?>
                </form>
            </section>
            <section class="postbox osm-settings-tile" id="osm-shortcodes" aria-labelledby="osm-shortcodes-title">
                <h2 id="osm-shortcodes-title">Shortcode documentation</h2>
                <h3>Programme</h3><p><code>[osm_programme sectionids="12345,67890" futureonly="true"]</code></p>
                <h3>Events</h3><p><code>[osm_events sectionids="12345,67890" futureonly="true"]</code></p>
                <h3>Section Points</h3><p><code>[osm_patrols sectionids="12345,67890"]</code></p><p>Patrols and member-name formats follow the Section Points settings.</p>
                <h3>Waiting-list application</h3><p><code>[osm_waiting_list]</code></p><p>Uses the section selected in Configuration. No section IDs are required.</p>
                <p class="description"><code>sectionid="12345"</code> remains supported. Use comma-separated IDs for multiple sections. <code>futureonly</code> defaults to <code>false</code>; <code>true</code> includes today and future activities.</p>
                <p class="description">The same displays are available in shortcodes and Gutenberg blocks.</p>
            </section>
        </div>
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
            <section class="postbox osm-settings-tile" aria-labelledby="osm-settings-title">
                <h2 id="osm-settings-title">Settings</h2>
                <p>Manage what appears on your website and where waiting-list applications are sent.</p>
                <ul class="osm-settings-links">
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=online-scout-manager&tab=configuration#osm-section-points' ) ); ?>">Configuration</a><p class="description">Member-name display, date formats, waiting-list section and cache.</p></li>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=online-scout-manager&tab=authentication' ) ); ?>">Authentication</a><p class="description"><?php echo $client_id && $client_secret ? 'Application credentials configured.' : 'Connect your OSM application.'; ?></p></li>
                </ul>
            </section>
            <section class="postbox osm-settings-tile" aria-labelledby="osm-help-title"><h2 id="osm-help-title">Help and feedback</h2><p><a href="https://github.com/alantiller/osm-for-wordpress" target="_blank" rel="noopener noreferrer">Report a bug or request a feature on GitHub</a>.</p></section>
        </div>
    <?php elseif ( 'configuration' === $active_tab ): ?>
        <div class="osm-settings-column">
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
            <section class="postbox osm-settings-tile" id="osm-section-points" aria-labelledby="osm-section-points-title">
                <h2 id="osm-section-points-title">Section Points</h2>
                <p>Enable Section Points on General, then set each section’s name formats and excluded members here. Excluded members are omitted completely from public member lists.</p>
                <?php $points_sections = array_intersect_key( $sections, array_filter( $public_patrol_sections ) ); ?>
                <?php if ( ! $points_sections ): ?><p>No sections have Section Points enabled. Use the Show Section Points row on General.</p><?php endif; ?>
                <?php foreach ( $points_sections as $section_id => $section ):
                    $name_display = OSM_Options::get_name_display( (int) $section_id );
                    $excluded_members = OSM_Options::get_excluded_members( (int) $section_id );
                    $members = $section_members[ $section_id ] ?? [];
                    $other_excluded = array_diff( $excluded_members, array_column( $members, 'id' ) );
                ?>
                <section class="postbox osm-settings-tile osm-section-tile" aria-labelledby="osm-points-section-<?php echo esc_attr( $section_id ); ?>">
                    <h3 id="osm-points-section-<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $section['groupname'] . ': ' . $section['sectionname'] ); ?></h3>
                    <form class="osm-points-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_section_points' ) ); ?>">
                        <?php wp_nonce_field( 'osm_section_points_nonce' ); ?>
                        <input type="hidden" name="osm_section_id" value="<?php echo esc_attr( $section_id ); ?>">
                        <?php foreach ( [ 'firstname' => 'First name', 'lastname' => 'Last name' ] as $field => $label ): ?>
                            <p><label for="osm_<?php echo esc_attr( $field . '_' . $section_id ); ?>"><?php echo esc_html( $label ); ?></label>
                                <select id="osm_<?php echo esc_attr( $field . '_' . $section_id ); ?>" name="osm_<?php echo esc_attr( $field ); ?>_display">
                                    <?php foreach ( [ 'hidden' => 'Hidden', 'full' => 'Full name', 'initial' => 'Initial only' ] as $value => $option_label ): ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $name_display[ $field ], $value ); ?>><?php echo esc_html( $option_label ); ?></option><?php endforeach; ?>
                                </select>
                            </p>
                        <?php endforeach; ?>
                        <table class="widefat striped osm-preview-table"><tbody><tr><th scope="row">Preview</th><td><div class="osm-points-preview" aria-live="polite">The preview will use the members listed below.</div></td></tr></tbody></table>
                        <p class="description">Updates as you change name formats or exclusions. Changes are published after saving. The preview uses the currently loaded members.</p>
                        <h4>Members and exclusions</h4>
                        <p>This full-name list is visible only to administrators. Tick Exclude to keep a member out of every public Section Points member list for this section.</p>
                        <?php if ( isset( $member_errors[ $section_id ] ) ): ?><p role="status"><?php echo esc_html( $member_errors[ $section_id ] ); ?></p><?php endif; ?>
                        <?php if ( $members ): ?>
                        <table class="widefat striped osm-member-table"><thead><tr><th scope="col">Member</th><th scope="col">OSM ID</th><th scope="col">Exclude</th></tr></thead><tbody>
                            <?php foreach ( $members as $member ): ?><tr data-osm-member data-firstname="<?php echo esc_attr( $member['firstname'] ); ?>" data-lastname="<?php echo esc_attr( $member['lastname'] ); ?>" data-patrol="<?php echo esc_attr( $member['patrol'] ); ?>"><th scope="row"><label for="osm-exclude-<?php echo esc_attr( $section_id . '-' . $member['id'] ); ?>"><?php echo esc_html( $member['name'] ?: 'Unnamed member' ); ?></label></th><td><?php echo esc_html( $member['id'] ); ?></td><td><input id="osm-exclude-<?php echo esc_attr( $section_id . '-' . $member['id'] ); ?>" type="checkbox" name="osm_excluded_members[]" value="<?php echo esc_attr( $member['id'] ); ?>" <?php checked( in_array( $member['id'], $excluded_members, true ) ); ?> aria-label="<?php echo esc_attr( 'Exclude ' . $member['name'] ); ?>"></td></tr><?php endforeach; ?>
                        </tbody></table>
                        <?php elseif ( ! isset( $member_errors[ $section_id ] ) ): ?><p>No active members with OSM IDs were returned for this section.</p><?php endif; ?>
                        <p><label for="osm-other-excluded-<?php echo esc_attr( $section_id ); ?>">Other excluded member IDs</label><textarea id="osm-other-excluded-<?php echo esc_attr( $section_id ); ?>" name="osm_other_excluded_members" rows="2" class="large-text"><?php echo esc_textarea( implode( ', ', $other_excluded ) ); ?></textarea></p>
                        <p class="description">Saved exclusions for members absent from the list are kept here. Add OSM member IDs separated by commas or spaces; remove an ID only when you want to allow that member again.</p>
                        <?php submit_button( 'Save Section Points' ); ?>
                    </form>
                </section>
                <?php endforeach; ?>
                <p class="description">Only publish names you are authorised to share. Keep your organisation’s privacy notice accurate.</p>
            </section>
			<section class="postbox osm-settings-tile" id="osm-waiting-list" aria-labelledby="osm-waiting-list-title">
                <h2 id="osm-waiting-list-title">Waiting list</h2>
                <p class="description">Optional notes and helping answers need one text field named Notes in the receiving OSM section. If that field is missing or ambiguous, the child and adult can still be added; the applicant is told to contact the group with the additional details.</p>
                <p class="description">Forms include a responsive default design. To match your site, add CSS targeting <code>.osm-waiting-list</code> in your theme’s custom CSS. Override <code>--osm-form-accent</code>, <code>--osm-form-background</code>, <code>--osm-form-text</code>, <code>--osm-form-button-text</code>, <code>--osm-form-border</code>, <code>--osm-form-radius</code> or <code>--osm-form-max-width</code>.</p>
                <p>Choose and save the OSM section to enable applications. It does not need a current term or programme/events enabled. Clear the selection to disable the form.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_waiting_list' ) ); ?>">
                    <?php wp_nonce_field( 'osm_waiting_list_nonce' ); ?>
                    <p><label for="osm_waiting_list_section_id">Waiting-list section</label><select id="osm_waiting_list_section_id" name="osm_waiting_list_section_id" class="osm-wide-control">
                        <option value="">Choose a section</option>
                        <?php if ( $waiting_list['section_id'] && ! isset( $sections[ $waiting_list['section_id'] ] ) ): ?><option value="<?php echo esc_attr( $waiting_list['section_id'] ); ?>" selected>Saved section (currently unavailable)</option><?php endif; ?>
                        <?php foreach ( $waiting_list_sections as $section_id => $section ): ?><option value="<?php echo esc_attr( $section_id ); ?>" <?php selected( $waiting_list['section_id'], $section_id ); ?>><?php echo esc_html( $section['groupname'] . ': ' . $section['sectionname'] ); ?></option><?php endforeach; ?>
                    </select></p>
                    <?php submit_button( 'Save waiting-list section' ); ?>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_clear_waiting_list' ) ); ?>">
                    <?php wp_nonce_field( 'osm_clear_waiting_list_nonce' ); ?>
                    <p><button type="submit" class="button" <?php disabled( ! $waiting_list['section_id'] ); ?>>Clear waiting list</button></p>
                </form>
            </section>
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
            <section class="postbox osm-settings-tile" id="osm-maintenance" aria-labelledby="osm-maintenance-title">
                <h2 id="osm-maintenance-title">Cache and reset</h2>
                <p>Clear cached data to fetch fresh information from OSM.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_purge_cache' ) ); ?>"><?php wp_nonce_field( 'osm_purge_nonce' ); ?><p><button type="submit" class="button">Purge cache</button></p></form>
                <h3>Reset configuration</h3><p>Removes credentials, section selections, Section Points choices, waiting-list access, display options and cached data.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_reset_configuration' ) ); ?>"><?php wp_nonce_field( 'osm_reset_nonce' ); ?><p><button type="submit" class="button">Reset configuration</button></p></form>

            </section>
        </div>
    <?php else: ?>
        <div class="osm-settings-column">
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
                    <li>Click <strong>Save &amp; Authenticate</strong>, then choose your website sections on General and your waiting-list section in Configuration.</li>
                </ol>
                <p>The plugin requests all four scopes by default using your Client ID and Client Secret:</p>
                <ul><?php foreach ( explode( ' ', OSM_API::get_scopes() ) as $scope ): ?><li><code><?php echo esc_html( $scope ); ?></code></li><?php endforeach; ?></ul>
                <p>These allow programme and event reads, patrol/member reads and waiting-list member creation. Your OSM application must have access to the sections you select.</p>
			</section>
		</div>
    <?php endif; ?>
    </div>
</div>
