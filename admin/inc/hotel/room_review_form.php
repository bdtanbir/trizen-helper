
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

        <div class="form-wrapper contact-form-action">
            <div class="row">

                <div class="col-lg-12">
                    <div class="input-box form-group room-stars">
                        <span class="label-text">
                            <?php esc_html_e( 'Your Rating', 'trizen-helper' ); ?>
                        </span>
                        <span class="ts-stars">
                            <?php
                                for ( $i = 1; $i <= 5; $i++ ) {
                                    echo '<i class="la la-star grey"></i>';
                                }
                            ?>
                        </span>
                        <input name="comment_rate" class="ts_review_stars" type="hidden" value="1">
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
<?php } ?>
