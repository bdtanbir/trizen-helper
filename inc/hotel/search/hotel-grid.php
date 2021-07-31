<?php
$badge_title = get_post_meta(get_the_ID(), 'trizen_hotel_badge_title', true);
$address_title = get_post_meta(get_the_ID(), 'address', true);
$price = get_price();
$count_review    = get_comment_count(get_the_ID())['approved'];

if(get_the_post_thumbnail()) {
	$empty_img = 'empty-hotel-img';
} else {
	$empty_img = 'empty-hotel-img';
}
$avg = TSReview::get_avg_rate();
?>

<div class="card-item <?php echo esc_attr($empty_img); ?>">
	<?php if(get_the_post_thumbnail()) { ?>
        <div class="card-img">
            <a href="<?php the_permalink(); ?>" class="d-block">
				<?php the_post_thumbnail(); ?>
            </a>
			<?php if (!empty($badge_title)) { ?>
                <span class="badge">
                    <?php echo esc_html($badge_title); ?>
                </span>
			<?php } ?>
        </div>
	<?php } ?>
    <div class="card-body">
		<?php if (!get_the_post_thumbnail() && !empty($badge_title)) { ?>
            <span class="badge">
                <?php echo esc_html($badge_title); ?>
            </span>
		<?php } if(get_the_title()) { ?>
            <h3 class="card-title">
                <a href="<?php the_permalink(); ?>">
					<?php the_title(); ?>
                </a>
            </h3>
		<?php } if(!empty($address_title)) { ?>
            <p class="card-meta">
				<?php echo esc_html($address_title); ?>
            </p>
		<?php } ?>
        <div class="card-rating">
			<span class="badge text-white">
                <?php echo esc_html($avg); ?>
            </span>
            <span class="review__text">
                <?php echo TSReview::get_rate_review_text($avg, $count_review); ?>
            </span>
            <span class="rating__text">
                <?php comments_number(__('(0 Review)', 'trizen-helper'), __('(1 Review)', 'trizen-helper'), __('(% Reviews)', 'trizen-helper')); ?>
            </span>
        </div>
        <div class="card-price d-flex align-items-center justify-content-between">
            <p>
                <span class="price__from">
                    <?php esc_html_e('From', 'trizen-helper'); ?>
                </span>
                <span class="price__num">
                    <?php echo TravelHelper::format_money($price); ?>
                </span>
                <span class="price__text">
                    <?php esc_html_e('Per night', 'trizen-helper'); ?>
                </span>
            </p>
            <a href="<?php the_permalink(); ?>" class="btn-text">
				<?php esc_html_e('See details', 'trizen-helper'); ?><i class="la la-angle-right"></i>
            </a>
        </div>
    </div>
</div>


