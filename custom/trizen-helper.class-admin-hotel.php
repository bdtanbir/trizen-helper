<?php

add_action( 'admin_menu',  'trizen_hotel_booking_add_menu_page' );
function trizen_hotel_booking_add_menu_page()
{
	//Add booking page

	add_submenu_page( 'edit.php?post_type=ts_hotel', __( 'Hotel Bookings', 'trizen-helper' ), __( 'Hotel Bookings', 'trizen-helper' ), 'manage_options', 'ts_hotel_booking', '__hotel_booking_page' );
}

function __hotel_booking_page()
{

	$section = isset( $_GET[ 'section' ] ) ? $_GET[ 'section' ] : FALSE;

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
    }
    include_once TRIZEN_HELPER_PATH.'inc/admin/views/hotel/booking_index.php';

}

