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

function trizen_helper_load() {
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
	require_once TRIZEN_HELPER_PATH.'admin/hotel-inventory/inventory.php';
    require_once TRIZEN_HELPER_PATH.'admin/inc/database.helper.php';
    require_once TRIZEN_HELPER_PATH.'admin/inc/class.review.php';
    require_once TRIZEN_HELPER_PATH.'core/database/tables/availability.php';
    require_once TRIZEN_HELPER_PATH.'core/database/tables/hotel_room_availability.php';
    require_once TRIZEN_HELPER_PATH.'core/database/tables/order_item.php';
    require_once TRIZEN_HELPER_PATH.'admin/inc/class.user.php';
}
require_once TRIZEN_HELPER_PATH.'custom/trizen-availability-model.php';
require_once TRIZEN_HELPER_PATH.'inc/trizen-hook-function.php';

if(is_admin()) {
    require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.availability.php';
    require_once TRIZEN_HELPER_PATH.'core/database/tables/ts_price.php';
}
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/nested_sets_model.helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.room.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/availability.helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.neworder.data.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.woocommerce.php';
require_once TRIZEN_HELPER_PATH.'core/database/tables/posts.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.hotel.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.upgrade.data.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.location.relationships.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/travel-helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/hotel.helper.php';
require_once TRIZEN_HELPER_PATH.'inc/class.hotel-helper.php';
//require_once TRIZEN_HELPER_PATH.'inc/class.hotel.php';
//require_once TRIZEN_HELPER_PATH.'inc/class.travelobject.php';
require_once TRIZEN_HELPER_PATH.'inc/helper/price.helper.php';
require_once TRIZEN_HELPER_PATH.'inc/hotel-alone-helper.php';


