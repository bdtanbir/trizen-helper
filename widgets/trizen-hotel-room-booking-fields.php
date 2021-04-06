
<?php

class trizen_hrbf_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct(
			'trizen_hrbf', // Base ID
			__( 'Trizen: Hotel Room Booking Info', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Hotel Room Booking Info', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		// PART 1: Extracting the arguments + getting the values
		extract($args);
		$trizen_hrbf_title         = apply_filters('widget_title', $instance['trizen_hrbf_title']);

        $default_args = array(
            'post_type' => 'hotel_room'
        );
        $room_query = new WP_Query($default_args);

		// Before widget code, if any
		echo $args['before_widget'];

        $room_price = get_post_meta(get_the_ID(), 'price', true);
        $trizen_hotel_room_extra_service_data    = get_post_meta(get_the_ID(), 'trizen_hotel_extra_services_data_group', true);

        if(!empty($trizen_hrbf_title)) {
            echo $args['before_title'] . esc_html( $trizen_hrbf_title ) . $args['after_title'];
        }

		while ( have_posts() ): the_post();
		$room_id   = get_the_ID();
		$hotel_id  = get_post_meta( get_the_ID(), 'trizen_hotel_room_select', true );
        ?>
        <form id="form-booking-inpage single-room-form" class="form single-room-form hotel-room-booking-form" method="post">
            <input name="action" value="hotel_add_to_cart" type="hidden">
            <input name="item_id" value="<?php echo esc_attr($hotel_id); ?>" type="hidden">
            <input name="room_id" value="<?php echo esc_attr($room_id); ?>" type="hidden">
            <?php
            $current_calendar = get_current_available_calendar(get_the_ID());
            $current_calendar_reverb = date('m/d/Y', strtotime($current_calendar));
            $start          = get( 'start', date( getDateFormat(), strtotime($current_calendar)) );
            $end            = get( 'end', date( getDateFormat(), strtotime( "+ 1 day", strtotime($current_calendar)) ) );
            $date           = get( 'date', date( 'd/m/Y h:i a', strtotime($current_calendar) ) . '-' . date( 'd/m/Y h:i a', strtotime( '+1 day', strtotime($current_calendar) ) ) );
            ?>



            <div class="sidebar-widget-item">
                <div class="contact-form-action">
                    <!-- <form action="#"> -->
                        <div class="input-box">
                            <label class="label-text" for="input-check-in">
                                <?php esc_html_e('Check In', 'trizen-helper'); ?>
                            </label>
                            <div class="form-group">
                                <span class="la la-calendar form-icon"></span>
<!--                                <input id="input-check-in" value="--><?php //echo esc_attr($start); ?><!--" class="date-range form-control" type="text" name="check_in">-->
                            </div>
                        </div>
                        <div class="input-box">
                            <label class="label-text" for="input-check-out">
                                <?php esc_html_e('Check out', 'trizen-helper'); ?>
                            </label>
                            <div class="form-group">
                                <span class="la la-calendar form-icon"></span>
<!--                                <input id="input-check-out" value="--><?php //echo esc_html($end); ?><!--" class="date-range form-control" type="text" name="check_out">-->


                                <input type="hidden" class="check-in-input"
                                       value="<?php echo esc_attr( $start ) ?>" name="check_in">
                                <input type="hidden" class="check-out-input"
                                       value="<?php echo esc_attr( $end ) ?>" name="check_out">
                                <input type="text" class="check-in-out date-range form-control"
                                       data-room-id="<?php echo esc_attr($room_id) ?>"
                                       value="<?php echo esc_attr( $date ); ?>" name="date">
                            </div>
                        </div>
                        <!--<div class="input-box">
                            <label class="label-text" for="input-form-select">
                                <?php /*esc_html_e('Rooms', 'trizen-helper'); */?>
                            </label>
                            <?php /*if($room_query->have_posts()) { */?>
                                <div class="form-group">
                                    <div class="select-contain w-auto">
                                        <select id="input-form-select" class="select-contain-select" name="room-for-hotel">
                                            <option value="0">
                                                <?php /*esc_html_e('Select Room', 'trizen-helper'); */?>
                                            </option>

                                            <?php /*while($room_query->have_posts()) { $room_query->the_post();
                                                $title_one = get_the_title();
                                                $postid_one = get_the_ID();
                                                
                                                echo '<option value="'.esc_attr($postid_one).'">
                                                '.esc_html($title_one).'
                                                    </option>';
                                                } wp_reset_query(); */?>

                                        </select>
                                    </div>
                                </div>
                            <?php /*} */?>
                        </div>-->
                    <!-- </form> -->
                </div>
            </div>
            <div class="sidebar-widget-item">
                <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                    <label class="font-size-16" for="hotel-room-adult-input">
                        <?php esc_html_e('Adults', 'trizen-helper'); ?> <span><?php esc_html_e('Age 18+', 'trizen-helper'); ?></span>
                    </label>
                    <div class="qtyBtn d-flex align-items-center">
                        <div class="qtyDec">
                            <i class="la la-minus"></i>
                        </div>
                        <input id="hotel-room-adult-input" type="text" name="adult_number" value="<?php esc_attr_e('0', 'trizen'); ?>">
                        <div class="qtyInc">
                            <i class="la la-plus"></i>
                        </div>
                    </div>
                </div>
                <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                    <label class="font-size-16" for="hotel-room-children-input">
                        <?php esc_html_e('Children', 'trizen-helper'); ?> <span><?php esc_html_e('2-12 years old', 'trizen-helper'); ?></span>
                    </label>
                    <div class="qtyBtn d-flex align-items-center">
                        <div class="qtyDec">
                            <i class="la la-minus"></i>
                        </div>
                        <input id="hotel-room-children-input" type="text" name="children_number" value="<?php esc_attr_e('0', 'trizen'); ?>">
                        <div class="qtyInc">
                            <i class="la la-plus"></i>
                        </div>
                    </div>
                </div>
                <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                    <label class="font-size-16" for="hotel-room-infants-input">
                        <?php esc_html_e('Infants', 'trizen-helper'); ?> <span><?php esc_html_e('0-2 years old', 'trizen-helper'); ?></span>
                    </label>
                    <div class="qtyBtn d-flex align-items-center">
                        <div class="qtyDec">
                            <i class="la la-minus"></i>
                        </div>
                        <input id="hotel-room-infants-input" type="text" name="infants_number" value="<?php esc_attr_e('0', 'trizen'); ?>">
                        <div class="qtyInc">
                            <i class="la la-plus"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sidebar-widget-item py-4">
                <?php if($trizen_hotel_room_extra_service_data) { ?>
                    <h3 class="title stroke-shape">
                        <?php esc_html_e('Extra Services', 'trizen-helper'); ?>
                    </h3>
                <?php } ?>
                <div class="extra-service-wrap">
                    <!-- <form method="post" class="extraServiceForm" id="extraServiceForm"> -->
                        <?php if($trizen_hotel_room_extra_service_data) { ?>
                            <div id="checkboxContainPrice">

                                <?php
                                foreach ( $trizen_hotel_room_extra_service_data as $key => $item ) {
                                    $extra_price_title = strtolower(str_replace(' ', '-', $item['trizen_hotel_room_extra_service_title']));
                                    ?>
                                    <div class="custom-checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($extra_price_title); ?>" id="<?php echo esc_attr($extra_price_title); echo __('-','trizen-helper').esc_attr($key); ?>" value="<?php echo esc_attr($item['trizen_hotel_room_extra_service_price']); ?>" />
                                        <label for="<?php echo esc_attr($extra_price_title); echo __('-','trizen-helper').esc_attr($key); ?>" class="d-flex justify-content-between align-items-center">
                                            <?php echo esc_html($item['trizen_hotel_room_extra_service_title']); ?>
                                            <span class="text-black font-weight-regular">
                                                <?php esc_html_e('$', 'trizen-helper'); echo esc_html($item['trizen_hotel_room_extra_service_price']); echo esc_html($item['trizen_hotel_room_extra_service_price_designation']); ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php } ?>

                            </div>
                        <?php } ?>
                        <div class="total-price pt-3">
                            <p class="text-black">
                                <?php esc_html_e('Your Price', 'trizen-helper'); ?>
                            </p>
                            <p class="d-flex align-items-center">
                                <span class="font-size-17 text-black"><?php esc_html_e('$', 'trizen-helper'); ?></span> <input type="text" name="total" class="num" value="<?php if(!empty($room_price)) { echo esc_attr($room_price); } else { esc_attr_e('0', 'trizen-helper'); } ?>" readonly="readonly"/><span><?php esc_html_e('/ per room', 'trizen-helper'); ?></span>
                            </p>
                        </div>
                    <!-- </form> -->
                </div>
            </div>
            <div class="btn-box submit-group">
                <button class="theme-btn text-center w-100 mb-2 upper font-medium btn_hotel_booking btn-book-ajax"
                        type="submit"
                        name="submit" >
                    <?php esc_html_e( 'Book Now', 'trizen-helper' ) ?>
                    <i class="fa fa-spinner fa-spin d-none hide"></i>
                </button>
                <!-- <a href="cart.html" class="theme-btn text-center w-100 mb-2">
                    <?php // esc_html_e('Book Now', 'trizen-helper'); ?>
                </a> -->
            </div>
            <div class="mt30 message-wrapper">
                <?php // echo STTemplate::message() ?>
            </div>
        </form>

		<?php
		endwhile;


		// After widget code, if any
		echo $args['after_widget'];
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance                            = array();
		$instance['trizen_hrbf_title']        = $new_instance['trizen_hrbf_title'];
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		if(isset($instance['trizen_hrbf_title'])) {
			$trizen_hrbf_title = $instance['trizen_hrbf_title'];
		} else {
			$trizen_hrbf_title = __('Your Reservation', 'trizen-helper');
        }

		// PART 1: Display the fields
		?>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_hrbf_title')); ?>">
					<?php echo esc_html__('Title', 'trizen-helper'); ?>
				</label>
				<input class="widefat"
				       id="<?php echo esc_attr($this->get_field_id('trizen_hrbf_title')); ?>"
				       name="<?php echo esc_attr($this->get_field_name('trizen_hrbf_title')); ?>"
				       type="text"
				       value="<?php echo esc_attr($trizen_hrbf_title); ?>" />
			</p>
		<?php
	}
} // class trizen_hrbf_widget

function trizen_register_hrbf_widget() {

	register_widget( 'trizen_hrbf_widget' );

}
add_action( 'widgets_init', 'trizen_register_hrbf_widget' );

