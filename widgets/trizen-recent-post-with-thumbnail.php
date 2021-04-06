
<?php

class trizen_rpwt_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct(
			'trizen_rpwt', // Base ID
			__( 'Trizen: Recent Post With Thumbnail', 'trizen-helper' ), // Name
			array( 'description' => __( 'Trizen: Recent Post With Thumbnail', 'trizen-helper' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		// PART 1: Extracting the arguments + getting the values
		extract($args);
		$trizen_rpwt_title         = apply_filters('widget_title', $instance['trizen_rpwt_title']);
		$trizen_rpwt_post_number   =  $instance['trizen_rpwt_post_number'];
		$trizen_rpwt_post_number2  =   $instance['trizen_rpwt_post_number2'];
		$trizen_rpwt_post_number3  =    $instance['trizen_rpwt_post_number3'];
		$trizen_rpwt_post_ids      =     $instance['trizen_rpwt_post_ids'];
		$trizen_rpwt_post2_ids     =      $instance['trizen_rpwt_post2_ids'];
		$trizen_rpwt_post3_ids     =       $instance['trizen_rpwt_post3_ids'];
		$trizen_rpwt_post_ids_exp  =        explode(',', $trizen_rpwt_post_ids);
		$trizen_rpwt_post2_ids_exp =         explode(',', $trizen_rpwt_post2_ids);
		$trizen_rpwt_post3_ids_exp =          explode(',', $trizen_rpwt_post3_ids);

		// Before widget code, if any
		echo $args['before_widget'];

		?>
		<div class="trizen-rpwt recent-widget">

			<?php if(!empty($trizen_rpwt_title)) {
				echo $args['before_title'] . esc_html( $trizen_rpwt_title ) . $args['after_title'];
			}
			if(!empty($trizen_rpwt_post_ids)) {
				$default1 = [
					'posts_per_page' => $trizen_rpwt_post_number,
					'post_type'      =>  'post',
                    'post__in'       =>   $trizen_rpwt_post_ids_exp
				];
			} else {
				$default1 = [
					'posts_per_page' => $trizen_rpwt_post_number,
					'post_type'      => 'post',
				];
			}
			$post_query1 = new WP_Query( $default1 );

			if(!empty($trizen_rpwt_post2_ids)) {
				$default2 = [
					'posts_per_page' => $trizen_rpwt_post_number2,
					'post_type'      =>  'post',
                    'post__in'       =>   $trizen_rpwt_post2_ids_exp
				];
			} else {
				$default2 = [
					'posts_per_page' => $trizen_rpwt_post_number2,
					'post_type'      =>  'post',
				];
			}
			$post_query2 = new WP_Query( $default2 );

			if(!empty($trizen_rpwt_post3_ids)) {
				$default3 = [
					'posts_per_page' => $trizen_rpwt_post_number3,
					'post_type'      =>  'post',
                    'post__in'       =>   $trizen_rpwt_post3_ids_exp
				];
			} else {
				$default3 = [
					'posts_per_page' => $trizen_rpwt_post_number3,
					'post_type'      =>  'post',
				];
			}
			$post_query3 = new WP_Query( $default3 );

			if ($post_query1->have_posts() || $post_query2->have_posts() || $post_query3->have_posts()) {
				?>
				<div class="section-tab section-tab-2 pb-3">
					<ul class="nav nav-tabs" id="myTab3" role="tablist">
                        <?php if($post_query1->have_posts()) { ?>
                            <li class="nav-item">
                                <a class="nav-link" id="recent-tab" data-toggle="tab" href="#recent" role="tab" aria-controls="recent" aria-selected="true">
                                    <?php esc_html_e('Recent', 'trizen-helper'); ?>
                                </a>
                            </li>
                        <?php } if($post_query2->have_posts()) { ?>
                            <li class="nav-item">
                                <a class="nav-link active" id="popular-tab" data-toggle="tab" href="#popular" role="tab" aria-controls="popular" aria-selected="false">
                                    <?php esc_html_e('Popular', 'trizen-helper'); ?>
                                </a>
                            </li>
                        <?php } if ($post_query3->have_posts()) { ?>
                            <li class="nav-item">
                                <a class="nav-link" id="new-tab" data-toggle="tab" href="#new" role="tab" aria-controls="new" aria-selected="false">
                                    <?php esc_html_e('New', 'trizen-helper'); ?>
                                </a>
                            </li>
                        <?php } ?>
					</ul>
				</div>
				<div class="tab-content" id="myTabContent">
                    <?php if($post_query1->have_posts()) { ?>
                        <div class="tab-pane" id="recent" role="tabpanel" aria-labelledby="recent-tab">
                            <?php
                            while ($post_query1->have_posts()) {
                                $post_query1->the_post();
                                if(get_the_post_thumbnail() || get_the_title()) {
                                    ?>
                                    <div class="card-item card-item-list recent-post-card">
                                        <?php if(!empty(get_the_post_thumbnail())) { ?>
                                            <div class="card-img">
                                                <a href="<?php the_permalink(); ?>" class="d-block">
                                                    <?php the_post_thumbnail(); ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                        <div class="card-body">
                                            <?php if(!empty(get_the_title())) { ?>
                                                <h3 class="card-title">
                                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                                </h3>
                                            <?php } ?>
                                            <p class="card-meta">
                                                <span class="post__date"> <?php the_time( get_option( 'date_format' ) ); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } ?>
                        </div><!-- end tab-pane -->
                    <?php } if ($post_query2->have_posts()) { ?>
                        <div class="tab-pane fade show active" id="popular" role="tabpanel" aria-labelledby="popular-tab">
                            <?php
                            while ($post_query2->have_posts()) {
                                $post_query2->the_post();
                                if(get_the_post_thumbnail() || get_the_title()) {
                                    ?>
                                    <div class="card-item card-item-list recent-post-card">
                                        <?php if(!empty(get_the_post_thumbnail())) { ?>
                                            <div class="card-img">
                                                <a href="<?php the_permalink(); ?>" class="d-block">
                                                    <?php the_post_thumbnail(); ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                        <div class="card-body">
                                            <?php if(!empty(get_the_title())) { ?>
                                                <h3 class="card-title">
                                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                                </h3>
                                            <?php } ?>
                                            <p class="card-meta">
                                                <span class="post__date"> <?php the_time( get_option( 'date_format' ) ); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } ?>
                        </div><!-- end tab-pane -->
                    <?php } if($post_query3->have_posts()) { ?>
                        <div class="tab-pane " id="new" role="tabpanel" aria-labelledby="new-tab">
                            <?php
                            while ($post_query3->have_posts()) {
                                $post_query3->the_post();
                                if(get_the_post_thumbnail() || get_the_title()) {
                                    ?>
                                    <div class="card-item card-item-list recent-post-card">
                                        <?php if(!empty(get_the_post_thumbnail())) { ?>
                                            <div class="card-img">
                                                <a href="<?php the_permalink(); ?>" class="d-block">
                                                    <?php the_post_thumbnail(); ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                        <div class="card-body">
                                            <?php if(!empty(get_the_title())) { ?>
                                                <h3 class="card-title">
                                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                                </h3>
                                            <?php } ?>
                                            <p class="card-meta">
                                                <span class="post__date"> <?php the_time( get_option( 'date_format' ) ); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } ?>
                        </div><!-- end tab-pane -->
                    <?php } ?>
				</div>
				<?php
			} ?>
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
		$instance                             = array();
		$instance['trizen_rpwt_title']        =  $new_instance['trizen_rpwt_title'];
		$instance['trizen_rpwt_post_number']  =   $new_instance['trizen_rpwt_post_number'];
		$instance['trizen_rpwt_post_number2'] =    $new_instance['trizen_rpwt_post_number2'];
		$instance['trizen_rpwt_post_number3'] =     $new_instance['trizen_rpwt_post_number3'];
		$instance['trizen_rpwt_post_ids']     =      $new_instance['trizen_rpwt_post_ids'];
		$instance['trizen_rpwt_post2_ids']    =       $new_instance['trizen_rpwt_post2_ids'];
		$instance['trizen_rpwt_post3_ids']    =        $new_instance['trizen_rpwt_post3_ids'];
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
		if(isset($instance['trizen_rpwt_title'])) {
			$trizen_rpwt_title = $instance['trizen_rpwt_title'];
		} else {
			$trizen_rpwt_title = __('Connect & Follow', 'trizen-helper');
		}
		if(isset($instance['trizen_rpwt_post_number'])) {
			$trizen_rpwt_post_number = $instance['trizen_rpwt_post_number'];
		} else {
			$trizen_rpwt_post_number = __('3', 'trizen-helper');
		}
		if(isset($instance['trizen_rpwt_post_number2'])) {
			$trizen_rpwt_post_number2 = $instance['trizen_rpwt_post_number2'];
		} else {
			$trizen_rpwt_post_number2 = __('3', 'trizen-helper');
		}
		if(isset($instance['trizen_rpwt_post_number3'])) {
			$trizen_rpwt_post_number3 = $instance['trizen_rpwt_post_number3'];
		} else {
			$trizen_rpwt_post_number3 = __('3', 'trizen-helper');
		}
		if(isset($instance['trizen_rpwt_post_ids'])) {
			$trizen_rpwt_post_ids = $instance['trizen_rpwt_post_ids'];
		} else {
			$trizen_rpwt_post_ids = '';
		}
		if(isset($instance['trizen_rpwt_post2_ids'])) {
			$trizen_rpwt_post2_ids = $instance['trizen_rpwt_post2_ids'];
		} else {
			$trizen_rpwt_post2_ids = '';
		}
		if(isset($instance['trizen_rpwt_post3_ids'])) {
			$trizen_rpwt_post3_ids = $instance['trizen_rpwt_post3_ids'];
		} else {
			$trizen_rpwt_post3_ids = '';
		}

		// PART 2-3: Display the fields
		?>
		<div class="trizen-rpwt-fields">
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_title')); ?>">
					<?php esc_html_e('Title:', 'trizen-helper'); ?>
				</label>
				<input class="widefat"
				       id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_title')); ?>"
				       name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_title')); ?>"
				       type="text"
				       value="<?php echo esc_attr($trizen_rpwt_title); ?>" />
			</p>
            <hr>
            <h2>
                <?php esc_html_e('Recent Posts', 'trizen-helper'); ?>
            </h2>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_ids')); ?>">
					<?php esc_html_e('Post ID(s):', 'trizen-helper'); ?>
				</label>
				<input
                    class="widefat"
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_ids')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post_ids')); ?>"
					type="text"
					value="<?php echo esc_attr($trizen_rpwt_post_ids); ?>" />
                <span class="highlight-desc">
                    <?php esc_html_e('Enter post id(s) here with comma. For example:', 'trizen-helper'); ?> <strong><?php esc_html_e('1,2,3,4,5', 'trizen-helper'); ?></strong>
                </span>
			</p>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number')); ?>">
					<?php esc_html_e('Number of Posts Show:', 'trizen-helper'); ?>
				</label>
				<input
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post_number')); ?>"
					type="number"
					value="<?php echo esc_attr($trizen_rpwt_post_number); ?>"
					placeholder="<?php echo esc_attr__('3', 'trizen-helper'); ?>" />
			</p>
            <hr>
            <h2>
                <?php esc_html_e('Popular Posts', 'trizen-helper'); ?>
            </h2>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post2_ids')); ?>">
					<?php esc_html_e('Post ID(s):', 'trizen-helper'); ?>
				</label>
				<input
                    class="widefat"
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post2_ids')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post2_ids')); ?>"
					type="text"
					value="<?php echo esc_attr($trizen_rpwt_post2_ids); ?>" />
                <span class="highlight-desc">
                    <?php esc_html_e('Enter post id(s) here with comma. For example:', 'trizen-helper'); ?> <strong><?php esc_html_e('1,2,3,4,5', 'trizen-helper'); ?></strong>
                </span>
			</p>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number2')); ?>">
					<?php esc_html_e('Number of Posts Show:', 'trizen-helper'); ?>
				</label>
				<input
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number2')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post_number2')); ?>"
					type="number"
					value="<?php echo esc_attr($trizen_rpwt_post_number2); ?>"
					placeholder="<?php esc_attr_e('3', 'trizen-helper'); ?>" />
			</p>
            <hr>
            <h2><?php esc_html_e('New Posts', 'trizen-helper'); ?></h2>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post3_ids')); ?>">
					<?php esc_html_e('Post ID(s):', 'trizen-helper'); ?>
				</label>
				<input
                    class="widefat"
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post3_ids')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post3_ids')); ?>"
					type="text"
					value="<?php echo esc_attr($trizen_rpwt_post3_ids); ?>" />
                <span class="highlight-desc">
                    <?php esc_html_e('Enter post id(s) here with comma. For example:', 'trizen-helper'); ?> <strong><?php esc_html_e('1,2,3,4,5', 'trizen-helper'); ?></strong>
                </span>
			</p>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number3')); ?>">
					<?php esc_html_e('Number of Posts Show:', 'trizen-helper'); ?>
				</label>
				<input
					id="<?php echo esc_attr($this->get_field_id('trizen_rpwt_post_number3')); ?>"
					name="<?php echo esc_attr($this->get_field_name('trizen_rpwt_post_number3')); ?>"
					type="number"
					value="<?php echo esc_attr($trizen_rpwt_post_number3); ?>"
					placeholder="<?php esc_attr_e('3', 'trizen-helper'); ?>" />
			</p>
		</div>
		<?php
	}
} // class trizen_rpwt_widget

function trizen_register_rpwt() {

	register_widget( 'trizen_rpwt_widget' );

}
add_action( 'widgets_init', 'trizen_register_rpwt' );