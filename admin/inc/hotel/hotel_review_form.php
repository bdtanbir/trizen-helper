
<div class="form-wrapper">
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <input type="text" class="form-control"
                       name="author"
                       placeholder="<?php esc_attr_e('Name *', 'trizen-helper') ?>">
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <input type="email" class="form-control"
                       name="email"
                       placeholder="<?php esc_attr_e('Email *', 'trizen-helper') ?>">
            </div>
        </div>
        <div class="col-xs-12">
            <div class="form-group">
                <input type="text" class="form-control"
                       name="comment_title"
                       placeholder="<?php esc_attr_e('Title', 'trizen-helper') ?>">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-md-4 col-md-push-8">
            <div class="form-group review-items has-matchHeight">
                <div class="item">
                    <label><?php esc_html_e('Service', 'trizen-helper'); ?></label>
                    <input class="ts_review_stats" type="hidden"
                           name="ts_review_stats[<?php echo trim( 'Service' ); ?>]">
                    <div class="rates">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<i class="la la-star grey"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="item">
                    <label><?php esc_html_e('Location', 'trizen-helper'); ?></label>
                    <input class="ts_review_stats" type="hidden"
                           name="ts_review_stats[<?php echo trim( 'Location' ); ?>]">
                    <div class="rates">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<i class="la la-star grey"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="item">
                    <label><?php esc_html_e('Value for Money', 'trizen-helper'); ?></label>
                    <input class="ts_review_stats" type="hidden"
                           name="ts_review_stats[<?php echo trim( 'Value for Money' ); ?>]">
                    <div class="rates">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<i class="la la-star grey"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="item">
                    <label><?php esc_html_e('Cleanliness', 'trizen-helper'); ?></label>
                    <input class="ts_review_stats" type="hidden"
                           name="ts_review_stats[<?php echo trim( 'Cleanliness' ); ?>]">
                    <div class="rates">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<i class="la la-star grey"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="item">
                    <label><?php esc_html_e('Facilities', 'trizen-helper'); ?></label>
                    <input class="ts_review_stats" type="hidden"
                           name="ts_review_stats[<?php echo trim( 'Facilities' ); ?>]">
                    <div class="rates">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<i class="la la-star grey"></i>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-8 col-md-pull-4">
            <div class="form-group">
                <textarea name="comment"
                          class="form-control has-matchHeight"
                          placeholder="<?php esc_html_e('Content', 'trizen-helper') ?>"></textarea>
            </div>
        </div>
    </div>
</div>
