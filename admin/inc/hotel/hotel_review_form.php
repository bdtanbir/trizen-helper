
<div class="form-title-wrap">
    <h3 class="title">
        <?php esc_html_e('Write a Review', 'trizen-helper'); ?>
    </h3>
</div>

<div class="form-content">
    <div class="rate-option p-2">
        <div class="row review-items has-matchHeight">

            <?php
            $stars = TSReview::get_review_stars( get_the_ID() );
            if ( !empty( $stars ) ) {
                foreach ( $stars as $star ) {
                    ?>
                    <div class="col-lg-4 responsive-column item">
                        <div class="rate-option-item">
                            <label><?php echo esc_html($star); ?></label>
                            <input class="ts_review_stars" type="hidden" name="ts_review_stars[<?php echo trim( $star ); ?>]">
                            <div class="rate-stars-option rates">
                                <?php
                                for ( $i = 1; $i <= 5; $i++ ) {
                                    echo '<i class="la la-star grey"></i>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

        </div>
    </div>

    <div class="form-wrapper contact-form-action">
        <div class="row">
            <div class="col-lg-6 responsive-column">
                <div class="input-box">
                    <label class="label-text" for="author">
                        <?php esc_html_e('Name', 'trizen-helper'); ?>
                    </label>
                    <div class="form-group">
                        <span class="la la-user form-icon"></span>
                        <input id="author" type="text" class="form-control"
                               name="author"
                               placeholder="<?php esc_attr_e('Your name', 'trizen-helper') ?>">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 responsive-column">
                <div class="input-box">
                    <label class="label-text" for="email">
                        <?php esc_html_e('Email', 'trizen-helper'); ?>
                    </label>
                    <div class="form-group">
                        <span class="la la-envelope-o form-icon"></span>
                        <input id="email" type="email" class="form-control"
                               name="email"
                               placeholder="<?php esc_attr_e('Email address', 'trizen-helper') ?>">
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="input-box">
                    <label class="label-text" for="comment">
                        <?php esc_html_e('Message', 'trizen--helper'); ?>
                    </label>
                    <div class="form-group">
                        <span class="la la-pencil form-icon"></span>
                        <textarea id="comment" name="comment"
                                  class="form-control has-matchHeight"
                                  placeholder="<?php esc_attr_e('Write message', 'trizen-helper'); ?>"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



