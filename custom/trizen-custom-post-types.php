
<?php
function trizen_custom_post_types() {
	/* Hotel Start */
	$labels = [
		'name'                  => __( 'Hotels', 'trizen-helper' ),
		'singular_name'         => __( 'Hotel', 'trizen-helper' ),
		'menu_name'             => __( 'Hotels', 'trizen-helper' ),
		'name_admin_bar'        => __( 'Hotel', 'trizen-helper' ),
		'add_new'               => __( 'Add New', 'trizen-helper' ),
		'add_new_item'          => __( 'Add New Hotel', 'trizen-helper' ),
		'new_item'              => __( 'New Hotel', 'trizen-helper' ),
		'edit_item'             => __( 'Edit Hotel', 'trizen-helper' ),
		'view_item'             => __( 'View Hotel', 'trizen-helper' ),
		'all_items'             => __( 'All Hotels', 'trizen-helper' ),
		'search_items'          => __( 'Search Hotels', 'trizen-helper' ),
		'parent_item_colon'     => __( 'Parent Hotels:', 'trizen-helper' ),
		'not_found'             => __( 'No hotels found.', 'trizen-helper' ),
		'not_found_in_trash'    => __( 'No hotels found in Trash.', 'trizen-helper' ),
		'insert_into_item'      => __( "Insert into Hotel", 'trizen-helper' ),
		'uploaded_to_this_item' => __( "Uploaded to this Hotel", 'trizen-helper' ),
		'featured_image'        => __( "Feature Image", 'trizen-helper' ),
		'set_featured_image'    => __( "Set featured image", 'trizen-helper' )
	];
	$args   = [
		'labels'              => $labels,
		'menu_icon'           => 'dashicons-building',
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'query_var'           => true,
		'rewrite'             => [ 'slug' => get_option( 'hotel_permalink', 'ts_hotel' ) ],
		'capability_type'     => 'post',
		'has_archive'         => true,
		'hierarchical'        => false,
		'exclude_from_search' => true,
		'supports'            => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ]
	];
	register_post_type( 'ts_hotel', $args );

	$name   = __( 'Hotel Facilities', 'trizen-helper' );
	$labels = [
		'name'              => $name,
		'singular_name'     => $name,
		'search_items'      => sprintf( __( 'Search %s', 'trizen-helper' ), $name ),
		'all_items'         => sprintf( __( 'All %s', 'trizen-helper' ), $name ),
		'parent_item'       => sprintf( __( 'Parent %s', 'trizen-helper' ), $name ),
		'parent_item_colon' => sprintf( __( 'Parent %s', 'trizen-helper' ), $name ),
		'edit_item'         => sprintf( __( 'Edit %s', 'trizen-helper' ), $name ),
		'update_item'       => sprintf( __( 'Update %s', 'trizen-helper' ), $name ),
		'add_new_item'      => sprintf( __( 'New %s', 'trizen-helper' ), $name ),
		'new_item_name'     => sprintf( __( 'New %s', 'trizen-helper' ), $name ),
		'menu_name'         => $name,
	];
	$args   = [
		'hierarchical' => true,
		'labels'       => $labels,
		'show_ui'      => 'edit.php?post_type=ts_hotel',
		'query_var'    => true,
		'show_admin_column' => true,
	];
	register_taxonomy( 'hotel_facilities', 'ts_hotel', $args );
	/* Hotel End */


	/* Location Start */
	$labels = array(
		'name'                  => __('Locations', 'trizen-helper'),
		'singular_name'         => __('Location', 'trizen-helper'),
		'menu_name'             => __('Locations', 'trizen-helper'),
		'name_admin_bar'        => __('Location', 'trizen-helper'),
		'add_new'               => __('Add New', 'trizen-helper'),
		'add_new_item'          => __('Add New Location', 'trizen-helper'),
		'new_item'              => __('New Location', 'trizen-helper'),
		'edit_item'             => __('Edit Location', 'trizen-helper'),
		'view_item'             => __('View Location', 'trizen-helper'),
		'all_items'             => __('All Locations', 'trizen-helper'),
		'search_items'          => __('Search Locations', 'trizen-helper'),
		'parent_item_colon'     => __('Parent Locations:', 'trizen-helper'),
		'not_found'             => __('No locations found.', 'trizen-helper'),
		'not_found_in_trash'    => __('No locations found in Trash.', 'trizen-helper'),
		'insert_into_item'      => __('Insert into Location', 'trizen-helper'),
		'uploaded_to_this_item' => __("Uploaded to this Location", 'trizen-helper'),
		'featured_image'        => __("Feature Image", 'trizen-helper'),
		'set_featured_image'    => __("Set featured image", 'trizen-helper')
	);
	$args = array(
		'labels'              => $labels,
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'query_var'           => true,
		'rewrite'             => array('slug' => get_option('location_permalink', 'ts_location')),
		'has_archive'         => true,
		'hierarchical'        => true,
		'supports'            => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes'),
		'menu_icon'           => 'dashicons-location-alt',
		'exclude_from_search' => true,
	);
	register_post_type('location', $args);

	// Location ==============================================================

	$labels = array(
		'name'                       => __('Location Type', 'trizen-helper'),
		'singular_name'              => __('Location Type', 'trizen-helper'),
		'search_items'               => __('Search Location Type', 'trizen-helper'),
		'popular_items'              => __('Popular Location Type', 'trizen-helper'),
		'all_items'                  => __('All Location Type', 'trizen-helper'),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __('Edit Location Type', 'trizen-helper'),
		'update_item'                => __('Update Location Type', 'trizen-helper'),
		'add_new_item'               => __('Add New Location Type', 'trizen-helper'),
		'new_item_name'              => __('New Location Type Name', 'trizen-helper'),
		'separate_items_with_commas' => __('Separate Location Type with commas', 'trizen-helper'),
		'add_or_remove_items'        => __('Add or remove Location Type', 'trizen-helper'),
		'choose_from_most_used'      => __('Choose from the most used Location Type', 'trizen-helper'),
		'not_found'                  => __('No Pickup Location Type.', 'trizen-helper'),
		'menu_name'                  => __('Location Type', 'trizen-helper'),
	);
	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
	);
	register_taxonomy('ts_location_type', 'location', $args);
	/* Location End */
}

add_action( 'init', 'trizen_custom_post_types' );

