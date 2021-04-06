<?php
/*
Plugin Name: Trizen Helper
Plugin URI:
Description: This is helper plugin for Trizen theme.
Author: Trizen
Version: 1.0.0
Author URI: https://techydevs.com/about
text-domain: trizen-helper
*/

if (!defined('ABSPATH')) die('No Direct Access is allowed');


define( 'TRIZEN_HELPER_URI', plugin_dir_url(  __FILE__ ) );
define( 'TRIZEN_HELPER_PATH', plugin_dir_path( __FILE__ ) );
define( 'TRIZEN_HELPER_VERSION', '1.0' );


add_action('plugins_loaded', 'trizen_helper_load');

function trizen_helper_load()
{
	require_once TRIZEN_HELPER_PATH.'widgets/trizen-social-profile.php';
	require_once TRIZEN_HELPER_PATH.'widgets/trizen-recent-post-with-thumbnail.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-custom-post-types.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-custom-metaboxes.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-taxonomy-class.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-room-facilities-custom-icon-field.php';
	require_once TRIZEN_HELPER_PATH.'widgets/trizen-hotel-organized-by.php';
	require_once TRIZEN_HELPER_PATH.'widgets/trizen-hotel-price-and-availability.php';
	require_once TRIZEN_HELPER_PATH.'widgets/trizen-hotel-room-booking-fields.php';
	require_once TRIZEN_HELPER_PATH.'inc/trizen-helper.booking.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-helper.class-admin-room.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-helper.class-admin-hotel.php';
	require_once TRIZEN_HELPER_PATH.'custom/trizen-room-availability-model.php';
	require_once TRIZEN_HELPER_PATH.'assets/admin/hotel-inventory/inventory.php';
}


