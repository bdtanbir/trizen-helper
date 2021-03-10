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
		add_meta_box(
			'trizen-hotel-room-infos-meta',
			__( 'Room Information', 'trizen-helper' ),
			'trizen_hotel_room_infos_callback',
			'hotel_room',
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
			'trizen_hotel_regular_price',
			'trizen_hotel_sale_price',
			'trizen_hotel_features_title',
			'trizen_hotel_features_stitle',
			'trizen_hotel_features_icon',
			'trizen_hotel_faqs_title',
			'trizen_hotel_faqs_content',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field, wp_kses_post( $_POST[$field] ) );
			}
		}




		/* Hotel Features */
		$oldfield = get_post_meta($post_id, 'trizen_hotel_features_data_group', true);
		if($_POST['trizen_hotel_features_title']) {
			$newfield      = array();
			$trizen_hotel_features_title   = $_POST['trizen_hotel_features_title'];
			$trizen_hotel_features_stitle = $_POST['trizen_hotel_features_stitle'];
			$trizen_hotel_features_icon = $_POST['trizen_hotel_features_icon'];
			$count = count( $trizen_hotel_features_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_hotel_features_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_hotel_features_title']   = stripslashes( strip_tags( $trizen_hotel_features_title[ $i ] ) );
					$newfield[ $i ]['trizen_hotel_features_stitle'] = stripslashes( $trizen_hotel_features_stitle[ $i ] ); // and however you want to sanitize
					$newfield[ $i ]['trizen_hotel_features_icon'] = stripslashes( $trizen_hotel_features_icon[ $i ] ); // and however you want to sanitize
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $oldfield )
			update_post_meta( $post_id, 'trizen_hotel_features_data_group', $newfield );
		elseif ( empty($newfield) && $oldfield )
			delete_post_meta( $post_id, 'trizen_hotel_features_data_group', $oldfield );


		/* Hotel Faqs */
		$oldfield = get_post_meta($post_id, 'trizen_hotel_faqs_data_group', true);
		if($_POST['trizen_hotel_faqs_title']) {
			$newfield      = array();
			$trizen_hotel_faqs_title   = $_POST['trizen_hotel_faqs_title'];
			$trizen_hotel_faqs_content = $_POST['trizen_hotel_faqs_content'];
			$count = count( $trizen_hotel_faqs_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_hotel_faqs_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_hotel_faqs_title']   = stripslashes( strip_tags( $trizen_hotel_faqs_title[ $i ] ) );
					$newfield[ $i ]['trizen_hotel_faqs_content'] = stripslashes( $trizen_hotel_faqs_content[ $i ] ); // and however you want to sanitize
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $oldfield )
			update_post_meta( $post_id, 'trizen_hotel_faqs_data_group', $newfield );
		elseif ( empty($newfield) && $oldfield )
			delete_post_meta( $post_id, 'trizen_hotel_faqs_data_group', $oldfield );

	}
	add_action( 'save_post_ts_hotel', 'trizen_save_meta_box', 10, 2 );


	/**
	 * Save meta box content.
	 *
	 * @param $post_id
	 */
	function trizen_hotel_room_save_meta_box( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		$room_gallery_meta_key = 'trizen_hotel_room_image_gallery';
		update_post_meta( $post_id, $room_gallery_meta_key, $_POST[$room_gallery_meta_key] );


		if (isset($_POST['trizen_hotel_room_select'])) {
			update_post_meta( $post_id, 'trizen_hotel_room_select', $_POST['trizen_hotel_room_select']);
		}

		$fields = [
			'trizen_room_price',
			'trizen_hotel_room_number',
			'trizen_hotel_room_extra_service_title',
			'trizen_hotel_room_extra_service_price',
			'trizen_hotel_room_extra_service_price_designation',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field, wp_kses_post( $_POST[$field] ) );
			}
		}





		/* Hotel Room Extra Services */
		$room_extra_service_oldfield = get_post_meta($post_id, 'trizen_hotel_extra_services_data_group', true);
		if($_POST['trizen_hotel_room_extra_service_title']) {
			$newfield      = array();
			$trizen_hotel_room_extra_service_title   = $_POST['trizen_hotel_room_extra_service_title'];
			$trizen_hotel_room_extra_service_price = $_POST['trizen_hotel_room_extra_service_price'];
			$trizen_hotel_room_extra_service_price_designation = $_POST['trizen_hotel_room_extra_service_price_designation'];
			$count = count( $trizen_hotel_room_extra_service_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_hotel_room_extra_service_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_hotel_room_extra_service_title']   = stripslashes( strip_tags( $trizen_hotel_room_extra_service_title[ $i ] ) );
					$newfield[ $i ]['trizen_hotel_room_extra_service_price'] = stripslashes( $trizen_hotel_room_extra_service_price[ $i ] ); // and however you want to sanitize
					$newfield[ $i ]['trizen_hotel_room_extra_service_price_designation'] = stripslashes( $trizen_hotel_room_extra_service_price_designation[ $i ] ); // and however you want to sanitize
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $room_extra_service_oldfield )
			update_post_meta( $post_id, 'trizen_hotel_extra_services_data_group', $newfield );
		elseif ( empty($newfield) && $room_extra_service_oldfield )
			delete_post_meta( $post_id, 'trizen_hotel_extra_services_data_group', $room_extra_service_oldfield );
	}
	add_action( 'save_post_hotel_room', 'trizen_hotel_room_save_meta_box', 10, 2 );



	/**
	 * Meta box display callback.
	 */
	function trizen_hotel_infos_callback() {
		require_once TRIZEN_HELPER_PATH.'custom/trizen-hotel-information-meta.php';
	}

	/**
	 * Meta box display callback.
	 */
	function trizen_hotel_room_infos_callback() {
		require_once TRIZEN_HELPER_PATH.'custom/trizen-hotel-room-information-meta.php';
	}
}


