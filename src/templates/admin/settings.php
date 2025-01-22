<div class="wrap">
    <h1>Online Scout Manager for WordPress</h1>

    <?php if ( empty( $client_id ) || empty( $client_secret ) ): ?>
        <div class="notice notice-error"><p><strong>Authentication not configured.</strong> Please configure authentication in the "Authentication" tab.</p></div>
    <?php elseif ( empty( $enabled_sections ) ): ?>
        <div class="notice notice-error"><p><strong>No sections enabled.</strong> Please enable sections in the "Sections Enabled" tab.</p></div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url( 'admin.php?page=osm-for-wordpress&tab=general' ); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
        <a href="<?php echo admin_url( 'admin.php?page=osm-for-wordpress&tab=shortcodes' ); ?>" class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
        <a href="<?php echo admin_url( 'admin.php?page=osm-for-wordpress&tab=sections' ); ?>" class="nav-tab <?php echo $active_tab === 'sections' ? 'nav-tab-active' : ''; ?>">Sections Enabled</a>
        <a href="<?php echo admin_url( 'admin.php?page=osm-for-wordpress&tab=authentication' ); ?>" class="nav-tab <?php echo $active_tab === 'authentication' ? 'nav-tab-active' : ''; ?>">Authentication</a>
    </h2>

    <?php if ( $active_tab === 'general' ): ?>
        <h2>General Settings</h2>
        <p>The following functions allow administrators to perform certain actions when maintaining the website or diagnosing an issue. These should not be used unless you know what they do.</p>
        <ul>
            <li><strong>Purge Cache:</strong> This will remove all cached data from the database. This will not affect the configuration settings.</li>
            <li><strong>Reset Configuration:</strong> This will remove all configuration settings, including authentication and enabled sections. This will not affect the cached data.</li>
        </ul>
        
        <div class="osm-actions">
            <form method="post" action="<?php echo admin_url( 'admin-post.php?action=osm_purge_cache' ); ?>" style="margin-bottom: 10px;">
                <?php wp_nonce_field( 'osm_purge_nonce' ); ?>
                <button type="submit" class="button">Purge Cache</button>
            </form>

            <form method="post" action="<?php echo admin_url( 'admin-post.php?action=osm_reset_configuration' ); ?>">
                <?php wp_nonce_field( 'osm_reset_nonce' ); ?>
                <button type="submit" class="button button-secondary">Reset Configuration</button>
            </form>
        </div>

        <h3>Enabled Sections</h3>
        <?php if ( empty( $enabled_sections ) ): ?>
            <p><strong>No sections enabled.</strong></p>
        <?php else: ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th id="columnname" class="manage-column column-columnname" scope="col">Section Name</th>
                        <th id="columnname" class="manage-column column-columnname" scope="col">Section ID</th>
                        <th id="columnname" class="manage-column column-columnname" scope="col">Current Term ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $row_count = 1;
                    foreach ( $enabled_sections as $sectionid => $enabled ):
                        $row_count++;
                        $sectionDetails = OSM_API::get_sections()[$sectionid]; ?>
                        <tr class="<?php echo esc_attr( ($row_count % 2 === 0) ? '' : 'alternate' ); ?>">
                            <td class="column-columnname"><?php echo esc_html( $sectionDetails['groupname'] . ': ' . $sectionDetails['sectionname'] ); ?></td>
                            <td class="column-columnname"><?php echo esc_html( $sectionid ); ?></td>
                            <td class="column-columnname"><?php echo esc_html( OSM_API::get_current_term( $sectionid ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Bugs &amp; Feature Requests</h3>
        <p>If you encounter any bugs or have feature requests, please report them via the GitHub repository: <a href="https://github.com/alantiller/osm-for-wordpress" target="_blank">alantiller/osm-for-wordpress</a>.</p>
    <?php elseif ( $active_tab === 'shortcodes' ): ?>
        <h2>Shortcodes</h2>
        <p>Use the following shortcodes to display OSM data on your website:</p>

        <h3>Programme Shortcode</h3>
        <p>
            <code>[osm_programme sectionid="SECTION_ID" futureonly="true"]</code>
        </p>
        <ul>
            <li><strong>sectionid</strong> (required): The ID of the section to display.</li>
            <li><strong>futureonly</strong> (optional): Set to <code>true</code> to show only future events. Default: <code>false</code>.</li>
        </ul>
        <p>Example:</p>
        <p>
            <code>[osm_programme sectionid="12345" futureonly="true"]</code>
        </p>

        <h3>Events Shortcode</h3>
        <p>
            <code>[osm_events sectionid="SECTION_ID" futureonly="true"]</code>
        </p>
        <ul>
            <li><strong>sectionid</strong> (required): The ID of the section to display.</li>
            <li><strong>futureonly</strong> (optional): Set to <code>true</code> to show only future events. Default: <code>false</code>.</li>
        </ul>
        <p>Example:</p>
        <p>
            <code>[osm_events sectionid="67890" futureonly="false"]</code>
        </p>
    <?php elseif ( $active_tab === 'sections' ): ?>
        <h2>Sections Enabled</h2>
        <form method="post" action="<?php echo admin_url( 'admin-post.php?action=osm_save_sections' ); ?>">
            <?php wp_nonce_field( 'osm_sections_nonce' ); ?>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Section Name</th>
                        <th>Enable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( OSM_API::get_sections() as $sectionid => $section ): ?>
                        <tr>
                            <td><?php echo esc_html( $section['groupname'] . ': ' . $section['sectionname'] ); ?></td>
                            <td>
                                <input type="checkbox" name="osm_enabled_sections[<?php echo esc_attr( $sectionid ); ?>]" value="1" <?php checked( 1, $enabled_sections[ $sectionid ] ?? 0 ); ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button( 'Save Sections' ); ?>
        </form>

    <?php elseif ( $active_tab === 'authentication' ): ?>
        <h2>Authentication</h2>
        <form method="post" action="<?php echo admin_url( 'admin-post.php?action=osm_save_auth' ); ?>">
            <?php wp_nonce_field( 'osm_auth_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="osm_client_id">Client ID</label></th>
                    <td><input type="text" id="osm_client_id" name="osm_client_id" placeholder="<?php if ($client_id) {echo 'Value hidden for security. Reset Configuration to clear.';} ?>" value="" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="osm_client_secret">Client Secret</label></th>
                    <td><input type="password" id="osm_client_secret" name="osm_client_secret" placeholder="<?php if ($client_secret) {echo 'Value hidden for security. Reset Configuration to clear.';} ?>" value="" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button( 'Save & Authenticate' ); ?>
            <h3>Need help?</h3>
            <p>Follow these steps to obtain your Client ID and Client Secret:</p>
            <ol>
                <li>Log in to <a href="https://www.onlinescoutmanager.co.uk" target="_blank">Online Scout Manager (OSM)</a>.</li>
                <li>Expand the <strong>Settings</strong> menu at the bottom of the page.</li>
                <li>Select <strong>My Account Details</strong>.</li>
                <li>Click <strong>Developer Tools</strong> from the menu on the left-hand side.</li>
                <li>Click <strong>Create Application</strong>.</li>
                <li>Provide a name for your application and click <strong>Save</strong>.</li>
                <li>
                    The <strong>Client ID</strong> and <strong>Client Secret</strong> will be displayed <em>once only</em>. 
                    Make sure to copy them both and paste them into the fields above.
                </li>
                <li>Click <strong>Save & Authenticate</strong> to authenticate your application.</li>
            </ol>
        </form>
    <?php endif; ?>
</div>