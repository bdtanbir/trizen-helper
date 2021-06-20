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
			'trizen-hotel-badge-meta',
			__( 'Badge Title', 'trizen-helper' ),
			'trizen_hotel_badge_callback',
			'ts_hotel',
			'side',
			'low'
		);
		add_meta_box(
			'trizen-hotel-room-infos-meta',
			__( 'Room Information', 'trizen-helper' ),
			'trizen_hotel_room_infos_callback',
			'hotel_room',
			'advanced',
			'high'
		);
		add_meta_box(
			'trizen-select-country-meta',
			__( 'Select Country', 'trizen-helper' ),
			'trizen_select_country_callback',
			'location',
			'side',
			'low'
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

        // If the checkbox was not empty, save it as array in post meta
        if ( ! empty( $_POST['multi_location'] ) ) {
            update_post_meta( $post_id, 'multi_location', $_POST['multi_location'] );

            // Otherwise just delete it if its blank value.
        } else {
            delete_post_meta( $post_id, 'multi_location' );
        }

        // If the checkbox was not empty, save it as array in post meta
        if ( ! empty( $_POST['multi_location'] ) ) {
            update_post_meta( $post_id, 'multi_location', $_POST['multi_location'] );

            // Otherwise just delete it if its blank value.
        } else {
            delete_post_meta( $post_id, 'multi_location' );
        }

        $visible = isset( $_POST['enable_google_map'] ) && $_POST['enable_google_map'] == 1;
        $visible = (int)$visible;
        update_post_meta( $post_id,  'enable_google_map', $visible );

        $visible = isset( $_POST['enable_is_auto_calculate'] ) && $_POST['enable_is_auto_calculate'] == 1;
        $visible = (int)$visible;
        update_post_meta( $post_id,  'enable_is_auto_calculate', $visible );

		$fields = [
			'address',
			'trizen_hotel_video_url',
			'price_avg',
			'trizen_hotel_sale_price',
			'trizen_hotel_features_title',
			'trizen_hotel_features_stitle',
			'trizen_hotel_features_icon',
			'trizen_hotel_faqs_title',
			'trizen_hotel_faqs_content',
			'trizen_hotel_badge_title',
			'lat',
			'lng',
			'zoom',
			'gmap_apikey',
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
			$trizen_hotel_features_title  = $_POST['trizen_hotel_features_title'];
			$trizen_hotel_features_stitle =   $_POST['trizen_hotel_features_stitle'];
			$trizen_hotel_features_icon   =     $_POST['trizen_hotel_features_icon'];
			$count = count( $trizen_hotel_features_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_hotel_features_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_hotel_features_title']  = stripslashes( strip_tags( $trizen_hotel_features_title[ $i ] ) );
					$newfield[ $i ]['trizen_hotel_features_stitle'] =   stripslashes( $trizen_hotel_features_stitle[ $i ] ); // and however you want to sanitize
					$newfield[ $i ]['trizen_hotel_features_icon']   =     stripslashes( $trizen_hotel_features_icon[ $i ] ); // and however you want to sanitize
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
					$newfield[ $i ]['trizen_hotel_faqs_content'] =  stripslashes( $trizen_hotel_faqs_content[ $i ] ); // and however you want to sanitize
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
	function trizen_save_location_meta_box( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		$fields = [
			'location_country',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field, wp_kses_post( $_POST[$field] ) );
			}
		}
	}
	add_action( 'save_post_location', 'trizen_save_location_meta_box', 10, 2 );


	/**
	 * Save meta box content.
	 *
	 * @param $post_id
	 */
	function trizen_hotel_room_save_meta_box( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		$room_gallery_meta_key = 'trizen_hotel_room_image_gallery';
		update_post_meta( $post_id, $room_gallery_meta_key, $_POST[$room_gallery_meta_key] );


		if (isset($_POST['room_parent'])) {
			update_post_meta( $post_id, 'room_parent', $_POST['room_parent']);
		}
		/*if (isset($_POST['discount_type_no_day'])) {
			update_post_meta( $post_id, 'discount_type_no_day', $_POST['discount_type_no_day']);
		}*/
		if (isset($_POST['default_state'])) {
			update_post_meta( $post_id, 'default_state', $_POST['default_state']);
		}

		$fields = [
			'price',
			'number_room',
			'room_badge_title',
			'trizen_hotel_room_extra_service_title',
			'trizen_hotel_room_extra_service_price',
			'trizen_hotel_room_extra_service_price_designation',
			'trizen_room_facility_num_of_adults',
			'trizen_room_facility_num_of_beds',
			'trizen_hotel_room_footage',
			'trizen_room_other_facility_title',
			'trizen_room_rules_title',
			'discount_rate',
			'address',
			'discount_type_no_day',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
			}
		}


		/* Hotel Room Extra Services */
		$room_extra_service_oldfield = get_post_meta($post_id, 'trizen_hotel_extra_services_data_group', true);
		if ( $_POST['trizen_hotel_room_extra_service_title'] ) {
			$newfield                                          = array();
			$trizen_hotel_room_extra_service_title             = $_POST['trizen_hotel_room_extra_service_title'];
			$trizen_hotel_room_extra_service_price             = $_POST['trizen_hotel_room_extra_service_price'];
			$trizen_hotel_room_extra_service_price_designation = $_POST['trizen_hotel_room_extra_service_price_designation'];
			$count                                             = count( $trizen_hotel_room_extra_service_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_hotel_room_extra_service_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_hotel_room_extra_service_title']             = stripslashes( strip_tags( $trizen_hotel_room_extra_service_title[ $i ] ) );
					$newfield[ $i ]['trizen_hotel_room_extra_service_price']             = stripslashes( $trizen_hotel_room_extra_service_price[ $i ] ); // and however you want to sanitize
					$newfield[ $i ]['trizen_hotel_room_extra_service_price_designation'] = stripslashes( $trizen_hotel_room_extra_service_price_designation[ $i ] ); // and however you want to sanitize
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $room_extra_service_oldfield )
			update_post_meta( $post_id, 'trizen_hotel_extra_services_data_group', $newfield );
		elseif ( empty($newfield) && $room_extra_service_oldfield )
			delete_post_meta( $post_id, 'trizen_hotel_extra_services_data_group', $room_extra_service_oldfield );



		/* Hotel Room other facility */
		$room_other_facility_oldfield = get_post_meta($post_id, 'trizen_room_other_facility_data_group', true);
		if ( $_POST['trizen_room_other_facility_title'] ) {
			$newfield                         = array();
			$trizen_room_other_facility_title = $_POST['trizen_room_other_facility_title'];
			$count                            = count( $trizen_room_other_facility_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_room_other_facility_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_room_other_facility_title'] = stripslashes( strip_tags( $trizen_room_other_facility_title[ $i ] ) );
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $room_other_facility_oldfield )
			update_post_meta( $post_id, 'trizen_room_other_facility_data_group', $newfield );
		elseif ( empty($newfield) && $room_other_facility_oldfield )
			delete_post_meta( $post_id, 'trizen_room_other_facility_data_group', $room_other_facility_oldfield );


		/* Hotel Room Rules */
		$room_rules_oldfield = get_post_meta($post_id, 'trizen_room_rules_data_group', true);
		if ( $_POST['trizen_room_rules_title'] ) {
			$newfield                = array();
			$trizen_room_rules_title = $_POST['trizen_room_rules_title'];
			$count                   = count( $trizen_room_rules_title );
			for ( $i = 0; $i < $count; $i ++ ) {
				if ( $trizen_room_rules_title[ $i ] != '' ) :
					$newfield[ $i ]['trizen_room_rules_title'] = stripslashes( strip_tags( $trizen_room_rules_title[ $i ] ) );
				endif;
			}
		}
		if ( !empty( $newfield ) && $newfield != $room_rules_oldfield )
			update_post_meta( $post_id, 'trizen_room_rules_data_group', $newfield );
		elseif ( empty($newfield) && $room_rules_oldfield )
			delete_post_meta( $post_id, 'trizen_room_rules_data_group', $room_rules_oldfield );
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

	/**
	 * Meta box display callback.
	 */
	function trizen_hotel_badge_callback() {
		$badge_title = get_post_meta(get_the_ID(), 'trizen_hotel_badge_title', true);
		?>
		<div class="form-group">
			<label for="trizen_hotel_badge_title">
				<?php esc_html_e('Badge Title', 'trizen-helper'); ?>
			</label>
			<input
				id="trizen_hotel_badge_title"
				class="widefat"
				name="trizen_hotel_badge_title"
				type="text"
				value="<?php echo esc_attr($badge_title); ?>">
		</div>
	<?php }

    /**
     * Meta box display callback.
     */
    function trizen_select_country_callback() {
        $location_country = get_post_meta(get_the_ID(), 'location_country', true);
    ?>
        <div class="form-group">
            <?php
                $locations = TravelHelper::_get_location_country();
            ?>
            <select name="location_country" id="location_country" class="select-to-select2">
                <?php foreach ($locations as $location) { ?>
                    <option value="<?php echo esc_attr($location['value']); ?>" <?php echo selected( $location['value'], $location_country ); ?>>
                        <?php echo esc_html($location['label']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    <?php
    }
}


