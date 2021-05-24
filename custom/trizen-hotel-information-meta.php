<?php
$hotel_address_title = get_post_meta( get_the_ID(), 'address', true );
$hotel_video         = get_post_meta( get_the_ID(), 'trizen_hotel_video_url', true );
$hotel_regular_price = get_post_meta( get_the_ID(), 'trizen_hotel_regular_price', true );
$hotel_sale_price    = get_post_meta( get_the_ID(), 'trizen_hotel_sale_price', true );
if(empty($hotel_video)) {
    $hotel_video_src = 'https://www.youtube.com/watch?v=5u1WISBbo5I';
} else {
	$hotel_video_src = $hotel_video;
}


// Hotel Features
$trizen_hotel_features_data   = get_post_meta(get_the_ID(), 'trizen_hotel_features_data_group', true);
$trizen_hotel_features_title  =  get_post_meta(get_the_ID(), 'trizen_hotel_features_title', true);
$trizen_hotel_features_stitle =   get_post_meta(get_the_ID(), 'trizen_hotel_features_stitle', true);
$trizen_hotel_features_icon   =    get_post_meta(get_the_ID(), 'trizen_hotel_features_icon', true);

// Hotel Faqs
$trizen_hotel_faqs_data    = get_post_meta(get_the_ID(), 'trizen_hotel_faqs_data_group', true);
$trizen_hotel_faqs_title   =  get_post_meta(get_the_ID(), 'trizen_hotel_faqs_title', true);
$trizen_hotel_faqs_content =   get_post_meta(get_the_ID(), 'trizen_hotel_faqs_content', true);
$google_api_key            =   get_post_meta(get_the_ID(), 'gmap_apikey', true);

