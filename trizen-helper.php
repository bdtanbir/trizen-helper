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


require_once TRIZEN_HELPER_PATH.'admin/inc/setting/trizen-option-setting.php';
add_action('plugins_loaded', 'trizen_helper_load');
function trizen_helper_load() {
require_once TRIZEN_HELPER_PATH.'custom/trizen-custom-metaboxes.php';
require_once TRIZEN_HELPER_PATH.'widgets/trizen-social-profile.php';
require_once TRIZEN_HELPER_PATH.'widgets/trizen-recent-post-with-thumbnail.php';
require_once TRIZEN_HELPER_PATH.'custom/trizen-custom-post-types.php';
require_once TRIZEN_HELPER_PATH.'custom/trizen-taxonomy-class.php';
require_once TRIZEN_HELPER_PATH.'custom/trizen-room-facilities-custom-icon-field.php';
require_once TRIZEN_HELPER_PATH.'widgets/trizen-hotel-organized-by.php';
require_once TRIZEN_HELPER_PATH.'widgets/trizen-hotel-room-booking-fields.php';
require_once TRIZEN_HELPER_PATH.'inc/trizen-helper.booking.php';
require_once TRIZEN_HELPER_PATH.'widgets/trizen-room-availability-form.php';
require_once TRIZEN_HELPER_PATH.'custom/trizen-helper.class-admin-room.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/database.helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.review.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.user.php';
require_once TRIZEN_HELPER_PATH.'inc/class.hotel-helper.php';
require_once TRIZEN_HELPER_PATH.'inc/class.hotel.search.php';
}
require_once TRIZEN_HELPER_PATH.'custom/trizen-availability-model.php';
require_once TRIZEN_HELPER_PATH.'inc/trizen-hook-function.php';

require_once TRIZEN_HELPER_PATH.'admin/inc/class.tsadmin.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/nested_sets_model.helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/availability.helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.neworder.data.php';
// if(is_admin()) {
    require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.availability.php';
    require_once TRIZEN_HELPER_PATH.'core/database/tables/ts_price.php';
