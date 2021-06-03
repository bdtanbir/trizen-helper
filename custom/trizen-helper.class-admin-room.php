<?php

/* Room Booking Start */
function trizen_room_booking_add_menu_page()
{
	//Add booking page
	add_submenu_page( 'edit.php?post_type=hotel_room', __( 'Room Bookings', 'trizen-helper' ), __( 'Room Bookings', 'trizen-helper' ), 'manage_options', 'ts_hotel_room_booking', '__hotel_room_booking_page' );
}
add_action( 'admin_menu', 'trizen_room_booking_add_menu_page');


/**
 * @since 1.2.6
 **/
function __hotel_room_booking_page()
{
	/*$section = isset( $_GET[ 'section' ] ) ? $_GET[ 'section' ] : FALSE;

	if ( $section ) {
		switch ( $section ) {
			case "edit_order_item":
				edit_order_item();
				break;
		}
	} else {

		$action = isset( $_POST[ 'ts_action' ] ) ? $_POST[ 'ts_action' ] : FALSE;
		switch ( $action ) {
			case "delete":
				_delete_items();
				break;
		}
	}*/
	include_once TRIZEN_HELPER_PATH.'inc/admin/views/hotel_room/booking_index.php';

}
/**
 * @since 1.2.6
 **/
function edit_order_item(){
	$item_id = isset( $_GET[ 'order_item_id' ] ) ? $_GET[ 'order_item_id' ] : FALSE;
	if ( !$item_id or get_post_type( $item_id ) != 'st_order' ) {
		//wp_safe_redirect(self::$booking_page); die;
		return FALSE;
	}
	include_once TRIZEN_HELPER_PATH.'inc/admin/views/hotel_room/booking_edit.php';
}

/**
 * @since 1.2.6
 **/
function _delete_items()
{

	if ( empty( $_POST ) or !check_admin_referer( 'shb_action', 'shb_field' ) ) {
		//// process form data, e.g. update fields
		return;
	}
	$ids = isset( $_POST[ 'post' ] ) ? $_POST[ 'post' ] : [];
	if ( !empty( $ids ) ) {
		foreach ( $ids as $id )
			wp_delete_post( $id, TRUE );

	}
	esc_html__( "Delete item(s) success", 'trizen-helper' );

}

function _get_currency_book_history( $post_id ) {
	$st_is_woocommerce_checkout = apply_filters( 'ts_is_woocommerce_checkout', false );
	if ( $st_is_woocommerce_checkout ) {
		global $wpdb;
		$querystr    = "SELECT meta_value FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
                                    WHERE
                                    1=1
                                    AND order_item_id = '{$post_id}'
                                    AND meta_key = '_ts_currency'";
		$st_currency = $wpdb->get_row( $querystr, OBJECT );
		if ( !empty( $st_currency->meta_value ) ) {
			return $st_currency->meta_value;
		}
	} else {
		$currency = get_post_meta( $post_id, 'currency', true );
		if ( isset( $currency[ 'symbol' ] ) ) {
			return $currency[ 'symbol' ];
		}
	}

	return null;
}

function format_money_raw( $money = '', $symbol = false, $precision = 2, $template = null )
{
	if ( $money == 0 ) {
		return esc_html__( "Free", 'trizen-helper' );
	}

	/*if ( !$symbol ) {
		$symbol = self::get_current_currency( 'symbol' );
	}*/

	if ( $precision ) {
		$money = round( $money, $precision );
	}
//	if ( !$template ) $template = self::get_current_currency( 'booking_currency_pos' );

	if ( !$template ) {
		$template = 'left';
	}

	$money = number_format( (float)$money, $precision );

	switch ( $template ) {
		case "right":
			$money_string = $money . $symbol;
			break;
		case "left_space":
			$money_string = $symbol . " " . $money;
			break;

		case "right_space":
			$money_string = $money . " " . $symbol;
			break;
		case "left":
		default:
			$money_string = $symbol . $money;
			break;
	}

	return $money_string;
}
if (!function_exists('ts_guest_title_to_text')) {

	function ts_guest_title_to_text($title_id) {
		switch ($title_id) {
			case "mr":
				return esc_html__('Mr', 'trizen-helper');
				break;
			case "mrs":
				return esc_html__('Mrs', 'trizen-helper');
				break;
			case "miss":
				return esc_html__('Miss', 'trizen-helper');
				break;
		}
	}

}
if (!function_exists('ts_admin_print_order_item_guest_name')) {

	function ts_admin_print_order_item_guest_name($data) {
		if (!empty($data['guest_name'])) {
			?>
			<div class="form-row">
				<label class="form-label" for="">
                    <?php esc_html_e('Guest Name', 'trizen-helper') ?>
                </label>
				<div class="controls">
					<?php
					$guest_title = isset($data['guest_title']) ? $data['guest_title'] : [];
					$html        = [];
					foreach ($data['guest_name'] as $k => $name) {
						$str    = isset($guest_title[$k]) ? ts_guest_title_to_text($guest_title[$k]) . ' ' : '';
						$str   .= $name;
						$html[] = $str;
					}
					echo implode(', ', $html);
					?>
				</div>
			</div>

			<?php
		}
	}

}



add_action('init', 'ts_register_location_tax');
function ts_register_location_tax() {
    $booking_type = apply_filters('ts_booking_post_type', [
        'ts_hotel',
        'ts_rental',
        'ts_tours',
        'ts_cars',
        'ts_activity',
        'hotel_room'
    ]);
}

/* Room Booking End */




