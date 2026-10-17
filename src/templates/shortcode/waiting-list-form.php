<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Direct application form; all values are request-local and escaped at output. */
$form_id = 'osm-application-' . bin2hex( random_bytes( 4 ) );
?>
<div class="osm-waiting-list">
    <?php if ( $result['message'] ): ?><p class="osm-form-notice" role="status"><?php echo esc_html( $result['message'] ); ?></p><?php endif; ?>
    <?php if ( $result['errors'] ): ?>
        <div class="osm-form-errors" role="alert"><p>Please correct the following:</p><ul><?php foreach ( $result['errors'] as $error ): ?><li><?php echo esc_html( $error ); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ( ! $result['complete'] ): ?>
        <form method="post" class="osm-application-form" data-prepare-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" <?php if ( $preview ): ?>data-osm-preview="true"<?php endif; ?>>
            <input type="hidden" name="osm_waiting_list_submit" value="1">
            <input type="hidden" name="_osm_nonce" value="<?php echo esc_attr( $credentials['nonce'] ); ?>">
            <input type="hidden" name="_osm_token" value="<?php echo esc_attr( $credentials['token'] ); ?>">
            <fieldset <?php if ( $preview ): ?>disabled<?php endif; ?>>
                <legend>Join the waiting list</legend>
                <p>Your application details will be sent securely to Online Scout Manager for the group to manage your enquiry.</p>
                <?php foreach ( [ 'Child’s details' => [ 'firstname' => 'Child’s first name', 'lastname' => 'Child’s last name', 'dob' => 'Child’s date of birth' ], 'Parent or carer’s details' => [ 'parent_firstname' => 'Parent or carer’s first name', 'parent_lastname' => 'Parent or carer’s last name', 'email' => 'Parent or carer’s email' ] ] as $group_label => $fields ): ?>
                    <fieldset class="osm-form-group"><legend><?php echo esc_html( $group_label ); ?></legend>
                        <div class="osm-form-fields">
                        <?php foreach ( $fields as $field => $label ): ?>
                            <?php if ( 'dob' === $field ): ?>
                                <fieldset class="osm-form-field osm-form-field--wide osm-dob"><legend>Child’s date of birth <span aria-hidden="true">*</span></legend>
                                    <div class="osm-dob-fields">
                                    <?php foreach ( [ 'day' => [ 'Day', 'dd', 2 ], 'month' => [ 'Month', 'mm', 2 ], 'year' => [ 'Year', 'yyyy', 4 ] ] as $part => $part_settings ): ?>
                                        <p><label for="<?php echo esc_attr( $form_id . '-dob-' . $part ); ?>"><?php echo esc_html( $part_settings[0] ); ?></label><input id="<?php echo esc_attr( $form_id . '-dob-' . $part ); ?>" name="dob_<?php echo esc_attr( $part ); ?>" type="text" inputmode="numeric" pattern="[0-9]{<?php echo 'year' === $part ? '4' : '1,2'; ?>}" maxlength="<?php echo esc_attr( $part_settings[2] ); ?>" placeholder="<?php echo esc_attr( $part_settings[1] ); ?>" value="<?php echo esc_attr( $result['values'][ 'dob_' . $part ] ?? '' ); ?>" required></p>
                                    <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php else: ?>
                            <p class="osm-form-field <?php echo 'email' === $field ? 'osm-form-field--wide' : ''; ?>"><label for="<?php echo esc_attr( $form_id . '-' . $field ); ?>"><?php echo esc_html( $label ); ?> <span aria-hidden="true">*</span></label>
                                <input id="<?php echo esc_attr( $form_id . '-' . $field ); ?>" name="<?php echo esc_attr( $field ); ?>" type="<?php echo 'email' === $field ? 'email' : 'text'; ?>" value="<?php echo esc_attr( $result['values'][ $field ] ?? '' ); ?>" maxlength="<?php echo 'email' === $field ? '254' : '120'; ?>" required>
                            </p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
                <fieldset class="osm-form-group"><legend>Anything else?</legend>
                    <p class="osm-form-field"><label for="<?php echo esc_attr( $form_id . '-help' ); ?>">Would you be willing to help with the group? (optional)</label><select id="<?php echo esc_attr( $form_id . '-help' ); ?>" name="willing_to_help">
                        <?php foreach ( [ '' => 'Choose an answer', 'yes' => 'Yes', 'no' => 'No', 'discuss' => 'I’d like to find out more' ] as $value => $label ): ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $result['values']['willing_to_help'] ?? '', $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
                    </select></p>
                    <p class="osm-form-field"><label for="<?php echo esc_attr( $form_id . '-note' ); ?>">Anything else we should know? (optional)</label><textarea id="<?php echo esc_attr( $form_id . '-note' ); ?>" name="parent_note" rows="4" maxlength="2000" aria-describedby="<?php echo esc_attr( $form_id . '-note-help' ); ?>"><?php echo esc_textarea( $result['values']['parent_note'] ?? '' ); ?></textarea></p>
                    <p id="<?php echo esc_attr( $form_id . '-note-help' ); ?>">For example, a sibling already in the group or a preferred meeting night. Up to 2,000 characters. Please do not include medical details here.</p>
                </fieldset>
                <div class="osm-form-honeypot" aria-hidden="true"><label>Leave this blank <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <p><label class="osm-form-consent"><input type="checkbox" name="privacy_acknowledged" value="1" required><span> <?php if ( $privacy_url ): ?>I have read the <a href="<?php echo esc_url( $privacy_url ); ?>">privacy notice</a> and <?php else: ?>I <?php endif; ?>understand that these details will be sent to Online Scout Manager for the group to manage this application.</span></label></p>
                <p><button type="submit">Send application</button></p>
                <p class="osm-form-status" role="status"></p>
            </fieldset>
            <?php if ( $preview ): ?><p>This is a preview. Applications cannot be submitted here.</p><?php endif; ?>
        </form>
    <?php endif; ?>
</div>
