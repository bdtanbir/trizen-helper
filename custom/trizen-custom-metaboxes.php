<?php

/**
 * Register meta boxes.
 */
if(!function_exists('trizen_register_meta_boxes')) {


	function trizen_register_meta_boxes() {
		add_meta_box(
			'trizen-hotel-infos-meta',
			__( 'Hotel Information', 'trizen-helper' ),
			'trizen_hotel_infos_callback',
			'ts_hotel',
			'advanced',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'trizen_register_meta_boxes' );


	/**
	 * Save meta box content.
	 *
	 * @param $post_id
	 */
	function trizen_save_meta_box( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		$meta_key = 'trizen_hotel_image_gallery';
		update_post_meta( $post_id, $meta_key, $_POST[$meta_key] );


		$fields = [
			'trizen_hotel_address_title',
			'trizen_hotel_video_url',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field, wp_kses_post( $_POST[$field] ) );
			}
		}

	}
	add_action( 'save_post_ts_hotel', 'trizen_save_meta_box', 10, 2 );



	/**
	 * Meta box display callback.
	 */
	function trizen_hotel_infos_callback() {
		require_once TRIZEN_HELPER_PATH.'custom/trizen-hotel-information-meta.php';
	}
}