$args = [
    'post_type' => 'post'
];
$query = new WP_Query($args)
?>
<div class="trizen-hotel-information-wrap">
    <div class="nav-pill-main-div">
        <ul class="tabs nav-justified">
            <li class="tab-link current nav-pill" href="tab-location">
                <?php esc_html_e('Location', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-hotel-details">
                <?php esc_html_e('Hotel Details', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-price">
                <?php esc_html_e('Price', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-hotel-features">
                <?php esc_html_e('Hotel Features', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-hotel-faqs">
                <?php esc_html_e('Hotel Faqs', 'trizen-helper'); ?>
            </li>
            <li class="tab-link nav-pill" href="tab-hotel-inventory">
                <?php esc_html_e('Inventory', 'trizen-helper'); ?>
            </li>
        </ul>
        <div class="trizen-hotel-infos-content">
            <div class="tab-content current" id="tab-location">
                <div class="form-settings" id="hotel_location_setting">
                    <label for="price" class="title">
                        <?php esc_html_e('Hotel Location', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Select one or more locations for your hotel (Enter the name you need to search in search box to filter address faster)', 'trizen-helper'); ?>
                    </span>

                    <div class="ts-select-location">
                        <input placeholder="<?php esc_html_e('Type to search', 'trizen-helper'); ?>" type="text"
                               class="widefat form-control" name="search" value="">
                        <div class="location-list-wrapper">
                            <?php
                            $html_location = TravelHelper::treeLocationHtml();
                            $multi_location = maybe_unserialize(get_post_meta(get_the_ID(), 'multi_location', true));
                            if (!empty($multi_location) && !is_array($multi_location)) {
                                $multi_location = explode(',', $multi_location);
                            }
                            if (empty($multi_location)) {
                                $multi_location = array('');
                            }

                            if (is_array($html_location) && count($html_location)):
                                foreach ($html_location as $key => $location):
                            ?>
                                <div class="location-list" data-name="<?php echo esc_attr($location['parent_name']); ?>" style="margin-left: <?php echo esc_attr( $location['level']) . 'px;'; ?>">
                                    <label for="<?php echo 'location-' . esc_attr($location['ID']); ?>">
                                        <input <?php if (in_array('_' . $location['ID'] . '_', $multi_location)) echo 'checked'; ?>
                                                type="checkbox"
                                                id="<?php echo 'location-' . esc_attr($location['ID']); ?>"
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

                <div class="form-settings" id="address_setting">
                    <label for="address" class="title">
                        <?php esc_html_e('Hotel address', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter your hotel address', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            id="address"
                            name="address"
                            type="text"
                            value="<?php echo esc_attr($hotel_address_title); ?>"
                            placeholder="<?php esc_attr_e('Address', 'trizen-helper'); ?>" />
                    </div>
                </div>

                <div class="form-settings" id="gmap_apikey_setting">
                    <label for="gmap_apikey" class="title">
                        <?php esc_html_e('Google Api key', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Enter your google map api key', 'trizen-helper'); ?>
                    </span>
                    <div class="form-input">
                        <input
                            type="text"
                            id="gmap_apikey"
                            name="gmap_apikey"
                            value="<?php echo esc_attr($google_api_key); ?>"
                            placeholder="<?php esc_attr_e('Google Map Api Key', 'trizen-helper'); ?>">
                    </div>
                </div>

                <div class="form-settings" id="location_map_setting">
                    <label for="address" class="title">
                        <?php esc_html_e('Location on map', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Select one location on map to see latiture and longiture', 'trizen-helper'); ?>
                    </span>
                    <div class="location_map_and_latlng">
                        <div class="ts_gmap_box">
                            <input
                                id="pac-input"
                                class="controls"
                                type="text"
                                placeholder="Search Box" />
                            <div id="ts_gmap"></div>
                        </div>
                        <div class="right">

                            <?php
                                $lat = get_post_meta(get_the_ID(), 'lat', true);
                                $lng = get_post_meta(get_the_ID(), 'lng', true);
                                $zoom = get_post_meta(get_the_ID(), 'zoom', true);
                            ?>
                            <label for="ts_gmap_lat" class="title">
                                <?php esc_html_e('Latitude:', 'trizen-helper'); ?>
                            </label>
                            <div class="form-input">
                                <input
                                    id="latitude"
                                    name="lat"
                                    type="text"
                                    value="<?php echo esc_html($lat) ?>"
                                    placeholder="<?php esc_attr_e('Latitude', 'trizen-helper'); ?>" />
                            </div>

                            <label for="ts_gmap_lat" class="title">
                                <?php esc_html_e('Longitude:', 'trizen-helper'); ?>
                            </label>
                            <div class="form-input">
                                <input
                                    id="longitude"
                                    name="lng"
                                    type="text"
                                    value="<?php echo esc_html($lng) ?>"
                                    placeholder="<?php esc_attr_e('Longitude', 'trizen-helper'); ?>" />
                            </div>

                            <label for="ts_gmap_lat" class="title">
                                <?php esc_html_e('Zoom Level:', 'trizen-helper'); ?>
                            </label>
                            <div class="form-input">
                                <input
                                    id="zoom_level"
                                    name="zoom"
                                    type="text"
                                    value="<?php echo esc_html($zoom) ?>"
                                    placeholder="<?php esc_attr_e('Zoom Level', 'trizen-helper'); ?>" />
                            </div>

                        </div>
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

                    <div>
                        <ul class="trizen_hotel_img_gallery_mtb">
                            <?php
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
                                    $hidden[]  = $image->ID;
                                    $image_src = wp_get_attachment_image_src( $image->ID, array( 80, 80 ) );
                                    $image_src = str_replace('-150x150', '', $image_src);
                                    echo '<li data-id="' . $image->ID .  '">
                                        <img src="'.$image_src[0].'" alt="'.esc_attr__("Image", "trizen-helper").'"><a href="#" class="trizen_hotel_img_gallery_remove">'.esc_html__("+", "trizen-helper").'</a></li>';
                                }

                            }
                            ?>
                        </ul>
                        <div style="clear:both"></div>
                    </div>
                    <input type="hidden" name="trizen_hotel_image_gallery" value="<?php echo join(',',$hidden); ?>" />
                    <a href="#" class="button trizen-btn trizen_upload_hotel_gallery_button">
                        <?php esc_html_e("Add Images", "trizen-helper"); ?>
                    </a>
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
                <div class="form-settings" id="hotel_sale_price_setting">
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
            <div class="tab-content" id="tab-hotel-features">
                <div class="form-settings" id="hotel_features_setting">
                    <label for="trizen_hotel_sale_price" class="title">
			            <?php esc_html_e('Hotel Features', 'trizen-helper'); ?>
                    </label>
                    <span class="description">
                        <?php esc_html_e('Add your hotel features', 'trizen-helper'); ?>
                    </span>

                    <!-- Start -->
                    <script type="text/html" id="tmpl-repeater">
                        <div class="field-group">
                            <label for="trizen_hotel_features_title">
                                <span>
                                    <?php esc_html_e('Title', 'trizen-helper'); ?>
                                </span>
                                <input id="trizen_hotel_features_title" type="text" name="trizen_hotel_features_title[]" value="" />
                            </label>

                            <label for="trizen_hotel_features_stitle">
                                <span>
                                    <?php esc_html_e('Sub Title', 'trizen-helper'); ?>
                                </span>
                                <input id="trizen_hotel_features_stitle" type="text" name="trizen_hotel_features_stitle[]" value="" />
                            </label>

                            <label for="trizen_hotel_features_icon">
                                <span>
                                    <?php esc_html_e('Icon Class', 'trizen-helper'); ?>
                                </span>
                                <input id="trizen_hotel_features_icon" type="text" name="trizen_hotel_features_icon[]" value="" />
                            </label>

                            <button type="button" class="button button-secondary trizen_hotel_features_remove dashicons dashicons-trash">
                            </button>
                        </div>
                    </script>

                    <div id="trizen_hotel_features_data" class="trizen-hotel-features-repeater-metabox">
		                <?php
		                if( !empty( $trizen_hotel_features_data ) ) {
			                foreach( $trizen_hotel_features_data as $index => $field ) { ?>
                                <div class="field-group">
                                    <label for="trizen_hotel_features_title-<?php echo esc_attr($index); ?>">
                                        <span>
                                            <?php esc_html_e('Title', 'trizen-helper'); ?>
                                        </span>
                                        <input id="trizen_hotel_features_title-<?php echo esc_attr($index); ?>" type="text" name="trizen_hotel_features_title[]" value="<?php if($field['trizen_hotel_features_title'] != '') echo esc_attr( $field['trizen_hotel_features_title'] ); ?>" />
                                    </label>

                                    <label for="trizen_hotel_features_stitle-<?php echo esc_attr($index); ?>">
                                        <span><?php esc_html_e('Sub Title', 'trizen-helper'); ?></span>
                                        <input id="trizen_hotel_features_stitle-<?php echo esc_attr($index); ?>" type="text" name="trizen_hotel_features_stitle[]" value="<?php if($field['trizen_hotel_features_stitle'] != '') echo esc_attr( $field['trizen_hotel_features_stitle'] ); ?>" />
                                    </label>

                                    <label for="trizen_hotel_features_icon-<?php echo esc_attr($index); ?>">
                                        <span><?php esc_html_e('Icon Class', 'trizen-helper'); ?></span>
                                        <input id="trizen_hotel_features_icon-<?php echo esc_attr($index); ?>" type="text" name="trizen_hotel_features_icon[]" value="<?php if($field['trizen_hotel_features_icon'] != '') echo esc_attr( $field['trizen_hotel_features_icon'] ); ?>" />
                                    </label>

                                    <button type="button" class="button button-secondary trizen_hotel_features_remove dashicons dashicons-trash">
                                    </button>
                                </div>
				                <?php
			                }
		                } ?>
                    </div>
                    <button type="button" id="trizen_hotel_features_add" class="button trizen-btn">
		                <?php esc_html_e('Add', 'trizen-helper'); ?>
                    </button>
                    <!-- End -->
                </div>
            </div>
            <div class="tab-content" id="tab-hotel-faqs">
                <div class="form-settings" id="hotel_faqs_setting">
                    <div class="hotel-accordion-wrap">
                        <label for="trizen_hotel_sale_price" class="title">
		                    <?php esc_html_e('Hotel Faqs', 'trizen-helper'); ?>
                        </label>
                        <span class="description">
                            <?php esc_html_e('Add your hotel faqs', 'trizen-helper'); ?>
                        </span>

                        <!-- Start -->
                        <script type="text/html" id="tmpl-repeater2">

                            <div class="field-group hotel-accordion-item">
                                <h3 class="hotel-accordion-title">
                                    <a href="#content-<?php echo get_the_ID(); ?>" class="accordion-toggle"></a>
                                    <button type="button" class="button button-secondary trizen_hotel_faq_remove dashicons dashicons-trash">
                                    </button>
                                </h3>
                                <div class="accordion-content active" id="content-<?php echo get_the_ID(); ?>">
                                    <div class="form-group">
                                        <label for="trizen_hotel_faqs_title" class="title">
                                            <?php esc_html_e('Title', 'trizen-helper'); ?>
                                        </label>
                                        <input id="trizen_hotel_faqs_title" type="text" name="trizen_hotel_faqs_title[]" />
                                    </div>
                                    <div class="form-group">
                                        <label for="trizen_hotel_faqs_content" class="title">
                                            <?php esc_html_e('Content', 'trizen-helper'); ?>
                                        </label>
                                        <textarea name="trizen_hotel_faqs_content[]" id="trizen_hotel_faqs_content"></textarea>
                                    </div>
                                </div>
                            </div>

                        </script>

                        <div id="trizen_hotel_faqs_data" class="trizen-hotel-faqs-repeater-metabox">
		                    <?php
		                    if( !empty( $trizen_hotel_faqs_data ) ) {
			                    foreach( $trizen_hotel_faqs_data as $index => $field ) { ?>

                                    <div class="field-group hotel-accordion-item">
                                        <h3 class="hotel-accordion-title">
                                            <a href="#content-<?php echo esc_attr($index); ?>" class="accordion-toggle">
	                                            <?php if($field['trizen_hotel_faqs_title'] != '') echo esc_html( $field['trizen_hotel_faqs_title'] ); ?>
                                            </a>
                                            <button type="button" class="button button-secondary trizen_hotel_faq_remove dashicons dashicons-trash">
                                            </button>
                                        </h3>
                                        <div class="accordion-content" id="content-<?php echo esc_attr($index); ?>">
                                            <div class="form-group">
                                                <label for="trizen_hotel_faqs_title" class="title">
                                                    <?php esc_html_e('Title', 'trizen-helper'); ?>
                                                </label>
                                                <input id="trizen_hotel_faqs_title" type="text" name="trizen_hotel_faqs_title[]" value="<?php if($field['trizen_hotel_faqs_title'] != '') echo esc_attr( $field['trizen_hotel_faqs_title'] ); ?>" />
                                            </div>
                                            <div class="form-group">
                                                <label for="trizen_hotel_faqs_content" class="title" name="trizen_hotel_faqs_title[]">
                                                    <?php esc_html_e('Content', 'trizen-helper'); ?>
                                                </label>
                                                <textarea name="trizen_hotel_faqs_content[]" id="trizen_hotel_faqs_content"><?php if($field['trizen_hotel_faqs_content'] != '') echo esc_attr( $field['trizen_hotel_faqs_content'] ); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
				                    <?php
			                    }
		                    } ?>
                        </div>
                        <button type="button" id="trizen_hotel_faqs_add" class="button trizen-btn">
		                    <?php esc_html_e('Add FAQ(s)', 'trizen-helper'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tab-hotel-inventory">
		        <?php include_once TRIZEN_HELPER_PATH . 'custom/trizen-hotel-inventory.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php