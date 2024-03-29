<?php
global $post;
$room_price         = get_post_meta( get_the_ID(), 'price', true );
$number_of_room     = get_post_meta( get_the_ID(), 'number_room', true );
$number_of_adults   = get_post_meta( get_the_ID(), 'adult_number', true );
$number_of_children = get_post_meta( get_the_ID(), 'children_number', true );
$number_of_beds     = get_post_meta( get_the_ID(), 'bed_number', true );
$room_footage       = get_post_meta( get_the_ID(), 'trizen_hotel_room_footage', true );
$room_badge_title   = get_post_meta( get_the_ID(), 'room_badge_title', true );
$room_address       = get_post_meta( get_the_ID(), 'address', true );

// Hotel Room Extra Services
// $trizen_hotel_room_extra_services_data = get_post_meta( get_the_ID(), 'extra_services', true );

$trizen_room_other_facility_data = get_post_meta( get_the_ID(), 'trizen_room_other_facility_data_group', true );
$trizen_room_rules_data          = get_post_meta( get_the_ID(), 'trizen_room_rules_data_group', true );
$discount_rate                   = get_post_meta( get_the_ID(), 'discount_rate', true );
$discount_type                   = get_post_meta( get_the_ID(), 'discount_type_no_day', true );
$hotel_rooms_select              = get_post_meta( get_the_ID(), 'room_parent', true );
$allow_full_day_booking          = get_post_meta( get_the_ID(), 'allow_full_day', true );


$html_location  = TravelHelper::treeLocationHtml();
$multi_location = maybe_unserialize(get_post_meta(get_the_ID(), 'multi_location', true));
if (!empty($multi_location) && !is_array($multi_location)) {
    $multi_location = explode(',', $multi_location);
}
if (empty($multi_location)) {
    $multi_location = array('');
}


$default = array(
	'post_type'      => 'ts_hotel',
	'posts_per_page' => -1
);
$hotel_rooms = new WP_Query($default);
?>

