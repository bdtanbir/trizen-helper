
<div class="form-title-wrap">
    <h3 class="title">
        <?php
        if(is_user_logged_in(  )) { 
            esc_html_e('Write a Review', 'trizen-helper'); 
        } else {
            esc_html_e( 'Login/Register to write Review ', 'trizen-helper' ); echo '<a href="#" data-toggle="modal" data-target="#loginPopupForm">Login</a> / <a href="#" data-toggle="modal" data-target="#signupPopupForm">Register</a>';
        }
        ?>
    </h3>
</div>

<?php if( is_user_logged_in(  )) { ?>
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
<?php } ?>
