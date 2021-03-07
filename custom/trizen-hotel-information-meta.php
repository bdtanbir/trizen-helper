<?php
$hotel_address_title = get_post_meta( get_the_ID(), 'trizen_hotel_address_title', true );
$hotel_video         = get_post_meta( get_the_ID(), 'trizen_hotel_video_url', true );
$hotel_regular_price = get_post_meta( get_the_ID(), 'trizen_hotel_regular_price', true );
$hotel_sale_price    = get_post_meta( get_the_ID(), 'trizen_hotel_sale_price', true );
if(empty($hotel_video)) {
    $hotel_video_src = 'https://www.youtube.com/watch?v=5u1WISBbo5I';
} else {
	$hotel_video_src = $hotel_video;
}
?>
<div class="trizen-hotel-information-wrap">
    <div class="nav-pill-main-div">
        <ul class="tabs nav nav-justified">
            <li class="tab-link current nav-pill" href="tab-location">
                <?php esc_html_e('Location', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-hotel-details">
                <?php esc_html_e('Hotel Details', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-price">
                <?php esc_html_e('Price', 'trizen-helper'); ?>
            </li>
        </ul>
        <div class="trizen-hotel-infos-content">
            <div class="tab-content current" id="tab-location">
                <div class="form-settings" id="address_setting">
                    <label for="trizen_hotel_address_title" class="title">
                        <?php esc_html_e('Hotel address', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter your hotel address', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_address_title"
                            name="trizen_hotel_address_title"
                            type="text"
                            value="<?php echo esc_attr($hotel_address_title); ?>"
                            placeholder="<?php esc_attr_e('Address', 'trizen-helper'); ?>" />
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tab-hotel-details">
                <div class="form-settings" id="hotel_details_gallery_setting">
                    <label for="trizen_hotel_img_gallery" class="title">
			            <?php esc_html_e('Hotel Gallery', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Upload one or many images to make a hotel image gallery for customers', 'trizen-helper'); ?>
                    </span>
		            <?php

		            $html = '<div><ul class="trizen_hotel_img_gallery_mtb">';
		            $hidden = array();
		            if( $images = get_posts( array(
			            'post_type'      => 'attachment',
			            'orderby'        => 'post__in',
			            'order'          => 'ASC',
			            'post__in'       => explode(',',get_post_meta(get_the_ID(), 'trizen_hotel_image_gallery', true)),
			            'numberposts'    => -1,
			            'post_mime_type' => 'image'
		            ) ) ) {

			            foreach( $images as $image ) {
				            $hidden[] = $image->ID;
				            $image_src = wp_get_attachment_image_src( $image->ID, array( 80, 80 ) );
				            $image_src = str_replace('-150x150', '', $image_src);
				            $html .= '<li data-id="' . $image->ID .  '">
                                <img src="'.$image_src[0].'" alt="'.__("Image", "trizen-helper").'"><a href="#" class="trizen_hotel_img_gallery_remove">'.__("+", "trizen-helper").'</a></li>';
			            }

		            }
		            $html .= '</ul><div style="clear:both"></div></div>';
		            $html .= '<input type="hidden" name="trizen_hotel_image_gallery" value="' . join(',',$hidden) . '" /><a href="#" class="button trizen-btn misha_upload_gallery_button">'.__("Add Images", "trizen-helper").'</a>';

		            echo $html;
		            ?>
                </div>

                <div class="form-settings" id="hotel_details_video_setting">
                    <label for="trizen_hotel_video_url" class="title">
		                <?php esc_html_e('Hotel Video', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter YouTube/Vimeo URL here', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_video_url"
                            name="trizen_hotel_video_url"
                            type="text"
                            value="<?php echo esc_attr($hotel_video_src); ?>"
                            placeholder="<?php esc_attr_e('Video URL', 'trizen-helper'); ?>" />
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tab-price">
                <div class="form-settings" id="hotel_price_setting">
                    <label for="trizen_hotel_regular_price" class="title">
			            <?php esc_html_e('Regular price', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter regular price here', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_regular_price"
                            name="trizen_hotel_regular_price"
                            type="text"
                            value="<?php echo esc_attr($hotel_regular_price); ?>"
                            placeholder="<?php esc_attr_e('$', 'trizen-helper'); ?>" />
                    </div>
                </div>
                <div class="form-settings" id="hotel_price_setting">
                    <label for="trizen_hotel_sale_price" class="title">
			            <?php esc_html_e('Sale price', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter sale price here', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="trizen_hotel_sale_price"
                            name="trizen_hotel_sale_price"
                            type="text"
                            value="<?php echo esc_attr($hotel_sale_price); ?>"
                            placeholder="<?php esc_attr_e('$', 'trizen-helper'); ?>" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php