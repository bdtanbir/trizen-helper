
<?php

class trizen_helper_social_profile_icons_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'trizen_helper_social_profile_icon', // Base ID
			__( 'Trizen: Social Profile', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Social Profile', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$social_icons = trizen_helper_get_social_icons();
		$title        = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		?>
		<div class="trizen-social-profiles">
			<?php
			if ( $title ) {
				echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
			}
			?>
			<ul class="social-profile <?php echo esc_attr($instance['classname']); ?>">
				<?php
				foreach ( $social_icons as $sci ) {

					$url = !empty($instance[ $sci ]) ? trim( $instance[ $sci ] ) : '';

					if ( empty( $url ) ) continue;
					if ( $sci == "vimeo" ) { $sci = "vimeo-square"; }
					printf("<li class='mr-1'><a target='_blank' href='%s'><i class='lab la-%s'></i></a></li>", esc_url( $url ), esc_attr( $sci ));
				}
				?>
			</ul>
		</div>
		<?php
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
		$instance                = array();
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['classname']   = strip_tags( $new_instance['classname'] );
		$instance['facebook-f']  = strip_tags( $new_instance['facebook-f'] );
		$instance['twitter']     = strip_tags( $new_instance['twitter'] );
		$instance['github']      = strip_tags( $new_instance['github'] );
		$instance['pinterest']   = strip_tags( $new_instance['pinterest'] );
		$instance['instagram']   = strip_tags( $new_instance['instagram'] );
		$instance['google-plus'] = strip_tags( $new_instance['google-plus'] );
		$instance['youtube']     = strip_tags( $new_instance['youtube'] );
		$instance['vimeo']       = strip_tags( $new_instance['vimeo'] );
		$instance['tumblr']      = strip_tags( $new_instance['tumblr'] );
		$instance['dribbble']    = strip_tags( $new_instance['dribbble'] );
		$instance['flickr']      = strip_tags( $new_instance['flickr'] );
		$instance['behance']     = strip_tags( $new_instance['behance'] );
		$instance['linkedin-in']    = strip_tags( $new_instance['linkedin-in'] );

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
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Connect & Follow', 'trizen-helper' );
		}

		$classname = '';
		if ( isset( $instance['classname'] ) ) {
			$classname = $instance['classname'];
		}

		$social_icons = trizen_helper_get_social_icons();
		foreach ( $social_icons as $sc ) {
			if ( ! isset( $instance[ $sc ] ) ) {
				$instance[ $sc ] = "";
			}
		}
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>">
				<?php esc_html_e( 'Title:', 'trizen-helper' ); ?>
			</label>
			<input class="widefat"
			       id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"
			       name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>"
			       type="text"
			       value="<?php echo esc_attr($title); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id( 'classname' )); ?>">
				<?php esc_html_e( 'CSS Class name: (Optional)', 'trizen-helper' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'classname' )); ?>"
			       name="<?php echo esc_attr($this->get_field_name( 'classname' )); ?>"
			       type="text"
			       value="<?php echo esc_attr($classname); ?>"/>
		</p>
		<p>
			<strong>
				<?php esc_html_e('Below you can add as many social links as you want and you can leave the rest empty.', 'trizen-helper'); ?>
			</strong>
		</p>
		<?php foreach ( $social_icons as $sci ) {
			?>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id( $sci )) ; ?>">
					<?php echo esc_html( ucfirst( $sci ) . " " . __( 'URL', 'trizen-helper' ) ); ?>:
				</label>
				<br/>
				<input class="widefat"
				       type="text"
				       id="<?php echo esc_attr($this->get_field_id( $sci )); ?>"
				       name="<?php echo esc_attr($this->get_field_name( $sci )); ?>"
				       value="<?php echo esc_attr($instance[ $sci ]); ?>"
				       placeholder="<?php esc_attr_e('https://', 'trizen-helper'); ?>"/>
			</p>

			<?php
		}
		?>


		<?php
	}


} // class Foo_Widget

function trizen_helper_social_icons_widget() {
	register_widget( 'trizen_helper_social_profile_icons_widget' );
}

add_action( 'widgets_init', 'trizen_helper_social_icons_widget' );

function trizen_helper_get_social_icons()
{
	return array(
		"facebook-f",
		"twitter",
		"github",
		"pinterest",
		"instagram",
		"google-plus",
		"youtube",
		"vimeo",
		"tumblr",
		"dribbble",
		"flickr",
		"behance",
		"linkedin-in"
	);
}