function trizen_helper_admin_script()
{
	wp_enqueue_style(
		'lib-admin-select2-css',
		'//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
	);
	wp_enqueue_style(
		'trizen-available-calendar-css',
		TRIZEN_HELPER_URI.('assets/admin/css/trizen-available-calendar.css'),
		'',
		TRIZEN_HELPER_VERSION
	);
	wp_enqueue_style(
		'trizen-admin-global-css',
		TRIZEN_HELPER_URI.('assets/admin/css/trizen-admin.css'),
		'',
		TRIZEN_HELPER_VERSION
	);

	if ( ! did_action( 'wp_enqueue_media' ) )
		wp_enqueue_media();
	wp_enqueue_script(
		'lib-admin-select2-js',
		'//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
		array('jquery'),
		TRIZEN_HELPER_VERSION,
		true
	);
	wp_enqueue_script(
		'lib-moment.js',
		TRIZEN_HELPER_URI.('assets/admin/js/moment.min.js'),
		array('jquery'),
		null,
		true
	);
	wp_enqueue_script(
		'lib-gantt.js',
		TRIZEN_HELPER_URI.('assets/admin/js/jquery.fn.gantt.js'),
		array('jquery', 'lib-moment.js'),
		null,
		true
	);
	wp_enqueue_script(
		'lib-fullcalendar-js',
		TRIZEN_HELPER_URI . ('assets/admin/js/main.min.js'),
		array('jquery','lib-moment.js'),
		null,
		true
	);
	wp_enqueue_script(
		'lib-locales-all-js',
		TRIZEN_HELPER_URI . ('assets/admin/js/locales-all.js'),
		array('jquery', 'lib-fullcalendar-js'),
		null,
		true
	);
	wp_register_script(
		'bulk-calendar',
		TRIZEN_HELPER_URI . ('assets/admin/js/bulk-calendar.js'),
		array('jquery'),
		null,
		true
	);
	wp_register_script(
		'trizen-hotel-inventory',
		TRIZEN_HELPER_URI . ('assets/admin/js/trizen-hotel-inventory.js'),
		array('jquery'),
		null,
		true
	);
	wp_enqueue_script(
		'trizen-hotel-calendar-js',
		TRIZEN_HELPER_URI . ('assets/admin/js/trizen-hotel-calendar.js'),
		['jquery', 'lib-daterangepicker-min'],
		null,
		true
	);
	wp_enqueue_script(
		'trizen-helper-admin-js',
		TRIZEN_HELPER_URI . ('assets/js/trizen-helper-admin.js'),
		'',
		TRIZEN_HELPER_VERSION,
		true
	);

	$locale = get_locale();
	$locale_fullcalendar = $locale;
	if (substr($locale, 0, 2)) {
		$locale_fullcalendar = strtolower(substr($locale, 0, 2));
	}
	wp_localize_script('jquery', 'ts_timezone', [
		'timezone_string' => get_option('timezone_string', 'local'),
	]);
	wp_localize_script('jquery', 'ts_params', [
		'locale'              => $locale,
		'locale_fullcalendar' => $locale_fullcalendar,
		'text_refresh'        => esc_html__('Refresh', 'trizen-helper'),
		'text_adult'          => esc_html__('Adult: ', 'trizen-helper'),
		'text_child'          => esc_html__('Child: ', 'trizen-helper'),
		'text_infant'         => esc_html__('Infant: ', 'trizen-helper'),
		'text_price'          => esc_html__('Price: ', 'trizen-helper'),
		'text_unavailable'    => esc_html__('Not Available ', 'trizen-helper'),
		'text_available'      => esc_html__('Available ', 'trizen-helper'),
		'text_adult_price'    => esc_html__('Adult Price ', 'trizen-helper'),
		'text_child_price'    => esc_html__('Child Price ', 'trizen-helper'),
		'text_infant_price'   => esc_html__("Infant", 'trizen-helper'),
		'text_update'         => esc_html__('Update ', 'trizen-helper'),
		'_s'                  => wp_create_nonce('traveler_admin_security'),
		'ajax_url'            => admin_url('admin-ajax.php'),
		'text_process_cancel' => esc_html__('You cancelled the process', 'trizen-helper'),
		'dateformat'          => getDateFormatJs(null, 'calendar'),
		'dateformat_convert'  => getDateFormatJs(null, 'admin-calendar'),
		'please_waite' => __('Please wait...', 'traveler'),
		'prev_month' => __('prev month', 'traveler'),
		'next_month' => __('next month', 'traveler'),
	]);


	wp_localize_script('jquery', 'locale_daterangepicker', [
		'direction' => (is_rtl() || trizen_get_option('right_to_left') == 'on')? 'rtl': 'ltr',
		'applyLabel' => __('Apply', 'traveler'),
		'cancelLabel' => __('Cancel', 'traveler'),
		'fromLabel' => __('From', 'traveler'),
		'toLabel' => __('To', 'traveler'),
		'customRangeLabel' => __('Custom', 'traveler'),
		'daysOfWeek' =>  [__('Su', 'traveler'), __('Mo', 'traveler'), __('Tu', 'traveler'), __('We', 'traveler'), __('Th', 'traveler'), __('Fr', 'traveler'), __('Sa', 'traveler')],
		'monthNames' => [__('January', 'traveler'), __('February', 'traveler'), __('March', 'traveler'), __('April', 'traveler'), __('May', 'traveler'), __('June', 'traveler'), __('July', 'traveler'), __('August', 'traveler'), __('September', 'traveler'), __('October', 'traveler'), __('November', 'traveler'), __('December', 'traveler')],
		'firstDay' => (int)trizen_get_option('start_week', 0),
		'today' => __('Today', 'traveler'),
		'please_waite' => __('Please wait...', 'traveler'),
		'buttons' => __('buttons', 'traveler'),
	]);
}
add_action('admin_enqueue_scripts', 'trizen_helper_admin_script');

function trizen_helper_scripts() {
	wp_enqueue_script('trizen-helper-js', TRIZEN_HELPER_URI .'assets/js/trizen-helper.js', ['jquery'], TRIZEN_HELPER_VERSION, true);

}
add_action('wp_enqueue_scripts', 'trizen_helper_scripts');





function get_username($user_id) {
	$userdata = get_userdata($user_id);
	if (!$userdata) {
		return esc_html__('Customer', 'trizen-helper');
	}
	if ($userdata->display_name) {
		return $userdata->display_name;
	} elseif ($userdata->first_name || $userdata->last_name) {
		return $userdata->first_name . ' ' . $userdata->last_name;
	} else {
		return $userdata->user_login;
	}
}


