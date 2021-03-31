<?php

add_action( 'hotel_facilities_add_form_fields', 'trizen_hotel_facilities_add_term_fields' );
function trizen_hotel_facilities_add_term_fields( $taxonomy ) { ?>

	<div class="form-field">
	<label for="trizen-hotel-facilities-icon">
        <?php esc_html_e('Icon Field', 'trizen-helper'); ?>
    </label>
	<input type="text" name="trizen-hotel-facilities-icon" id="trizen-hotel-facilities-icon" />
	<p><?php esc_html_e('Enter '); ?><strong><?php esc_html_e('Line Awesome', 'trizen-helper'); ?></strong> <?php esc_html_e('icon\'s name here: Example(s)', 'trizen-helper'); ?> <strong><?php esc_html_e('la la-check', 'trizen-helper'); ?></strong></p>
	</div>

<?php  }

add_action( 'hotel_facilities_edit_form_fields', 'trizen_hotel_facilities_edit_term_fields', 10, 2 );

function trizen_hotel_facilities_edit_term_fields( $term, $taxonomy ) {

	$value = get_term_meta( $term->term_id, 'trizen-hotel-facilities-icon', true );
	?>
	<tr class="form-field">
        <th>
            <label for="trizen-hotel-facilities-icon"><?php esc_html_e('Icon Field', 'trizen-helper'); ?></label>
        </th>
        <td>
            <input name="trizen-hotel-facilities-icon" id="trizen-hotel-facilities-icon" type="text" value="<?php echo esc_attr( $value ) ?>" />
            <p class="description"><?php esc_html_e('Enter '); ?><strong><?php esc_html_e('Line Awesome', 'trizen-helper'); ?></strong> <?php esc_html_e('icon\'s name here: Example(s)', 'trizen-helper'); ?> <strong><?php esc_html_e('la la-check', 'trizen-helper'); ?></strong></p>
        </td>
	</tr>

<?php }

add_action( 'created_hotel_facilities', 'trizen_hotel_facilities_icon_save_term_fields' );
add_action( 'edited_hotel_facilities', 'trizen_hotel_facilities_icon_save_term_fields' );
function trizen_hotel_facilities_icon_save_term_fields( $term_id ) {

	update_term_meta(
		$term_id,
		'trizen-hotel-facilities-icon',
		sanitize_text_field( $_POST[ 'trizen-hotel-facilities-icon' ] )
	);

}


