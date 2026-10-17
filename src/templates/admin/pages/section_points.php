<?php
declare(strict_types=1);
/** Full-width section controls with request-local member previews and exclusions. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" id="osm-section-points" aria-labelledby="osm-section-points-title">
    <h2 id="osm-section-points-title">Section Points</h2>
    <p>Enable Section Points on Sections, then choose how names appear. Excluded members are omitted completely from public member lists.</p>
    <?php $points_sections = array_intersect_key( $sections, array_filter( $public_patrol_sections ) ); ?>
    <?php if ( ! $points_sections ): ?><p>No sections have Section Points enabled. Use the Show Section Points row on Sections.</p><?php endif; ?>
    <?php foreach ( $points_sections as $section_id => $section ):
        $name_display = OSM_Options::get_name_display( (int) $section_id );
        $excluded_members = OSM_Options::get_excluded_members( (int) $section_id );
        $members = $section_members[ $section_id ] ?? [];
        $other_excluded = array_diff( $excluded_members, array_column( $members, 'id' ) );
    ?>
        <section class="osm-points-section" aria-labelledby="osm-points-section-<?php echo esc_attr( $section_id ); ?>">
            <form class="osm-points-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=osm_save_section_points' ) ); ?>">
                <?php wp_nonce_field( 'osm_section_points_nonce' ); ?>
                <input type="hidden" name="osm_section_id" value="<?php echo esc_attr( $section_id ); ?>">
                <div class="osm-points-settings">
                    <div><h3 id="osm-points-section-<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $section['sectionname'] ); ?></h3><span class="osm-section-meta"><?php echo esc_html( $section['groupname'] ); ?></span></div>
                    <?php foreach ( [ 'firstname' => 'First name', 'lastname' => 'Last name' ] as $field => $label ): ?>
                        <div><label for="osm_<?php echo esc_attr( $field . '_' . $section_id ); ?>"><?php echo esc_html( $label ); ?></label>
                            <select id="osm_<?php echo esc_attr( $field . '_' . $section_id ); ?>" name="osm_<?php echo esc_attr( $field ); ?>_display">
                                <?php foreach ( [ 'hidden' => 'Hidden', 'full' => 'Full name', 'initial' => 'Initial only' ] as $value => $option_label ): ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $name_display[ $field ], $value ); ?>><?php echo esc_html( $option_label ); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="osm-points-body">
                    <p class="description">This member list is visible only to administrators. Website name previews update as you change formats or exclusions; save to publish your choices.</p>
                    <?php if ( isset( $member_errors[ $section_id ] ) ): ?><p role="status"><?php echo esc_html( $member_errors[ $section_id ] ); ?></p><?php endif; ?>
                    <?php if ( $members ): ?>
                        <div class="osm-member-list" role="region" aria-label="<?php echo esc_attr( $section['sectionname'] . ' members and exclusions' ); ?>" tabindex="0">
                            <table class="widefat osm-member-table"><thead><tr><th scope="col">Member</th><th scope="col">OSM ID</th><th scope="col">Website name preview</th><th scope="col">Exclude</th></tr></thead><tbody>
                            <?php foreach ( $members as $member ):
                                $is_excluded = in_array( $member['id'], $excluded_members, true );
                                $preview_name = OSM_Options::format_member_name( $member['firstname'], $member['lastname'], (int) $section_id );
                            ?>
                                <tr data-osm-member data-firstname="<?php echo esc_attr( $member['firstname'] ); ?>" data-lastname="<?php echo esc_attr( $member['lastname'] ); ?>">
                                    <th scope="row"><label for="osm-exclude-<?php echo esc_attr( $section_id . '-' . $member['id'] ); ?>"><?php echo esc_html( $member['name'] ?: 'Unnamed member' ); ?></label></th>
                                    <td><?php echo esc_html( $member['id'] ); ?></td>
                                    <td class="osm-points-preview"><?php echo esc_html( $is_excluded ? 'Excluded' : ( $preview_name ?: 'Name hidden' ) ); ?></td>
                                    <td><input id="osm-exclude-<?php echo esc_attr( $section_id . '-' . $member['id'] ); ?>" type="checkbox" name="osm_excluded_members[]" value="<?php echo esc_attr( $member['id'] ); ?>" <?php checked( $is_excluded ); ?> aria-label="<?php echo esc_attr( 'Exclude ' . $member['name'] ); ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody></table>
                        </div>
                    <?php elseif ( ! isset( $member_errors[ $section_id ] ) ): ?><p>No active members with OSM IDs were returned for this section.</p><?php endif; ?>
                    <p><label for="osm-other-excluded-<?php echo esc_attr( $section_id ); ?>">Other excluded member IDs</label><textarea id="osm-other-excluded-<?php echo esc_attr( $section_id ); ?>" name="osm_other_excluded_members" rows="2" class="large-text"><?php echo esc_textarea( implode( ', ', $other_excluded ) ); ?></textarea></p>
                    <p class="description">Exclusions for members absent from the list are kept here. Add OSM IDs separated by commas or spaces; remove an ID only when you want to allow that member again.</p>
                    <?php submit_button( 'Save Section Points' ); ?>
                </div>
            </form>
        </section>
    <?php endforeach; ?>
    <p class="description">Only publish names you are authorised to share. Keep your organisation’s privacy notice accurate.</p>
</section>
