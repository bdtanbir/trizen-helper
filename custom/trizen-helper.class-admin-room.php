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
    echo '<h1>'.esc_html__('Hotel Room Booking', 'trizen-helper').'</h1>';
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
function edit_order_item()
{
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


function load_view($slug, $name = false, $data = []) {

	extract($data);

	if ($name) {
		$slug = $slug . '-' . $name;
	}

	//Find template in folder inc/admin/views/
	$template = locate_template('inc/admin/views/' . $slug . '.php');


	//If file not found
	if (is_file($template)) {
		ob_start();

		include $template;

		$data = @ob_get_clean();

		return $data;
	}
}

function get_history_bookings($type = "ts_hotel", $offset, $limit, $author = false) {
	global $wpdb;

	$where  = '';
	$join   = '';
	$select = '';

	if (isset($_GET['ts_date_start']) and $_GET['ts_date_start']) {

		if ($type == 'ts_cars') {
			$date   = ( date('m/d/Y', strtotime($_GET['ts_date_start'])) );
			$where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in >= '{$date}'";
		} else {
			$date   = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
			$where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) >= {$date}";
		}
	}

	if (isset($_GET['ts_date_end']) and $_GET['ts_date_end']) {

		if ($type == 'ts_cars') {
			$date   = ( date('m/d/Y', strtotime($_GET['ts_date_end'])) );
			$where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in <= '{$date}'";
		} else {
			$date   = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
			$where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) <= {$date}";
		}
	}

	if ($c_name = get('ts_custommer_name')) {
		$join  .= " INNER JOIN {$wpdb->prefix}postmeta as mt3 on mt3.post_id= {$wpdb->prefix}ts_order_item_meta.order_item_id";
		$where .= ' AND  mt3.meta_key=\'ts_first_name\'
             ';
		$where .= ' AND mt3.meta_value like \'%' . esc_sql($c_name) . '%\'';
	}

	if ($author) {
		$author = " AND {$wpdb->prefix}ts_order_item_meta.user_id = " . $author;
	}

	$querystr = "
            SELECT SQL_CALC_FOUND_ROWS  {$wpdb->prefix}posts.* from {$wpdb->prefix}ts_order_item_meta
            {$join}
            INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}ts_order_item_meta.order_item_id
            WHERE 1=1 AND st_booking_post_type = '{$type}' AND type='normal_booking' {$where}
            ORDER BY {$wpdb->prefix}ts_order_item_meta.id DESC
            LIMIT {$offset},{$limit}
            ";
	$pageposts = $wpdb->get_results($querystr, OBJECT);

	return ['total' => $wpdb->get_var("SELECT FOUND_ROWS();"), 'rows' => $pageposts];
}

function _get_currency_book_history( $post_id )
{
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

/* Room Booking End */




