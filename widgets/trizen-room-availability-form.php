
<?php

class trizen_hpa_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct(
			'trizen_hpa', // Base ID
			__( 'Trizen: Rooom Availability Form', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Rooom Availability Form', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		// PART 1: Extracting the arguments + getting the values
		extract($args);
		$trizen_hpa_title = apply_filters('widget_title', $instance['trizen_hpa_title']);



		$author_id = get_post_field( 'post_author', get_the_ID() );
		$userdata  = get_userdata( $author_id );
		// Before widget code, if any
		echo $args['before_widget'];


        $price = get_price();
		$hotel_regular_price = get_post_meta( get_the_ID(), 'price_avg', true );
		$hotel_sale_price    = get_post_meta( get_the_ID(), 'trizen_hotel_sale_price', true );
        $adult_number        = get( 'adult_number', 1 );
        $child_number        = get( 'child_number', 0 );
        $room_num_search       = get( 'room_num_search', 1 );
        ?>

            <form class="form form-check-availability-hotel">
                <input type="hidden" name="action" value="ajax_search_room">
                <input type="hidden" name="room_search" value="1">
                <input type="hidden" name="is_search_room" value="1">
                <input type="hidden" name="room_parent" value="<?php echo esc_attr(get_the_ID()); ?>">

                <div class="sidebar-widget-item">
                    <div class="sidebar-book-title-wrap mb-3">
                        <?php echo $args['before_title'] . esc_html( $trizen_hpa_title ) . $args['after_title'];
                        ?>
                        <p>
                            <span class="text-form"><?php esc_html_e('From', 'trizen-helper'); ?></span>
                            <span class="text-value ml-2"><?php echo TravelHelper::format_money($price); ?></span>
                        </p>
                    </div>
                </div>

                <div class="sidebar-widget-item">
                    <div class="contact-form-action">
                        <?php
                        $start = get('start', date(getDateFormat()));
                        $end   = get('end', date(getDateFormat(), strtotime("+ 1 day")));
                        $date  = get('date', date('d/m/Y h:i a'). '-'. date('d/m/Y h:i a', strtotime('+1 day')));
                        ?>
                        <div class="form-group form-date-field date-enquire form-date-search clearfix" data-format="<?php echo getDateFormatMoment() ?>">
                            <div class="date-wrapper clearfix">
                                <div class="check-in-wrapper">
                                    <label class="label-text">
                                        <?php esc_html_e('Check In - Check Out', 'trizen-helper'); ?>
                                    </label>
                                    <div class="check-in-check-out d-flex form-group form-control">
                                        <i class="la la-calendar form-icon"></i>
                                        <div class="render check-in-render"><?php echo esc_html($start); ?></div>&nbsp;-&nbsp;
                                        <div class="render check-out-render"><?php echo esc_html($end); ?></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" class="check-in-input" value="<?php echo esc_attr($start) ?>" name="start">
                            <input type="hidden" class="check-out-input" value="<?php echo esc_attr($end) ?>" name="end">
                            <input type="text" class="check-in-out" value="<?php echo esc_attr($date); ?>" name="date">
                        </div>
                    </div>
                </div>
                <div class="sidebar-widget-item">
                    <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                        <label class="font-size-16" for="adult_number">
                            <?php esc_html_e('Adults', 'trizen-helper'); ?> <span><?php esc_html_e('Age 18+', 'trizen-helper'); ?></span>
                        </label>
                        <div class="qtyBtn d-flex align-items-center">
                            <div class="qtyDec"><i class="la la-minus"></i></div>
                            <input type="text" id="adult_number" name="adult_number" value="<?php echo esc_attr($adult_number); ?>" autocomplete="off">
                            <div class="qtyInc"><i class="la la-plus"></i></div>
                        </div>
                    </div>
                    <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                        <label class="font-size-16" for="room_num_search">
                            <?php esc_html_e('Room Number', 'trizen-helper'); ?>
                        </label>
                        <div class="qtyBtn d-flex align-items-center">
                            <div class="qtyDec"><i class="la la-minus"></i></div>
                            <input type="text" id="room_num_search" name="room_num_search" value="<?php echo esc_attr($room_num_search); ?>">
                            <div class="qtyInc"><i class="la la-plus"></i></div>
                        </div>
                    </div>
                    <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                        <label class="font-size-16" for="child_number">
                            <?php esc_html_e('Children', 'trizen-helper'); ?> <span><?php esc_html_e('2-12 years old', 'trizen-helper'); ?></span>
                        </label>
                        <div class="qtyBtn d-flex align-items-center">
                            <div class="qtyDec"><i class="la la-minus"></i></div>
                            <input type="text" id="child_number" name="child_number" value="<?php echo esc_attr($child_number); ?>" autocomplete="off">
                            <div class="qtyInc"><i class="la la-plus"></i></div>
                        </div>
                    </div>
                </div>
                <div class="btn-box pt-2 submit-group">
                    <input class="theme-btn text-center w-100 font-size-18"
                           type="submit"
                           value="<?php esc_attr_e('Check Availability', 'trizen-helper'); ?>"
                           name="submit" />
                    <div class="d-flex align-items-center justify-content-between pt-2">
                        <a href="#" class="btn theme-btn-hover-gray font-size-15" data-toggle="modal" data-target="#sharePopupForm">
                            <i class="la la-share mr-1"></i> <?php esc_html_e('Share', 'trizen-helper'); ?>
                        </a>
                        <p>
                            <i class="la la-eye mr-1 font-size-15 color-text-2"></i>
                        </p>
                    </div>
                </div>
                <div class="message-wrapper alert-warning"></div>
            </form>

        <!--<div class="sidebar-widget-item">
            <div class="sidebar-book-title-wrap mb-3">
                <?php /*if(!empty($trizen_hpa_title)) { */?>
                    <h3><?php /*echo esc_html($trizen_hpa_title); */?></h3>
                <?php
/*                }
                if(!empty($hotel_regular_price) && !empty($hotel_sale_price)) { */?>
                    <p>
                        <span class="text-form"><?php /*esc_html_e('From', 'trizen-helper'); */?></span>
                        <span class="text-value ml-2 mr-1"><?php /*esc_html_e('$', 'trizen-helper'); echo esc_html($hotel_sale_price); */?></span>
                        <span class="before-price"><?php /*esc_html_e('$', 'trizen-helper'); echo esc_html($hotel_regular_price); */?></span>
                    </p>
                <?php /*} else {
                    if(!empty($hotel_regular_price)) {
                        */?>
                        <p>
                            <span class="text-form"><?php /*esc_html_e('From', 'trizen-helper'); */?></span>
                            <span class="text-value ml-2"><?php /*esc_html_e('$', 'trizen-helper'); echo esc_html($hotel_regular_price); */?></span>
                        </p>
                        <?php
/*                    } else {
                        if(!empty($hotel_sale_price) && empty($hotel_regular_price)) { */?>
                            <p>
                                <span class="text-form">
                                    <?php /*esc_html_e('We are sorry! First add', 'trizen-helper'); */?><strong><?php /*esc_html_e(' Regular Price', 'trizen-helper'); */?></strong><?php /*esc_html_e(' and then sale price!', 'trizen-helper'); */?>
                                </span>
                            </p>
                            <?php
/*                        }
                    }
                }*/?>
            </div>
        </div>
        <div class="sidebar-widget-item">
            <div class="contact-form-action">
                <form action="#">
                    <div class="input-box">
                        <label class="label-text" for="check-in-check-out">
                            <?php /*esc_html_e('Check in - Check out', 'trizen-helper'); */?>
                        </label>
                        <div class="form-group">
                            <span class="la la-calendar form-icon"></span>
                            <input id="check-in-check-out" class="date-range form-control" type="text" name="daterange">
                        </div>
                    </div>
                    <div class="input-box">
                        <label class="label-text" for="select-room">
                            <?php /*esc_html_e('Rooms', 'trizen-helper'); */?>
                        </label>
                        <div class="form-group">
                            <div class="select-contain w-auto">
                                <select id="select-room" class="select-contain-select">
                                    <option value="0"><?php /*esc_html_e('Select Rooms','trizen-helper'); */?></option>
                                    <option value="1"><?php /*esc_html_e('1 Room', 'trizen-helper'); */?></option>
                                    <option value="2"><?php /*esc_html_e('2 Rooms', 'trizen-helper'); */?></option>
                                    <option value="3"><?php /*esc_html_e('3 Rooms', 'trizen-helper'); */?></option>
                                    <option value="4"><?php /*esc_html_e('4 Rooms', 'trizen-helper'); */?></option>
                                    <option value="5"><?php /*esc_html_e('5 Rooms', 'trizen-helper'); */?></option>
                                    <option value="6"><?php /*esc_html_e('6 Rooms', 'trizen-helper'); */?></option>
                                    <option value="7"><?php /*esc_html_e('7 Rooms', 'trizen-helper'); */?></option>
                                    <option value="8"><?php /*esc_html_e('8 Rooms', 'trizen-helper'); */?></option>
                                    <option value="9"><?php /*esc_html_e('9 Rooms', 'trizen-helper'); */?></option>
                                    <option value="10"><?php /*esc_html_e('10 Rooms', 'trizen-helper'); */?></option>
                                    <option value="11"><?php /*esc_html_e('11 Rooms', 'trizen-helper'); */?></option>
                                    <option value="12"><?php /*esc_html_e('12 Rooms', 'trizen-helper'); */?></option>
                                    <option value="13"><?php /*esc_html_e('13 Rooms', 'trizen-helper'); */?></option>
                                    <option value="14"><?php /*esc_html_e('14 Rooms', 'trizen-helper'); */?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="sidebar-widget-item">
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16" for="adults-num">
                    <?php /*esc_html_e('Adults', 'trizen-helper'); */?> <span><?php /*esc_html_e('Age 18+', 'trizen-helper'); */?></span>
                </label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input id="adults-num" type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16" for="children-num">
                    <?php /*esc_html_e('Children', 'trizen-helper'); */?> <span><?php /*esc_html_e('2-12 years old', 'trizen-helper'); */?></span>
                </label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input id="children-num" type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16" for="infants_num"><?php /*esc_html_e('Infants', 'trizen-helper'); */?> <span><?php /*esc_html_e('0-2 years old', 'trizen-helper'); */?></span></label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input id="infants_num" type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
        </div>
        <div class="btn-box pt-2">
            <a href="" class="theme-btn text-center w-100 mb-2">
                <i class="la la-shopping-cart mr-2 font-size-18"></i><?php /*esc_html_e('Book Now', 'trizen-helper'); */?>
            </a>
            <a href="#" class="theme-btn text-center w-100 theme-btn-transparent">
                <i class="la la-heart-o mr-2"></i><?php /*esc_html_e('Add to Wishlist', 'trizen-helper');  */?>
            </a>
            <div class="d-flex align-items-center justify-content-between pt-2">
                <a href="#" class="btn theme-btn-hover-gray font-size-15" data-toggle="modal" data-target="#sharePopupForm">
                    <i class="la la-share mr-1"></i><?php /*esc_html_e('Share', 'trizen-helper'); */?>
                </a>
                <p>
                    <i class="la la-eye mr-1 font-size-15 color-text-2"></i><?php /*esc_html_e('3456', 'trizen-helper'); */?>
                </p>
            </div>
        </div>-->

		<?php

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
		$instance                     = array();
		$instance['trizen_hpa_title'] = $new_instance['trizen_hpa_title'];
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
		if(isset($instance['trizen_hpa_title'])) {
			$trizen_hpa_title = $instance['trizen_hpa_title'];
		} else {
			$trizen_hpa_title = __('POPULAR', 'trizen-helper');
		}

		// PART 1: Display the fields
		?>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_hpa_title')); ?>">
					<?php esc_html_e('Title', 'trizen-helper'); ?>
				</label>
				<input class="widefat"
				       id="<?php echo esc_attr($this->get_field_id('trizen_hpa_title')); ?>"
				       name="<?php echo esc_attr($this->get_field_name('trizen_hpa_title')); ?>"
				       type="text"
				       value="<?php echo esc_attr($trizen_hpa_title); ?>" />
			</p>
		<?php
	}
} // class trizen_hpa_widget

function trizen_register_hpa_widget() {

	register_widget( 'trizen_hpa_widget' );

}
add_action( 'widgets_init', 'trizen_register_hpa_widget' );