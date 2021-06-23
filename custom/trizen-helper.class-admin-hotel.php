<?php

add_action( 'admin_menu',  'trizen_hotel_booking_add_menu_page' );
function trizen_hotel_booking_add_menu_page()
{
	//Add booking page

	add_submenu_page( 'edit.php?post_type=ts_hotel', __( 'Hotel Bookings', 'trizen-helper' ), __( 'Hotel Bookings', 'trizen-helper' ), 'manage_options', 'ts_hotel_booking', '__hotel_booking_page' );
}

function __hotel_booking_page() {
    include_once TRIZEN_HELPER_PATH.'inc/admin/views/hotel/booking_index.php';

}

