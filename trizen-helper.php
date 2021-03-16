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
}


function trizen_helper_admin_script()
{
	wp_enqueue_style(
		'lib-admin-select2-css',
		'//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
	);
	wp_enqueue_style(
		'trizen-admin-global-css',
		TRIZEN_HELPER_URI.('assets/css/trizen-admin.css'),
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
		'trizen-helper-admin-js',
		TRIZEN_HELPER_URI.('assets/js/trizen-helper-admin.js'),
		'',
		TRIZEN_HELPER_VERSION,
		true
	);
}
add_action('admin_enqueue_scripts', 'trizen_helper_admin_script');

function trizen_helper_scripts() {
	wp_enqueue_script('trizen-helper-js', TRIZEN_HELPER_URI .'assets/js/trizen-helper.js', ['jquery'], TRIZEN_HELPER_VERSION, true);
	
	
    wp_localize_script('jquery', 'ts_params', [
        'theme_url' => get_template_directory_uri(),
        'site_url' => site_url(),
        'ajax_url' => admin_url('admin-ajax.php'),
        'loading_url' => admin_url('/images/wpspin_light.gif'),
        'st_search_nonce' => wp_create_nonce("st_search_security"),
        'free_text' => __('Free', 'trizen-helper'),
        'locale' => get_locale(),
        'text_refresh' => __("Refresh", 'trizen-helper'),
        'text_loading' => __("Loading...", 'trizen-helper'),
        'text_no_more' => __("No More", 'trizen-helper'),
        'no_vacancy' => __('No vacancies', 'trizen-helper'),
        'a_vacancy' => __('a vacancy', 'trizen-helper'),
        'more_vacancy' => __('vacancies', 'trizen-helper'),
        'utm' => (is_ssl() ? 'https' : 'http') . '://shinetheme.com/utm/utm.gif',
        '_s' => wp_create_nonce('ts_frontend_security'),
        'text_price' => __("Price", 'trizen-helper'),
        'text_origin_price' => __("Origin Price", 'trizen-helper'),
        'text_unavailable' => __('Not Available ', 'trizen-helper'),
        'text_available' => __('Available ', 'trizen-helper'),
        'text_adult_price' => __('Adult Price ', 'trizen-helper'),
        'text_child_price' => __('Child Price ', 'trizen-helper'),
        'text_update' => __('Update ', 'trizen-helper'),
        'text_adult' => __('Adult ', 'trizen-helper'),
        'text_child' => __('Child ', 'trizen-helper'),
        'text_use_this_media' => __('Use this media', 'trizen-helper'),
        'text_select_image' => __('Select Image', 'trizen-helper'),
        'text_confirm_delete_item' => __('Are you sure want to delete this item?', 'trizen-helper'),
        'text_process_cancel' => __('You cancelled the process', 'trizen-helper'),
        'prev_month' => __('prev month', 'trizen-helper'),
        'next_month' => __('next month', 'trizen-helper'),
        'please_waite' => __('Please wait...', 'trizen-helper'),
    ]);
}
add_action('wp_enqueue_scripts', 'trizen_helper_scripts');





function get_username($user_id) {
	$userdata = get_userdata($user_id);
	if (!$userdata) {
		return __('Customer', 'trizen-helper');
	}
	if ($userdata->display_name) {
		return $userdata->display_name;
	} elseif ($userdata->first_name || $userdata->last_name) {
		return $userdata->first_name . ' ' . $userdata->last_name;
	} else {
		return $userdata->user_login;
	}
}



