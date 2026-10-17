<?php
declare(strict_types=1);
/** Administrator section settings, presented as aligned full-width rows. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" aria-labelledby="osm-sections-title">
    <h2 id="osm-sections-title">Sections</h2>
    <p>Choose programme/events and Section Points independently for each section.</p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_sections' ) ); ?>">
        <?php wp_nonce_field( 'osm_sections_nonce' ); ?>
        <?php if ( $sections ): ?>
            <div class="osm-section-list" role="region" aria-label="Section display settings" tabindex="0">
                <table class="osm-section-table">
                    <thead><tr>
                        <th scope="col">Section</th>
                        <th scope="col">Section ID</th>
                        <th scope="col">Show Programme and Events</th>
                        <th scope="col">Show Section Points</th>
                        <th scope="col">Current term</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $sections as $section_id => $section ):
                        $section_enabled = ! empty( $enabled_sections[ $section_id ] );
                        $points_enabled = ! empty( $public_patrol_sections[ $section_id ] );
                    ?>
                        <tr>
                            <th scope="row"><strong><?php echo esc_html( $section['sectionname'] ); ?></strong><span class="osm-section-meta"><?php echo esc_html( $section['groupname'] ); ?></span></th>
                            <td><code><?php echo esc_html( $section_id ); ?></code></td>
                            <td><label for="osm-programme-<?php echo esc_attr( $section_id ); ?>"><span class="screen-reader-text">Show Programme and Events for <?php echo esc_html( $section['sectionname'] ); ?></span></label><input id="osm-programme-<?php echo esc_attr( $section_id ); ?>" type="checkbox" name="osm_enabled_sections[<?php echo esc_attr( $section_id ); ?>]" value="1" <?php checked( $section_enabled ); ?>></td>
                            <td><label for="osm-points-<?php echo esc_attr( $section_id ); ?>"><span class="screen-reader-text">Show Section Points for <?php echo esc_html( $section['sectionname'] ); ?></span></label><input id="osm-points-<?php echo esc_attr( $section_id ); ?>" type="checkbox" name="osm_public_patrol_sections[<?php echo esc_attr( $section_id ); ?>]" value="1" <?php checked( $points_enabled ); ?>></td>
                            <td><?php echo esc_html( $section_enabled || $points_enabled ? ( $current_terms[ $section_id ] ?? 'Unavailable' ) : '—' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php submit_button( 'Save sections' ); ?>
        <?php else: ?><p>No sections are available. Check authentication and your OSM application’s section access.</p><?php endif; ?>
    </form>
</section>
