
<?php

class trizen_hpa_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct(
			'trizen_hpa', // Base ID
			__( 'Trizen: Hotel Price And Availability', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Hotel Price And Availability', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		// PART 1: Extracting the arguments + getting the values
		extract($args);
		$trizen_hpa_title         = apply_filters('widget_title', $instance['trizen_hpa_title']);



		$author_id = get_post_field( 'post_author', get_the_ID() );
		$userdata  = get_userdata( $author_id );
		// Before widget code, if any
		echo $args['before_widget'];


		$hotel_regular_price = get_post_meta( get_the_ID(), 'trizen_hotel_regular_price', true );
		$hotel_sale_price    = get_post_meta( get_the_ID(), 'trizen_hotel_sale_price', true );
		?>

        <div class="sidebar-widget-item">
            <div class="sidebar-book-title-wrap mb-3">
                <?php if(!empty($trizen_hpa_title)) { ?>
                    <h3><?php echo esc_html($trizen_hpa_title); ?></h3>
                <?php
                }
                if(!empty($hotel_regular_price) && !empty($hotel_sale_price)) { ?>
                    <p>
                        <span class="text-form"><?php esc_html_e('From', 'trizen-helper'); ?></span>
                        <span class="text-value ml-2 mr-1">$<?php echo esc_html($hotel_sale_price); ?></span>
                        <span class="before-price">$<?php echo esc_html($hotel_regular_price); ?></span>
                    </p>
                <?php } else {
                    if(!empty($hotel_regular_price)) {
                        ?>
                        <p>
                            <span class="text-form"><?php esc_html_e('From', 'trizen-helper'); ?></span>
                            <span class="text-value ml-2">$<?php echo esc_html($hotel_regular_price); ?></span>
                        </p>
                        <?php
                    } else {
                        if(!empty($hotel_sale_price) && empty($hotel_regular_price)) { ?>
                            <p>
                                <span class="text-form">
                                    <?php esc_html_e('We are sorry! First add', 'trizen-helper'); ?><strong><?php esc_html_e(' Regular Price', 'trizen-helper'); ?></strong><?php esc_html_e(' and then sale price!', 'trizen-helper'); ?>
                                </span>
                            </p>
                            <?php
                        }
                    }
                }?>
            </div>
        </div>
        <div class="sidebar-widget-item">
            <div class="contact-form-action">
                <form action="#">
                    <div class="input-box">
                        <label class="label-text">Check in - Check out</label>
                        <div class="form-group">
                            <span class="la la-calendar form-icon"></span>
                            <input class="date-range form-control" type="text" name="daterange">
                        </div>
                    </div>
                    <div class="input-box">
                        <label class="label-text">Rooms</label>
                        <div class="form-group">
                            <div class="select-contain w-auto">
                                <select class="select-contain-select">
                                    <option value="0">Select Rooms</option>
                                    <option value="1">1 Room</option>
                                    <option value="2">2 Rooms</option>
                                    <option value="3">3 Rooms</option>
                                    <option value="4">4 Rooms</option>
                                    <option value="5">5 Rooms</option>
                                    <option value="6">6 Rooms</option>
                                    <option value="7">7 Rooms</option>
                                    <option value="8">8 Rooms</option>
                                    <option value="9">9 Rooms</option>
                                    <option value="10">10 Rooms</option>
                                    <option value="11">11 Rooms</option>
                                    <option value="12">12 Rooms</option>
                                    <option value="13">13 Rooms</option>
                                    <option value="14">14 Rooms</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="sidebar-widget-item">
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16">Adults <span>Age 18+</span></label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16">Children <span>2-12 years old</span></label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
            <div class="qty-box mb-2 d-flex align-items-center justify-content-between">
                <label class="font-size-16">Infants <span>0-2 years old</span></label>
                <div class="qtyBtn d-flex align-items-center">
                    <div class="qtyDec"><i class="la la-minus"></i></div>
                    <input type="text" name="qtyInput" value="0">
                    <div class="qtyInc"><i class="la la-plus"></i></div>
                </div>
            </div>
        </div>
        <div class="btn-box pt-2">
            <a href="tour-booking.html" class="theme-btn text-center w-100 mb-2"><i class="la la-shopping-cart mr-2 font-size-18"></i>Book Now</a>
            <a href="#" class="theme-btn text-center w-100 theme-btn-transparent"><i class="la la-heart-o mr-2"></i>Add to Wishlist</a>
            <div class="d-flex align-items-center justify-content-between pt-2">
                <a href="#" class="btn theme-btn-hover-gray font-size-15" data-toggle="modal" data-target="#sharePopupForm"><i class="la la-share mr-1"></i>Share</a>
                <p><i class="la la-eye mr-1 font-size-15 color-text-2"></i>3456</p>
            </div>
        </div>

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
		$instance                            = array();
		$instance['trizen_hpa_title']        = $new_instance['trizen_hpa_title'];
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
					<?php echo esc_html__('Title', 'trizen-helper'); ?>
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