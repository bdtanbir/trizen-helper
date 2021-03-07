<?php

/**
 * Register meta boxes.
 */
if(!function_exists('trizen_register_meta_boxes')) {
	function trizen_register_meta_boxes() {
		add_meta_box(
			'trizen-course-badge-title',
			__( 'Course Badge Title', 'trizen-helper' ),
			'trizen_display_feature_callback',
			'ts_hotel',
			'normal',
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


		$fields = [
			'trizen_course_badge_title',
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
	function trizen_display_feature_callback() {
		require_once TRIZEN_HELPER_PATH.'custom/trizen-hotel-information-meta.php';
	}
}

