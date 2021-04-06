
<?php

class trizen_hob_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct(
			'trizen_hob', // Base ID
			__( 'Trizen: Hotel Organized By', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Hotel Organized By', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		// PART 1: Extracting the arguments + getting the values
		extract($args);
		$trizen_hob_title = apply_filters('widget_title', $instance['trizen_hob_title']);



		$author_id = get_post_field( 'post_author', get_the_ID() );
		$userdata  =  get_userdata( $author_id );
		// Before widget code, if any
		echo $args['before_widget'];

		?>
            <?php
                echo $args['before_title'] . esc_html( $trizen_hob_title ) . $args['after_title'];
            ?>
            <div class="author-content">
                <div class="d-flex">
                    <div class="author-img">
                        <a href="<?php echo get_author_posts_url($author_id); ?>">
							<?php
                                echo get_avatar($author_id, 58)
							?>
                        </a>
                    </div>
                    <div class="author-bio">
                        <h4 class="author__title">
                            <a href="<?php echo get_author_posts_url($author_id); ?>" class="author-link">
								<?php echo get_username( $author_id ); ?>
                            </a>
                        </h4>
                        <span class="author__meta">
                            <?php echo sprintf( __( 'Member Since %s', 'trizen-helper' ), date( 'Y', strtotime( $userdata->user_registered ) ) ) ?>
                        </span>
                    </div>
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
		$instance['trizen_hob_title']        = $new_instance['trizen_hob_title'];
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
		if(isset($instance['trizen_hob_title'])) {
			$trizen_hob_title = $instance['trizen_hob_title'];
		} else {
			$trizen_hob_title = __('Organized By', 'trizen-helper');
		}

		// PART 1: Display the fields
		?>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_hob_title')); ?>">
					<?php esc_html_e('Title', 'trizen-helper'); ?>
				</label>
				<input class="widefat"
				       id="<?php echo esc_attr($this->get_field_id('trizen_hob_title')); ?>"
				       name="<?php echo esc_attr($this->get_field_name('trizen_hob_title')); ?>"
				       type="text"
				       value="<?php echo esc_attr($trizen_hob_title); ?>" />
			</p>
		<?php
	}
} // class trizen_hob_widget

function trizen_register_hob_widget() {

	register_widget( 'trizen_hob_widget' );

}
add_action( 'widgets_init', 'trizen_register_hob_widget' );