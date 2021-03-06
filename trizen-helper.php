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
}




