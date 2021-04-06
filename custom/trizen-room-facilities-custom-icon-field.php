<?php

add_action( 'room_facilities_add_form_fields', 'trizen_room_facilities_add_term_fields' );
function trizen_room_facilities_add_term_fields( $taxonomy ) { ?>

	<div class="form-field">
        <label for="trizen-room-facilities-icon">
            <?php esc_html_e('Icon Field', 'trizen-helper'); ?>
        </label>
        <input type="text" name="trizen-room-facilities-icon" id="trizen-room-facilities-icon" />
        <p>
            <?php esc_html_e('Enter '); ?><strong><?php esc_html_e('Line Awesome', 'trizen-helper'); ?></strong> <?php esc_html_e('icon\'s name here: Example(s)', 'trizen-helper'); ?> <strong><?php esc_html_e('la la-check', 'trizen-helper'); ?></strong>
        </p>
	</div>

<?php  }

add_action( 'room_facilities_edit_form_fields', 'trizen_room_facilities_edit_term_fields', 10, 2 );

function trizen_room_facilities_edit_term_fields( $term, $taxonomy ) {

	$value = get_term_meta( $term->term_id, 'trizen-room-facilities-icon', true );
	?>
	<tr class="form-field">
        <th>
            <label for="trizen-room-facilities-icon">
                <?php esc_html_e('Icon Field', 'trizen-helper'); ?>
            </label>
        </th>
        <td>
            <input name="trizen-room-facilities-icon" id="trizen-room-facilities-icon" type="text" value="<?php echo esc_attr( $value ) ?>" />
            <p class="description">
                <?php esc_html_e('Enter '); ?><strong><?php esc_html_e('Line Awesome', 'trizen-helper'); ?></strong> <?php esc_html_e('icon\'s name here: Example(s)', 'trizen-helper'); ?> <strong><?php esc_html_e('la la-check', 'trizen-helper'); ?></strong>
            </p>
        </td>
	</tr>

<?php }

add_action( 'created_room_facilities', 'trizen_room_facilities_icon_save_term_fields' );
add_action( 'edited_room_facilities', 'trizen_room_facilities_icon_save_term_fields' );
function trizen_room_facilities_icon_save_term_fields( $term_id ) {

	update_term_meta(
		$term_id,
		'trizen-room-facilities-icon',
		sanitize_text_field( $_POST[ 'trizen-room-facilities-icon' ] )
	);

}


