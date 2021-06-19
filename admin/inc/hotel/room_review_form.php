
<div class="form-wrapper">
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <input type="text" class="form-control"
                       name="author"
                       placeholder="<?php _e('Name *', 'trizen-helper') ?>">
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <input type="email" class="form-control"
                       name="email"
                       placeholder="<?php _e('Email *', 'trizen-helper') ?>">
            </div>
        </div>
        <div class="col-xs-12">
            <div class="form-group">
                <input type="text" class="form-control"
                       name="comment_title"
                       placeholder="<?php _e('Title', 'trizen-helper') ?>">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-md-4 col-md-push-8">
            <div class="form-group review-items has-matchHeight">
                <?php
                $stats = TSReview::get_review_stars( get_the_ID() );
                if ( !empty( $stats ) ) {
                    foreach ( $stats as $stat ) {
                        ?>
                        <div class="item">
                            <label><?php echo esc_html($stat); ?></label>
                            <input class="ts_review_stars" type="hidden"
                                   name="ts_review_stars[<?php echo trim( $stat ); ?>]">
                            <div class="rates">
                                <?php
                                for ( $i = 1; $i <= 5; $i++ ) {
                                    echo '<i class="fa fa-smile-o grey"></i>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <div class="col-xs-12 col-md-8 col-md-pull-4">
            <div class="form-group">
                <textarea name="comment"
                          class="form-control has-matchHeight"
                          placeholder="<?php _e('Content', 'trizen-helper') ?>"></textarea>
            </div>
        </div>
    </div>
</div>
