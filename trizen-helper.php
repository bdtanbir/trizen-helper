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
}


function trizen_helper_admin_script()
{
	wp_enqueue_style(
		'trizen-admin-global-css',
		TRIZEN_HELPER_URI.('assets/css/trizen-admin.css'),
		'',
		TRIZEN_HELPER_VERSION
	);

	if ( ! did_action( 'wp_enqueue_media' ) )
		wp_enqueue_media();
	wp_enqueue_script(
		'trizen-helper-admin-js',
		TRIZEN_HELPER_URI.('assets/js/trizen-helper-admin.js'),
		array('jquery'),
		TRIZEN_HELPER_VERSION,
		true
	);
}
add_action('admin_enqueue_scripts', 'trizen_helper_admin_script');




