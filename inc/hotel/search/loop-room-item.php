<?php

$room_id = get_the_ID();
$room_id = TravelHelper::post_translated($room_id);
$item_id = get_post_meta( $room_id, 'room_parent', true );
if ( empty( $item_id ) ) {
    $item_id = $room_id;
}

$room_facilities = get_the_terms( $room_id , 'room_facilities' );

$get_data                  = array();
$get_data['start']         = request( 'start' );
$get_data['end']           = request( 'end' );
$get_data['date']          = request( 'date' );
$get_data['room_num_search'] = request( 'room_num_search' );
// $get_data['infant_number'] = request( 'infant_number' );
$get_data['adult_number']  = request( 'adult_number' );
$get_data['child_number']  = request( 'child_number' );
$link_with_params = add_query_arg($get_data, get_the_permalink());
?>


<div class="cabin-type padding-top-30px">
    <form class="form-booking-inpage" method="get">
        <input type="hidden" name="check_in" value="<?php echo request( 'start' ); ?>"/>
        <input type="hidden" name="check_out" value="<?php echo request( 'end' ); ?>"/>
        <input type="hidden" name="room_num_search" value="<?php echo request( 'room_num_search' ); ?>" />
        <!-- <input type="hidden" name="infant_number" value="<?php echo request( 'infant_number' ); ?>"/> -->
        <input type="hidden" name="adult_number" value="<?php echo request( 'adult_number' ); ?>"/>
        <input type="hidden" name="child_number" value="<?php echo request( 'child_number' ); ?>"/>
        <input name="action" value="hotel_add_to_cart" type="hidden">
        <input name="item_id" value="<?php echo esc_attr($item_id); ?>" type="hidden">
        <input name="room_id" value="<?php echo esc_attr($room_id); ?>" type="hidden">
        <input type="hidden" name="start" value="<?php echo request( 'start' ); ?>"/>
        <input type="hidden" name="end" value="<?php echo request( 'end' ); ?>"/>
        <input type="hidden" name="is_search_room" value="<?php echo request( 'is_search_room' ); ?>">

        <div class="cabin-type-item seat-selection-item d-flex">
            <?php if(get_the_post_thumbnail()) { ?>
                <div class="cabin-type-img flex-shrink-0">
                    <?php the_post_thumbnail(); ?>
                </div>
            <?php } ?>
            <div class="cabin-type-detail">
                <?php if(get_the_title()) { ?>
                    <h3 class="title">
                        <a href="<?php echo esc_url($link_with_params); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>
                <?php } if($room_facilities) { ?>
                    <div class="row padding-top-20px">

                        <?php
                        $count = 0;
                        foreach ($room_facilities as $room_facility) {
                        $room_facility_icon = get_term_meta( $room_facility->term_id, 'trizen-room-facilities-icon', true );
                        $count++;
                        if($count <= 4) {
                        ?>
                            <div class="col-lg-6 responsive-column">
                                <div class="single-tour-feature d-flex align-items-center mb-3">
                                    <?php if(!empty($room_facility_icon)) { ?>
                                        <div class="single-feature-icon icon-element ml-0 flex-shrink-0 mr-2">
                                            <i class="<?php echo esc_attr($room_facility_icon); ?>"></i>
                                        </div>
                                    <?php } if(!empty($room_facility->name)) { ?>
                                        <div class="single-feature-titles">
                                            <h3 class="title font-size-15 font-weight-medium">
                                                <?php echo esc_html($room_facility->name); ?>
                                            </h3>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } 
                        } ?>

                    </div>
                <?php } ?>
                <div class="room-photos">
                    <a class="btn theme-btn-hover-gray" data-src="<?php the_post_thumbnail_url(); ?>"
                       data-fancybox="gallery<?php the_ID(); ?>"
                       data-caption="<?php esc_attr_e('Showing image - 01', 'trizen-helper'); ?>"
                       data-speed="700">
                        <i class="la la-photo mr-2"></i><?php esc_html_e('Room Photos', 'trizen-helper'); ?>
                    </a>
                    <?php
                    $hidden = array();
                    if( $images = get_posts( array(
                        'post_type'      => 'attachment',
                        'orderby'        => 'post__in',
                        'order'          => 'ASC',
                        'post__in'       => explode(',',get_post_meta(get_the_ID(), 'trizen_hotel_room_image_gallery', true)),
                        'numberposts'    => -1,
                        'post_mime_type' => 'image'
                    ) ) ) {
                        $count = 2;
                        foreach( $images as $image ) {
                            $hidden[]  = $image->ID;
                            $image_src = wp_get_attachment_image_src( $image->ID, array( 80, 80 ) );
                            $image_src = str_replace('-150x150', '', $image_src);
                            $image_src = str_replace( '-100x100', '', $image_src );
                            if( $count <= 9 ) {
                                $zero_nm = '0';
                            } else {
                                $zero_nm = '';
                            }
                            echo '<a class="d-none"
                                   data-fancybox="gallery'.get_the_ID().'"
                                   data-src="' . $image_src[0] . '"
                                   data-caption="' . esc_attr__('Showing image - ', 'trizen-helper') . esc_attr($zero_nm) . $count++. '"
                                   data-speed="700"></a>';
                        }

                    }
                    ?>

                </div>
            </div>
            <div class="cabin-price">
                <?php
                $start         = convertDateFormat( request( 'start' ) );
                $end           = convertDateFormat( request( 'end' ) );
                if ( $start && $end ) {
                $is_search_room  = request( 'is_search_room' );
                // $infant_number = intval( request( 'infant_number', 1 ) );
                $adult_number    = request( 'adult_number', 1 );
                $child_number    = request( 'child_number', '' );
                $room_num_search = request( 'room_num_search', 1 );

                $sale_price  = TSPrice::getRoomPrice( $room_id, strtotime( $start ), strtotime( $end ), $room_num_search, $adult_number, $child_number );
                $total_price = TSPrice::getRoomPriceOnlyCustomPrice( $room_id, strtotime( $start ), strtotime( $end ), $room_num_search, $adult_number, $child_number );
            
                ?>
                <p class="text-uppercase font-size-14">
                    <?php esc_html_e('Per/night', 'trizen-helper'); ?>
                    <strong class="mt-n1 text-black font-size-18 font-weight-black d-block">
                      <?php echo TravelHelper::format_money( $sale_price ); ?> 
                    </strong>
                </p>
                <div class="custom-checkbox mb-0">
                    <a href="<?php echo esc_url($link_with_params); ?>" class="theme-btn theme-btn-small">
                        <?php esc_html_e( 'Room Details', 'trizen-helper' ); ?>
                    </a>
                </div>
                <?php } else { ?>
                    <div class="custom-checkbox mb-0">
                        <a href="#" class="btn-show-price theme-btn theme-btn-small">
                            <?php esc_html_e( 'Show Price', 'trizen-helper' ); ?>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </form>
</div>
