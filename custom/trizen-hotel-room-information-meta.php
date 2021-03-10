<?php

$room_price = get_post_meta(get_the_ID(), 'trizen_room_price', true);
$number_of_room = get_post_meta(get_the_ID(), 'trizen_hotel_room_number', true);

// Hotel Room Extra Services
$trizen_hotel_room_extra_services_data = get_post_meta(get_the_ID(), 'trizen_hotel_extra_services_data_group', true);
$trizen_hotel_room_extra_service_title = get_post_meta(get_the_ID(), 'trizen_hotel_room_extra_service_title', true);
$trizen_hotel_room_extra_service_price = get_post_meta(get_the_ID(), 'trizen_hotel_room_extra_service_price', true);


$default = array(
	'post_type'      => 'ts_hotel',
	'posts_per_page' => -1
);
$hotel_rooms = new WP_Query($default);
?>

<div class="trizen-hotel-information-wrap trizen-hotel-room-information-wrap">
	<div class="nav-pill-main-div">
		<ul class="tabs nav-justified">
			<li class="tab-link current nav-pill" href="tab-room-general">
				<?php esc_html_e('Room General', 'trizen-helper'); ?>
			</li>
			<li class="tab-link nav-pill" href="tab-room-price">
				<?php esc_html_e('Room Price', 'trizen-helper'); ?>
			</li>
		</ul>
		<div class="trizen-hotel-infos-content">
			<div class="tab-content current" id="tab-room-general">
                <div class="form-settings" id="room_gallery_img">
                    <label for="trizen_hotel_room_gallery_image" class="title">
						<?php esc_html_e('Gallery', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Upload Images to make a gallery image for room.', 'trizen-helper'); ?>
                    </span>

					<?php
					$html = '<div><ul class="trizen_hotel_room_img_gallery_mtb">';
					$hidden = array();
					if( $images = get_posts( array(
						'post_type'      => 'attachment',
						'orderby'        => 'post__in',
						'order'          => 'ASC',
						'post__in'       => explode(',',get_post_meta(get_the_ID(), 'trizen_hotel_room_image_gallery', true)),
						'numberposts'    => -1,
						'post_mime_type' => 'image'
					) ) ) {
						foreach( $images as $image ) {
							$hidden[] = $image->ID;
							$image_src = wp_get_attachment_image_src( $image->ID, array( 80, 80 ) );
							$image_src = str_replace('-150x150', '', $image_src);
							$html .= '<li data-id="' . $image->ID .  '">
                                <img src="'.$image_src[0].'" alt="'.__("Image", "trizen-helper").'"><a href="#" class="trizen_hotel_room_img_gallery_remove">'.__("+", "trizen-helper").'</a></li>';
						}
					}
					$html .= '</ul><div style="clear:both"></div></div>';
					$html .= '<input type="hidden" name="trizen_hotel_room_image_gallery" value="' . join(',',$hidden) . '" /><a href="#" class="button trizen-btn trizen_upload_hotel_room_gallery_button">'.__("Add Images", "trizen-helper").'</a>';

					echo $html;
					?>

                </div>

				<div class="form-settings" id="hotel_room_select">
					<label for="trizen_hotel_room_select" class="title">
						<?php esc_html_e('Hotel Room', 'trizen-helper'); ?>
					</label>
					<span class="description">
                        <?php esc_html_e('Select a hotel for this type of room', 'trizen-helper'); ?>
                    </span>
                    <?php
						$hotel_rooms_select = get_post_meta( get_the_ID(), 'trizen_hotel_room_select', true );
					    ?>
                    <select id="trizen_hotel_room_select" class="select-to-select2" name="trizen_hotel_room_select">
                        <?php while ($hotel_rooms->have_posts()) { $hotel_rooms->the_post();
	                        $title_one = get_the_title();
	                        $postid_one = get_the_ID();

	                        echo '
	                        <option value="'.$postid_one.'" '.selected( $postid_one, $hotel_rooms_select, false ).'>
	                            '.$title_one.'
                            </option>
	                        ';
                        } ?>
                    </select>
				</div>

                <div class="form-settings" id="hotel_room_number">
                    <label for="trizen_hotel_room_number" class="title">
						<?php esc_html_e('Number of Rooms', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Number of available rooms for booking', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_room_number"
                            name="trizen_hotel_room_number"
                            type="text"
                            value="<?php echo esc_attr($number_of_room); ?>"
                            placeholder="<?php esc_attr_e('Number of rooms', 'trizen-helper'); ?>" />
                    </div>
                </div>
			</div>
			<div class="tab-content" id="tab-room-price">
				<div class="form-settings" id="room_price_setting">
					<label for="trizen_room_price" class="title">
						<?php esc_html_e('Room Price', 'trizen-helper'); ?>
					</label>
					<span class="description">
                        <?php esc_html_e('Enter your room price here', 'trizen-helper'); ?>
                    </span>
					<div class="form-input">
						<input
							id="trizen_room_price"
							name="trizen_room_price"
							type="text"
							value="<?php echo esc_attr($room_price); ?>"
							placeholder="<?php esc_attr_e('Price', 'trizen-helper'); ?>" />
					</div>
				</div>

				<div class="form-settings" id="room_extra_services_setting">
					<label for="trizen_room_price" class="title">
						<?php esc_html_e('Extra Services', 'trizen-helper'); ?>
					</label>
					<span class="description">
                        <?php esc_html_e('Accompanied service price', 'trizen-helper'); ?>
                    </span>

                    <div class="room-extra-services">
                        <!-- Start -->
                        <script type="text/html" id="tmpl-repeater3">

                            <div class="field-group hotel-accordion-item">
                                <h3 class="hotel-accordion-title">
                                    <a href="#room-extra-services-<?php echo get_the_ID(); ?>" class="accordion-toggle"></a>
                                    <button type="button" class="button button-secondary trizen_hotel_room_extra_service_remove dashicons dashicons-trash">
                                    </button>
                                </h3>
                                <div class="accordion-content" id="room-extra-services-<?php echo get_the_ID(); ?>">
                                    <div class="form-group">
                                        <label for="trizen_hotel_room_extra_service_title" class="title">
                                            <?php esc_html_e('Title', 'trizen-helper'); ?>
                                        </label>
                                        <input id="trizen_hotel_room_extra_service_title" type="text" name="trizen_hotel_room_extra_service_title[]" />
                                    </div>
                                    <div class="form-group">
                                        <label for="trizen_hotel_room_extra_service_price" class="title">
                                            <?php esc_html_e('Price', 'trizen-helper'); ?>
                                        </label>
                                        <input name="trizen_hotel_room_extra_service_price[]" id="trizen_hotel_room_extra_service_price" />
                                    </div>
                                    <div class="form-group">
                                        <label for="trizen_hotel_room_extra_service_price_designation" class="title">
                                            <?php esc_html_e('Price Designation', 'trizen-helper'); ?>
                                        </label>
                                        <input name="trizen_hotel_room_extra_service_price_designation[]" id="trizen_hotel_room_extra_service_price_designation" />
                                    </div>
                                </div>
                            </div>

                        </script>

                        <div id="trizen_hotel_room_extra_services_data" class="trizen-hotel-room-extra-services-repeater-metabox">
		                    <?php
		                    if( !empty( $trizen_hotel_room_extra_services_data ) ) {
			                    foreach( $trizen_hotel_room_extra_services_data as $index => $field ) { ?>

                                    <div class="field-group hotel-accordion-item">
                                        <h3 class="hotel-accordion-title">
                                            <a href="#room-extra-services-<?php echo esc_attr($index); ?>" class="accordion-toggle">
							                    <?php if($field['trizen_hotel_room_extra_service_title'] != '') echo esc_html( $field['trizen_hotel_room_extra_service_title'] ); ?>
                                            </a>
                                            <button type="button" class="button button-secondary trizen_hotel_room_extra_service_remove dashicons dashicons-trash">
                                            </button>
                                        </h3>
                                        <div class="accordion-content" id="room-extra-services-<?php echo esc_attr($index); ?>">
                                            <div class="form-group">
                                                <label for="trizen_hotel_room_extra_service_title" class="title">
                                                    <?php esc_html_e('Title', 'trizen-helper'); ?>
                                                </label>
                                                <input id="trizen_hotel_room_extra_service_title" type="text" name="trizen_hotel_room_extra_service_title[]" value="<?php if($field['trizen_hotel_room_extra_service_title'] != '') echo esc_attr( $field['trizen_hotel_room_extra_service_title'] ); ?>" />
                                            </div>
                                            <div class="form-group">
                                                <label for="trizen_hotel_room_extra_service_price" class="title" name="trizen_hotel_room_extra_service_price[]">
                                                    <?php esc_html_e('Price', 'trizen-helper'); ?>
                                                </label>
                                                <input name="trizen_hotel_room_extra_service_price[]" id="trizen_hotel_room_extra_service_price" value="<?php if($field['trizen_hotel_room_extra_service_price'] != '') echo esc_attr( $field['trizen_hotel_room_extra_service_price'] ); ?>" />
                                            </div>
                                            <div class="form-group">
                                                <label for="trizen_hotel_room_extra_service_price_designation" class="title" name="trizen_hotel_room_extra_service_price_designation[]">
                                                    <?php esc_html_e('Price Designation', 'trizen-helper'); ?>
                                                </label>
                                                <input name="trizen_hotel_room_extra_service_price_designation[]" id="trizen_hotel_room_extra_service_price_designation" value="<?php if($field['trizen_hotel_room_extra_service_price_designation'] != '') echo esc_attr( $field['trizen_hotel_room_extra_service_price_designation'] ); ?>" />
                                            </div>
                                        </div>
                                    </div>
				                    <?php
			                    }
		                    } ?>
                        </div>
                        <button type="button" id="trizen_hotel_room_extra_service_add" class="button trizen-btn">
		                    <?php esc_html_e('Add New', 'trizen-helper'); ?>
                        </button>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>