if (is_admin()) {
	add_action( 'wp_ajax_ts_get_availability_hotel', '_get_availability_hotel' );
	add_action( 'wp_ajax_ts_add_custom_price', '_add_custom_price' );


	function _get_availability_hotel()
	{
		$results       = [];
		$post_id       = request('post_id', '');
		$post_id       = post_origin($post_id);
		$check_in      = request('start', '');
		$check_out     = request('end', '');
		$price_ori     = floatval(get_post_meta($post_id, 'price', true));
		$default_state = get_post_meta($post_id, 'default_state', true);
		$number_room   = intval(get_post_meta($post_id, 'trizen_hotel_room_number', true));
		if (get_post_type($post_id) == 'hotel_room') {
			$data = _getdataHotel($post_id, $check_in, $check_out);
			for ($i = intval($check_in); $i <= intval($check_out); $i = strtotime('+1 day', $i)) {
				$in_date = false;
				if (is_array($data) && count($data)) {
					foreach ($data as $key => $val) {
						if ($i >= intval($val->check_in) && $i <= intval($val->check_out)) {
							$status = $val->status;
							if ($status != 'unavailable') {
								$item = [
									'price'   => floatval($val->price),
									'start'   => date('Y-m-d', $i),
									'title'   => get_the_title($post_id),
									'item_id' => $val->id,
									'status'  => $val->status,
								];
							} else {
								unset($item);
							}
							if (!$in_date)
								$in_date = true;
						}
					}
				}
				if (isset($item)) {
					$results[] = $item;
					unset($item);
				}
				if (!$in_date && ($default_state == 'available' || !$default_state)) {
					$item_ori = [
						'price'  => $price_ori,
						'start'  => date('Y-m-d', $i),
						'title'  => get_the_title($post_id),
						'number' => $number_room,
						'status' => 'available',
					];
					$results[] = $item_ori;
					unset($item_ori);
				}
				if (!$in_date) {
					$parent_id = get_post_meta($post_id, 'trizen_hotel_room_select', true);
					TS_Model::inst()->insertOrUpdate([
						'post_id'   => $post_id,
						'check_in'  => $i,
						'check_out' => $i,
						'status'    => (!$default_state or $default_state == 'available') ? 'available' : 'unavailable',
						'is_base'   => 1,
						'price'     => $price_ori,
						'post_type' => 'hotel_room',
						'parent_id' => $parent_id,
					]);
				}
			}
		}
		echo json_encode($results);
		die();
	}

	function _add_custom_price()
	{
		$check_in  = request( 'calendar_check_in', '' );
		$check_out = request( 'calendar_check_out', '' );
		$format    = trizen_get_option( 'datetime_format', '{mm}/{dd}/{yyyy}' );
		if($format === '{dd}/{mm}/{yyyy}'
		   || $format === '{dd}/{m}/{yyyy}'
		   || $format === '{d}/{m}/{yyyy}'
		   || $format === '{dd}/{m}/{yyyy}'
		   || $format === '{d}/{mm}/{yyyy}'
		   || $format === '{dd}/{mm}/{yy}'
		   || $format === '{dd}/{m}/{yy}'
		   || $format === '{d}/{m}/{yy}'
		   || $format === '{dd}/{m}/{yy}'
		   || $format === '{d}/{mm}/{yy}'
		){
			$check_in  = str_replace('/', '-', $check_in);
			$check_out = str_replace('/', '-', $check_out);
			$check_in  = date('m/d/Y', strtotime($check_in));
			$check_out = date('m/d/Y', strtotime($check_out));
		}
		if ( empty( $check_in ) || empty( $check_out ) ) {
			echo json_encode( [
				'type'    => 'error',
				'status'  => 0,
				'message' => esc_html__( 'The check in or check out field is not empty.', 'trizen-helper' )
			] );
			die();
		}
		$check_in  = strtotime( $check_in );
		$check_out = strtotime( $check_out );
		if ( $check_in > $check_out ) {
			echo json_encode( [
				'type'    => 'error',
				'status'  => 0,
				'message' => esc_html__( 'The check out is later than the check in field.', 'trizen-helper' )
			] );
			die();
		}

		$status = request( 'calendar_status', 'available' );
		if ( $status == 'available' ) {
			if ( filter_var( $_POST[ 'calendar_price' ], FILTER_VALIDATE_FLOAT ) === false ) {
				echo json_encode( [
					'type'    => 'error',
					'status'  => 0,
					'message' => esc_html__( 'The price field is not a number.', 'trizen-helper' )
				] );
				die();
			}
		}
		$price   = floatval( request( 'calendar_price', '' ) );
//		$price   = request( 'calendar_price', '' );
		$post_id = request( 'calendar_post_id', '' );
		$post_id = post_origin($post_id);
//		$adult_price = floatval( request( 'calendar_adult_price', '' ) );
//		$child_price = floatval( request( 'calendar_child_price', '' ) );

		$parent_id = get_post_meta($post_id, 'trizen_hotel_room_select', true);

		for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
			$data = [
				'post_id'     => $post_id,
				'post_type'   => 'hotel_room',
				'check_in'    => $i,
				'check_out'   => $i,
				'price'       => $price,
				'status'      => $status,
				'parent_id'   => $parent_id,
				'is_base'     => 0,
//				'adult_price' => $adult_price,
//				'child_price' => $child_price,
			];
			TS_Model::inst()->insertOrUpdate($data);
		}

		echo json_encode( [
			'type'    => 'success',
			'status'  => 1,
			'message' => esc_html__( 'Successfully', 'trizen-helper' )
		] );
		die();
	}

}

