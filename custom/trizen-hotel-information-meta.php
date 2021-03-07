<?php
$hotel_address_title = get_post_meta( get_the_ID(), 'trizen_hotel_address_title', true );
$hotel_video         = get_post_meta( get_the_ID(), 'trizen_hotel_video_url', true );
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
            <li class="tab-link nav-pill" href="tab-3">
                Efficiency
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
            <div class="tab-content" id="tab-3">
                <div class="float-left">
                    <img alt="efficiency" class="finbyz-icon" src="https://finbyz.tech/svg/efficiency.svg"
                         title="efficiency">
                </div>
                <p class="pb-20">
                    The first step to increase the efficiency of your team is to track and limit how much time
                    they send on each task. ERP software will not only give you tools to monitor &amp; measure
                    financial performance, but can also help you in monitoring the tasks performed by each team
                    member. Sometimes the most important part of solving the problem is knowing where the
                    problem lies. An efficient organization needs you to be a leader, and ERP software
                    development provides you all the tools required to mentor your team to success! The
                    efficiency also increases when you have all information on the figure tips. Don’t you think
                    the sales call will be more efficient if caller has all information about last conversation
                    with the client, even the details of a call done few years ago!
                </p>
            </div>
        </div>
    </div>
</div>
<?php