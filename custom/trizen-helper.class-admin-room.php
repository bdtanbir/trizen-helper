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

	$section = isset( $_GET[ 'section' ] ) ? $_GET[ 'section' ] : FALSE;

	if ( $section ) {
		switch ( $section ) {
			case "edit_order_item":
				edit_order_item();
				break;
		}
	} else {

		$action = isset( $_POST[ 'st_action' ] ) ? $_POST[ 'st_action' ] : FALSE;
		switch ( $action ) {
			case "delete":
				_delete_items();
				break;
		}
		echo balanceTags( load_view( 'hotel_room/booking_index', FALSE ) );
	}

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
	echo balanceTags( load_view( 'hotel_room/booking_edit' ) );
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
	__( "Delete item(s) success", 'trizen-helper' );

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

	$where = '';
	$join = '';
	$select = '';

	if (isset($_GET['ts_date_start']) and $_GET['ts_date_start']) {

		if ($type == 'ts_cars') {
			$date = ( date('m/d/Y', strtotime($_GET['ts_date_start'])) );
			$where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in >= '{$date}'";
		} else {
			$date = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
			$where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) >= {$date}";
		}
	}

	if (isset($_GET['ts_date_end']) and $_GET['ts_date_end']) {

		if ($type == 'ts_cars') {
			$date = ( date('m/d/Y', strtotime($_GET['ts_date_end'])) );
			$where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in <= '{$date}'";
		} else {
			$date = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
			$where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) <= {$date}";
		}
	}

	if ($c_name = STInput::get('ts_custommer_name')) {
		$join .= " INNER JOIN {$wpdb->prefix}postmeta as mt3 on mt3.post_id= {$wpdb->prefix}ts_order_item_meta.order_item_id";
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


/* Room Booking End */