<div class="trizen-hotel-information-wrap trizen-hotel-room-information-wrap">
	<div class="nav-pill-main-div">
		<ul class="tabs nav-justified">
			<li class="tab-link current nav-pill" href="tab-room-location">
				<?php esc_html_e('Location', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-general">
				<?php esc_html_e('General', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-price">
				<?php esc_html_e('Price', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-facility">
				<?php esc_html_e('Room Facility', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-other-facility">
				<?php esc_html_e('Other Facility', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-rules">
				<?php esc_html_e('Rules', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-availability">
				<?php esc_html_e('Availability', 'trizen-helper'); ?>
			</li>
		</ul>
		<div class="trizen-hotel-infos-content">
            <div class="tab-content current" id="tab-room-location">
                <div class="form-settings" id="hotel_location_setting">
                    <label for="hotel_room_location_srch" class="title">
                        <?php esc_html_e('Location', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter location of room', 'trizen-helper'); ?>
                    </span>

                    <div class="ts-select-location">
                        <input id="hotel_room_location_srch" placeholder="<?php esc_html_e('Type to search', 'trizen-helper'); ?>" type="text"
                               class="widefat form-control" name="search" value="">
                        <div class="location-list-wrapper">
                            <?php

                            if (is_array($html_location) && count($html_location)):
                                foreach ($html_location as $key => $location):
                                    ?>
                                    <div class="location-list" data-name="<?php echo esc_attr($location['parent_name']); ?>" style="margin-left: <?php echo esc_attr( $location['level']) . 'px;'; ?>">
                                        <label for="<?php echo esc_attr__('location-', 'trizen-helper') . esc_attr($location['ID']); ?>">
                                            <input <?php if (in_array('_' . $location['ID'] . '_', $multi_location)) echo esc_attr__('checked', 'trizen-helper'); ?>
                                                    type="checkbox"
                                                    id="<?php echo esc_attr__('location-', 'trizen-helper') . esc_attr($location['ID']); ?>"
                                                    value="<?php echo '_' . esc_attr($location['ID']) . '_'; ?>"
                                                    name="multi_location[]"
                                                    data-post-id="<?php echo esc_attr($location['post_id']); ?>"
                                                    data-parent="<?php echo esc_attr($location['parent_id']); ?>">
                                            <span><?php echo esc_attr($location['post_title']); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; endif;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-settings" id="room_address_setting">
                    <label for="price" class="title">
                        <?php esc_html_e('Room Address', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter Full address of room', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="address"
                            name="address"
                            type="text"
                            value="<?php echo esc_attr($room_address); ?>"
                            placeholder="<?php esc_attr_e('Address', 'trizen-helper'); ?>" />
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tab-room-availability">
            <?php
                require_once TRIZEN_HELPER_PATH . '/custom/trizen-room-availability.php';
            ?>
            </div>
			<div class="tab-content" id="tab-room-general">
                <div class="form-settings" id="room_gallery_img">
                    <label for="trizen_hotel_room_gallery_image" class="title">
						<?php esc_html_e('Gallery', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Upload Images to make a gallery image for room.', 'trizen-helper'); ?>
                    </span>

                    <div>
                        <ul class="trizen_hotel_room_img_gallery_mtb">
                        <?php
                        $hidden = array();
                        if ( $images = get_posts( array(
                            'post_type'      => 'attachment',
                            'orderby'        => 'post__in',
                            'order'          => 'ASC',
                            'post__in'       => explode( ',', get_post_meta( get_the_ID(), 'trizen_hotel_room_image_gallery', true ) ),
                            'numberposts'    => - 1,
                            'post_mime_type' => 'image'
                        ) ) ) {
                            foreach ( $images as $image ) {
                                $hidden[]  = $image->ID;
                                $image_src = wp_get_attachment_image_src( $image->ID, array( 80, 80 ) );
                                $image_src = str_replace( '-150x150', '', $image_src );
                                echo      '<li data-id="' . esc_attr($image->ID) . '">
                                <img src="' . $image_src[0] . '" alt="' . esc_attr__( "Image", "trizen-helper" ) . '"><a href="#" class="trizen_hotel_room_img_gallery_remove">' . esc_html__( "+", "trizen-helper" ) . '</a></li>';
                            }
                        }
                        ?>
                        </ul>
                        <div style="clear:both"></div>
                    </div>
                    <input type="hidden" name="trizen_hotel_room_image_gallery" value="<?php echo join( ',', $hidden ); ?>" />
                    <a href="#" class="button trizen-btn trizen_upload_hotel_room_gallery_button">
                        <?php esc_html_e("Add Images", "trizen-helper"); ?>
                    </a>

                </div>

				<div class="form-settings" id="hotel_room_select">
					<label for="room_parent" class="title">
						<?php esc_html_e('Hotel Room', 'trizen-helper'); ?>
					</label>
					<span class="description">
                        <?php esc_html_e('Select a hotel for this type of room', 'trizen-helper'); ?>
                    </span>
                    <select id="room_parent" class="select-to-select2" name="room_parent">
                        <?php while ($hotel_rooms->have_posts()) { $hotel_rooms->the_post();
	                        $title_one  = get_the_title();
	                        $postid_one = get_the_ID();
	                        echo '
	                        <option value="'.esc_attr($postid_one).'" '.selected( $postid_one, $hotel_rooms_select, false ).'>
	                            '.esc_html($title_one).'
                            </option>
	                        ';
                        } ?>
                    </select>
				</div>

                <div class="form-settings" id="hotel_room_number">
                    <label for="number_room" class="title">
						<?php esc_html_e('Number of Rooms', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Number of available rooms for booking', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="number_room"
                            name="number_room"
                            type="text"
                            value="<?php echo esc_attr($number_of_room); ?>"
                            placeholder="<?php esc_attr_e('Number of rooms', 'trizen-helper'); ?>" />
                    </div>
                </div>

                <div class="form-settings" id="hotel_room_number">
                    <label for="room_badge_title" class="title">
						<?php esc_html_e('Badge Title', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter room badge title here.', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="room_badge_title"
                            name="room_badge_title"
                            type="text"
                            value="<?php echo esc_attr($room_badge_title); ?>" />
                    </div>
                </div>
			</div>
			<div class="tab-content" id="tab-room-price">
				<div class="form-settings" id="room_price_setting">
					<label for="price" class="title">
						<?php esc_html_e('Price', 'trizen-helper'); echo __(' (', 'trizen-helper').class_exists( 'WooCommerce' ) ? get_woocommerce_currency_symbol() : '$'.__(')', 'trizen-helper'); ?>
					</label>
					<span class="description">
                        <?php esc_html_e('The price of room per one night', 'trizen-helper'); ?>
                    </span>
					<div class="form-input">
						<input
							id="price"
							name="price"
							type="text"
							value="<?php echo esc_attr($room_price); ?>"
							placeholder="<?php esc_attr_e('Price', 'trizen-helper'); ?>" />
					</div>
				</div>


                <div class="form-settings" id="allow_full_day_setting">
                    <label for="allow_full_day" class="title">
                        <?php esc_html_e('Allowed Full Day Booking', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enable this option if you want to show google map', 'trizen-helper'); ?>
                    </span>
                    <?php
                    $is_checked = ($allow_full_day_booking == 1) ? 'checked' : '';
                    ?>
                    <div class="form-input">
                        <div class="nice-checkbox">
                            <input
                                    type="checkbox"
                                    name="allow_full_day"
                                    id="allow_full_day"
                                    value="<?php esc_attr_e('1', 'trizen-helper'); ?>"
                                <?php echo esc_attr($is_checked); ?>/>
                            <span></span>
                        </div>
                    </div>
                </div>

                <div class="form-settings" id="room_discount_type_setting">
                    <label for="discount_type_no_day" class="title">
                        <?php esc_html_e('Discount Type', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('This only use for discount by number of days. Calculation by: % or fixed', 'trizen-helper'); ?>
                    </span>
                    <select name="discount_type_no_day" id="discount_type_no_day">
                        <option value="percent" <?php if($discount_type === 'percent') esc_attr_e('selected', 'trizen-helper'); ?>>
                            <?php esc_html_e('Percent', 'trizen-helper'); ?>
                        </option>
                        <option value="amount" <?php if($discount_type === 'amount') esc_attr_e('selected', 'trizen-helper'); ?>>
                            <?php esc_html_e('Amount', 'trizen-helper'); ?>
                        </option>
                    </select>
                </div>

                <div class="form-settings" id="room_discount_rate_field">
                    <div class="form-group">
                        <label for="discount_rate" class="title">
                            <?php esc_html_e('Discount Rate (%)', 'trizen-helper'); ?>
                        </label>
                        <input type="text" name="discount_rate" id="discount_rate" value="<?php echo esc_attr($discount_rate); ?>" />
                    </div>
                </div>
			</div>
            <div class="tab-content" id="tab-room-facility">
                <div class="form-settings" id="room_number_of_beds_setting">
                    <label for="bed_number" class="title">
			            <?php esc_html_e('Number of Beds', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Number of beds in room', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input w-600">
                        <input
                            id="bed_number"
                            name="bed_number"
                            class="bed_number"
                            type="range"
                            value="<?php if(!empty($number_of_beds)) { echo esc_attr($number_of_beds); } else { esc_attr_e('0','trizen-helper');} ?>" />
                        <output class="range2-bubble"></output>
                    </div>
                </div>

                <div class="form-settings" id="room_number_of_adults_setting">
                    <label for="adult_number" class="title">
			            <?php esc_html_e('Number of adults', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Number of adults in room', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input w-600">
                        <input
                            id="adult_number"
                            name="adult_number"
                            class="adult_number"
                            type="range"
                            value="<?php if(!empty($number_of_adults)) { echo esc_attr($number_of_adults); } else { esc_attr_e('0','trizen-helper');} ?>"
                            placeholder="<?php esc_attr_e('Number of adults', 'trizen-helper'); ?>" />
                        <output class="range-bubble"></output>
                    </div>
                </div>

                <div class="form-settings" id="room_number_of_children_setting">
                    <label for="children_number" class="title">
			            <?php esc_html_e('Number of children', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Number of children in room', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input w-600">
                        <input
                            id="children_number"
                            name="children_number"
                            class="children_number"
                            type="range"
                            value="<?php if(!empty($number_of_children)) { echo esc_attr($number_of_children); } else { esc_attr_e('0','trizen-helper');} ?>"
                            placeholder="<?php esc_attr_e('Number of children', 'trizen-helper'); ?>" />
                        <output class="range3-bubble"></output>
                    </div>
                </div>

                <div class="form-settings" id="room_footage_setting">
                    <label for="trizen_hotel_room_footage" class="title">
			            <?php esc_html_e('Room Footage (square feet)', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Room footage (square feet)', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_room_footage"
                            name="trizen_hotel_room_footage"
                            class="trizen_hotel_room_footage"
                            type="text"
                            value="<?php echo esc_attr($room_footage); ?>" />
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tab-room-other-facility">
                <div class="form-settings" id="room_other_facility_setting">
                    <label for="trizen_room_other_facility" class="title">
			            <?php esc_html_e('Other Facility', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Room\'s other Facility', 'trizen-helper'); ?>
                    </span>

                    <!-- Start -->
                    <script type="text/html" id="tmpl-repeater4">
                        <div class="field-group">
                            <label for="trizen_room_other_facility_title">
                                <span>
                                    <?php esc_html_e('Title', 'trizen-helper'); ?>
                                </span>
                                <input id="trizen_room_other_facility_title" type="text" name="trizen_room_other_facility_title[]" value="" />
                            </label>

                            <button type="button" class="button button-secondary trizen_room_other_facility_remove dashicons dashicons-trash">
                            </button>
                        </div>
                    </script>

                    <div id="trizen_room_other_facility_data" class="trizen-room-other-facility-metabox">
		                <?php
		                if( !empty( $trizen_room_other_facility_data ) ) {
			                foreach( $trizen_room_other_facility_data as $index => $field ) { ?>
                                <div class="field-group">
                                    <label for="trizen_room_other_facility_title-<?php echo esc_attr($index); ?>">
                                        <span>
                                            <?php esc_html_e('Title', 'trizen-helper'); ?>
                                        </span>
                                        <input id="trizen_room_other_facility_title-<?php echo esc_attr($index); ?>" type="text" name="trizen_room_other_facility_title[]" value="<?php if($field['trizen_room_other_facility_title'] != '') echo esc_attr( $field['trizen_room_other_facility_title'] ); ?>" />
                                    </label>

                                    <button type="button" class="button button-secondary trizen_room_other_facility_remove dashicons dashicons-trash">
                                    </button>
                                </div>
				                <?php
			                }
		                } ?>
                    </div>
                    <button type="button" id="trizen_room_other_facility_add" class="button trizen-btn">
		                <?php esc_html_e('Add', 'trizen-helper'); ?>
                    </button>
                    <!-- End -->
                </div>
            </div>
            <div class="tab-content" id="tab-room-rules">
                <div class="form-settings" id="room_rules_setting">
                    <label for="trizen_room_rules" class="title">
						<?php esc_html_e('Rules', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Room rules', 'trizen-helper'); ?>
                    </span>

                    <!-- Start -->
                    <script type="text/html" id="tmpl-repeater5">
                        <div class="field-group">
                            <label for="trizen_room_rules_title">
                                <span>
                                    <?php esc_html_e('Title', 'trizen-helper'); ?>
                                </span>
                                <input id="trizen_room_rules_title" type="text" name="trizen_room_rules_title[]" value="" />
                            </label>

                            <button type="button" class="button button-secondary trizen_room_rules_remove dashicons dashicons-trash">
                            </button>
                        </div>
                    </script>

                    <div id="trizen_room_rules_data" class="trizen-room-rules-metabox">
						<?php
						if( !empty( $trizen_room_rules_data ) ) {
							foreach( $trizen_room_rules_data as $index => $field ) { ?>
                                <div class="field-group">
                                    <label for="trizen_room_rules_title-<?php echo esc_attr($index); ?>">
                                        <span>
                                            <?php esc_html_e('Title', 'trizen-helper'); ?>
                                        </span>
                                        <input id="trizen_room_rules_title-<?php echo esc_attr($index); ?>" type="text" name="trizen_room_rules_title[]" value="<?php if($field['trizen_room_rules_title'] != '') echo esc_attr( $field['trizen_room_rules_title'] ); ?>" />
                                    </label>

                                    <button type="button" class="button button-secondary trizen_room_rules_remove dashicons dashicons-trash">
                                    </button>
                                </div>
								<?php
							}
						} ?>
                    </div>
                    <button type="button" id="trizen_room_rules_add" class="button trizen-btn">
						<?php esc_html_e('Add', 'trizen-helper'); ?>
                    </button>
                    <!-- End -->
                </div>
            </div>
		</div>
	</div>
</div>