// }
require_once TRIZEN_HELPER_PATH.'admin/inc/class.woocommerce.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/order.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.room.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.hotel.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.upgrade.data.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.duplicate.data.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/class.admin.location.relationships.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/travel-helper.php';
require_once TRIZEN_HELPER_PATH.'admin/inc/helper/hotel.helper.php';
require_once TRIZEN_HELPER_PATH.'inc/class.hotel.php';
require_once TRIZEN_HELPER_PATH.'inc/helper/price.helper.php';
require_once TRIZEN_HELPER_PATH.'inc/hotel-alone-helper.php';
require_once TRIZEN_HELPER_PATH.'inc/class.single_hotel.php';
require_once TRIZEN_HELPER_PATH.'core/database/tables/posts.php';
require_once TRIZEN_HELPER_PATH.'core/database/tables/availability.php';
require_once TRIZEN_HELPER_PATH.'core/database/tables/order_item.php';
require_once TRIZEN_HELPER_PATH.'core/database/tables/hotel_room_availability.php';




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
		'trizen-setting-panel-admin-css',
		TRIZEN_HELPER_URI.('admin/css/trizen-admin-setting-panel.css'),
		'',
		time()
	);
    wp_enqueue_style(
        'sweetalert2-min-css',
        TRIZEN_HELPER_URI.( 'admin/css/sweetalert2.min.css' )
    );
	wp_enqueue_style(
		'trizen-admin-global-css',
		TRIZEN_HELPER_URI.('admin/css/trizen-admin.css'),
		'',
		time()
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
        'trizen-hotel-gmap-js',
        TRIZEN_HELPER_URI . ('admin/js/ts_hotel_gmap.js'),
        null,
        time(),
        true
    );
    if(!empty($google_api_key)) {
        wp_enqueue_script(
            'lib-gmap-js',
            'https://maps.googleapis.com/maps/api/js?key='.$google_api_key.'&libraries=places',
            null,
            '1.0',
            true
        );
    }

    wp_enqueue_script(
        'lib-moment.js',
        TRIZEN_HELPER_URI.('admin/js/moment.min.js'),
        array('jquery'),
        null,
        true
    );
    wp_enqueue_script(
        'fullcalendar',
        TRIZEN_HELPER_URI . ('admin/js/fullcalendar.js'),
        array('jquery','lib-moment.js'),
        '1.0',
        true
    );
    wp_enqueue_script(
        'lib-jquery-gantt.js',
        TRIZEN_HELPER_URI.('admin/js/jquery.fn.gantt.js'),
        array('jquery', 'lib-moment.js'),
        null,
        true
    );
    wp_enqueue_script(
        'bulk-calendar',
        TRIZEN_HELPER_URI . ('admin/js/bulk-calendar.js'),
        array('jquery'),
        TRIZEN_HELPER_VERSION,
        true
    );

	wp_register_script(
		'trizen-hotel-inventory',
		TRIZEN_HELPER_URI . ('admin/js/trizen-hotel-inventory.js'),
		array('jquery'),
		TRIZEN_HELPER_VERSION,
		true
	);


	wp_enqueue_script(
		'trizen-hotel-calendar-js',
		TRIZEN_HELPER_URI . ('admin/js/trizen-hotel-calendar.js'),
		['jquery'],
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

    wp_enqueue_script(
        'trizen-admin-setting-panel-js',
        TRIZEN_HELPER_URI.( 'admin/js/trizen-admin-setting-panel.js' ),
        array('jquery'),
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
		'currency_smbl'       => class_exists( 'WooCommerce' ) ? get_woocommerce_currency_symbol() : '$',
		'time_format'         => '12h',
        'ts_search_nonce'     => wp_create_nonce("ts_search_security"),
	]);


	wp_localize_script('jquery', 'locale_daterangepicker', [
		'direction'        => (is_rtl()) ? 'rtl': 'ltr',
		'applyLabel'       => esc_html__('Apply', 'trizen-helper'),
		'cancelLabel'      => esc_html__('Cancel', 'trizen-helper'),
		'fromLabel'        => esc_html__('From', 'trizen-helper'),
		'toLabel'          => esc_html__('To', 'trizen-helper'),
		'customRangeLabel' => esc_html__('Custom', 'trizen-helper'),
		'daysOfWeek'       => [esc_html__('Su', 'trizen-helper'), esc_html__('Mo', 'trizen-helper'), esc_html__('Tu', 'trizen-helper'), esc_html__('We', 'trizen-helper'), esc_html__('Th', 'trizen-helper'), esc_html__('Fr', 'trizen-helper'), esc_html__('Sa', 'trizen-helper')],
		'monthNames'       => [esc_html__('January', 'trizen-helper'), esc_html__('February', 'trizen-helper'), esc_html__('March', 'trizen-helper'), esc_html__('April', 'trizen-helper'), esc_html__('May', 'trizen-helper'), esc_html__('June', 'trizen-helper'), esc_html__('July', 'trizen-helper'), esc_html__('August', 'trizen-helper'), esc_html__('September', 'trizen-helper'), esc_html__('October', 'trizen-helper'), esc_html__('November', 'trizen-helper'), esc_html__('December', 'trizen-helper')],
		'firstDay'         => (int)0,
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
        time()
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
	wp_enqueue_script('ts-duplicate-js', TRIZEN_HELPER_URI .'admin/js/ts-duplicate.js', array('jquery', 'trizen-js'), _S_VERSION, true);
	wp_enqueue_script('trizen-helper-js', TRIZEN_HELPER_URI .'assets/js/trizen-helper.js', array('jquery', 'trizen-js'), TRIZEN_HELPER_VERSION, true);

	wp_localize_script('jquery', 'ts_params', [
        'site_url'        => site_url(),
        'ajax_url'        => admin_url('admin-ajax.php'),
        'ts_search_nonce' => wp_create_nonce("ts_search_security"),
        '_s'              => wp_create_nonce('ts_frontend_security'),
    ]);

}
add_action('wp_enqueue_scripts', 'trizen_helper_scripts');

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

/**
 * @since 1.0
 */
if (!function_exists('ts_get_page_search_result')) {
    function ts_get_page_search_result($post_type) {
        if (empty($post_type))
            return;
        switch ($post_type) {
            case "ts_hotel":
            case "hotel_room":
                $page_search = trizen_get_option('hotel_search_result_page');
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



//if (!function_exists('ts_get_link_with_search')) {
    function ts_get_link_with_search($link = false, $need = array(), $data = array()) {
        $form_data = array();
        if (!empty($need)) {
            foreach ($need as $key) {
                if (isset($data[$key]) and $data[$key]) {
                    $form_data[$key] = $data[$key];
                }
            }
        }
        return esc_url(add_query_arg($form_data, $link));
    }
//}



function destroy_cart() {
    do_action( 'ts_before_destroy_cart' );

    delete_cart( 'ts_cart' );
    delete_cart( 'ts_cart_coupon' );

    do_action( 'ts_after_destroy_cart' );
}

function delete_cart( $cart_name ) {
    setcookie( $cart_name, '', time() - 3600 );
}

add_action( 'init', '_remove_cart' );
function _remove_cart() {
    if (get('action', '') === 'ts-remove-cart' && wp_verify_nonce(get('security', ''), 'ts-security')) {
        if (class_exists('WC_Product')) {
            global $woocommerce;
            WC()->cart->empty_cart();
        }
        destroy_cart();
        wp_redirect(remove_query_arg(['action', 'security']));
        exit();
    }
}



add_filter('ts_is_woocommerce_checkout', 'ts_check_is_checkout_woocomerce');
if (!function_exists('ts_check_is_checkout_woocomerce')) {
    function ts_check_is_checkout_woocomerce($check) {
        if (class_exists('Woocommerce')) {
            $check = true;
        } else {
            $check = false;
        }
        return $check;
    }
}
