<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
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