add_action( 'wp_ajax_ts_get_availability_hotel', '_get_availability_hotel' );
function _get_availability_hotel()
{
    $results       = [];
    $post_id       = request('post_id', '');
    $post_id       = post_origin($post_id);
    $check_in      = request('start', '');
    $check_out     = request('end', '');
    $price_ori     = floatval(get_post_meta($post_id, 'price', true));
    $default_state = get_post_meta($post_id, 'default_state', true);
    $number_room   = intval(get_post_meta($post_id, 'number_room', true));
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
                $parent_id = get_post_meta($post_id, 'room_parent', true);
                TS_Hotel_Room_Availability::inst()->insertOrUpdate([
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


function trizen_helper_admin_script()
{
    $google_api_key = get_post_meta(get_the_ID(), 'gmap_apikey', true);
	wp_enqueue_style(
		'lib-admin-select2-css',
		'//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
	);
	wp_enqueue_style(
		'trizen-available-calendar-css',
		TRIZEN_HELPER_URI.('admin/css/trizen-available-calendar.css'),
		'',
		TRIZEN_HELPER_VERSION
	);
	wp_enqueue_style(
		'fullcalendar-min-css',
		TRIZEN_HELPER_URI.('admin/css/fullcalendar.min.css'),
		'',
		TRIZEN_HELPER_VERSION
	);
	wp_enqueue_style(
		'trizen-admin-global-css',
		TRIZEN_HELPER_URI.('admin/css/trizen-admin.css'),
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
		TRIZEN_HELPER_URI.('admin/js/moment.min.js'),
		array('jquery'),
		null,
		true
	);
    wp_enqueue_script(
        'lib-fullcalendar-js',
        TRIZEN_HELPER_URI . ('admin/js/fullcalendar.min.js'),
        array('jquery','lib-moment.js'),
        null,
        true
    );
    wp_enqueue_script(
        'st-qtip',
        TRIZEN_HELPER_URI . ('admin/js/jquery.qtip.js'),
        array('jquery'),
        null,
        true
    );
	wp_enqueue_script(
		'lib-gantt.js',
		TRIZEN_HELPER_URI.('admin/js/jquery.fn.gantt.js'),
		array('jquery', 'lib-moment.js'),
		null,
		true
	);
	/*wp_enqueue_script(
		'lib-locales-all-js',
		TRIZEN_HELPER_URI . ('admin/js/locales-all.js'),
		array('jquery', 'lib-fullcalendar-js'),
		null,
		true
	);*/
	wp_register_script(
		'bulk-calendar',
		TRIZEN_HELPER_URI . ('admin/js/bulk-calendar.js'),
		array('jquery'),
		null,
		true
	);
    /*wp_enqueue_script(
        'traveler-js',
        TRIZEN_HELPER_URI . ('admin/js/traveler.js'),
        null,
        TRIZEN_HELPER_VERSION,
        true
    );*/
    /*wp_enqueue_script(
        'lib-gmap3-js',
        TRIZEN_HELPER_URI . ('admin/js/gmap3.min.js'),
        ['jquery'],
        false,
        true
    );*/
    wp_enqueue_script(
        'trizen-hotel-gmap-js',
        TRIZEN_HELPER_URI . ('admin/js/ts_hotel_gmap.js'),
        null,
        TRIZEN_HELPER_VERSION,
        true
    );
    if(!empty($google_api_key)) {
        wp_enqueue_script(
            'lib-gmap-js',
            '//maps.googleapis.com/maps/api/js?key='.$google_api_key,
            null,
            '1.0',
            true
        );
    }
	wp_register_script(
		'trizen-hotel-inventory',
		TRIZEN_HELPER_URI . ('admin/js/trizen-hotel-inventory.js'),
		array('jquery'),
		null,
		true
	);
	wp_enqueue_script(
		'trizen-hotel-calendar-js',
		TRIZEN_HELPER_URI . ('admin/js/trizen-hotel-calendar.js'),
		null,
        TRIZEN_HELPER_VERSION,
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
		'please_waite'        => __('Please wait...', 'trizen-helper'),
		'prev_month'          => __('prev month', 'trizen-helper'),
		'next_month'          => __('next month', 'trizen-helper'),
		'currency_smbl'       => '$',
		'time_format'         => trizen_get_option('time_format', '12h'),
        'ts_search_nonce'     => wp_create_nonce("ts_search_security"),
	]);


	wp_localize_script('jquery', 'locale_daterangepicker', [
		'direction'        => (is_rtl() || trizen_get_option('right_to_left') == 'on')? 'rtl': 'ltr',
		'applyLabel'       => esc_html__('Apply', 'trizen-helper'),
		'cancelLabel'      => esc_html__('Cancel', 'trizen-helper'),
		'fromLabel'        => esc_html__('From', 'trizen-helper'),
		'toLabel'          => esc_html__('To', 'trizen-helper'),
		'customRangeLabel' => esc_html__('Custom', 'trizen-helper'),
		'daysOfWeek'       =>  [esc_html__('Su', 'trizen-helper'), esc_html__('Mo', 'trizen-helper'), esc_html__('Tu', 'trizen-helper'), esc_html__('We', 'trizen-helper'), esc_html__('Th', 'trizen-helper'), esc_html__('Fr', 'trizen-helper'), esc_html__('Sa', 'trizen-helper')],
		'monthNames'       => [esc_html__('January', 'trizen-helper'), esc_html__('February', 'trizen-helper'), esc_html__('March', 'trizen-helper'), esc_html__('April', 'trizen-helper'), esc_html__('May', 'trizen-helper'), esc_html__('June', 'trizen-helper'), esc_html__('July', 'trizen-helper'), esc_html__('August', 'trizen-helper'), esc_html__('September', 'trizen-helper'), esc_html__('October', 'trizen-helper'), esc_html__('November', 'trizen-helper'), esc_html__('December', 'trizen-helper')],
		'firstDay'         => (int)trizen_get_option('start_week', 0),
		'today'            => esc_html__('Today', 'trizen-helper'),
		'please_waite'     => esc_html__('Please wait...', 'trizen-helper'),
		'buttons'          => esc_html__('buttons', 'trizen-helper'),
	]);
}
add_action('admin_enqueue_scripts', 'trizen_helper_admin_script');

function trizen_helper_scripts() {
    wp_enqueue_style(
        'trizen-helper-css',
        TRIZEN_HELPER_URI.('assets/css/trizen-helper.css'),
        '',
        TRIZEN_HELPER_VERSION
    );

    wp_enqueue_script(
        'lib-moment-min',
        TRIZEN_HELPER_URI . ('assets/js/moment.min.js'),
        array('jquery'),
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'lib-daterangepicker-min',
        TRIZEN_HELPER_URI . ('assets/js/daterangepicker.js'),
        array('jquery'),
        '1.0.0',
        true
    );
	wp_enqueue_script('trizen-helper-js', TRIZEN_HELPER_URI .'assets/js/trizen-helper.js', array('jquery', 'trizen-js'), _S_VERSION, true);

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

if (!function_exists('ts_check_service_available')) {
    function ts_check_service_available($post_type = false) {
        if ($post_type) {
            if (function_exists('ts_options_id')) {
                $disable_list = ts_traveler_get_option('list_disabled_feature');
                $disable_list = is_array($disable_list) ? $disable_list : [];
                if (!empty($disable_list)) {
                    foreach ($disable_list as $key) {
                        if ($key == $post_type)
                            return false;
                    }
                }
            }
            return true;
        }
        return false;
    }
}

/** 1.1.4  */
if (!function_exists('ts_get_page_search_result')) {
    function ts_get_page_search_result($post_type) {
        if (empty($post_type))
            return;
        switch ($post_type) {
            case "ts_hotel":
            case "hotel_room":
                $page_search = trizen_get_option('hotel_search_result_page');
                break;
            case "ts_rental":
                $page_search = trizen_get_option('rental_search_result_page');
                break;
            case "ts_cars":
                $page_search = trizen_get_option('cars_search_result_page');
                break;
            case "ts_activity":
                $page_search = trizen_get_option('activity_search_result_page');
                break;
            case "ts_tours":
                $page_search = trizen_get_option('tours_search_result_page');
                break;
            default :
                $page_search = false;
        }
        return $page_search;
    }
}

function ts_get_data_location_from_to($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ts_location_relationships';
    $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d AND location_from <> '' AND location_to <> '' AND location_type = 'location_from_to'", $post_id);
    return $wpdb->get_results($sql, ARRAY_A);
}


if (!function_exists('ts_get_discount_value')) {
    function ts_get_discount_value($number, $percent = 0, $format_money = true) {
        if ($percent > 100)
            $percent = 100;
        $rs = $number - ($number / 100) * $percent;
        if ($format_money)
            return TravelHelper::format_money($rs);
        return $rs;
    }
}


if(!function_exists('ts_list_taxonomy')) {
    function ts_list_taxonomy($post_type='post'){
        $all = get_object_taxonomies($post_type,'objects');
        $result=array();
        $result[__('--Select--','trizen-helper')]=false;
        if(!empty($all))  {
            foreach($all as $key=>$value)
            {
                $result[$value->label]=$value->name;
            }
        }
        return $result;
    }
}

if (function_exists('ts_is_ajax') == false) {
    function ts_is_ajax() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } {
            return false;
        }
    }
}

//if(!function_exists('hotel_alone_is_ajax'))  {
    function hotel_alone_is_ajax() {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }
//}


