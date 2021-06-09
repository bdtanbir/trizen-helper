<?php
global $post;

if( is_admin() ){
    $post_id = get_the_ID();
}else{
    $post_id = STInput::get('id','');
}
?>

<div class="calendar-wrapper" data-post-id="<?php echo esc_attr($post->ID); ?>">
    <div class="left-side">
        <div class="form-settings default-calendar-state">
            <h1 class="title">
                <?php esc_html_e('Default calendar state', 'trizen-helper'); ?>
            </h1>
            <?php
            $default_state = get_post_meta( get_the_ID(), 'default_state', true );
            $avl = 'available';
            $not_avl = 'not_available';
            ?>
            <select name="default_state" id="default_state">
                <option value="<?php echo esc_attr($avl); ?>" <?php echo selected( $avl, $default_state, false ); ?>>
                    <?php esc_html_e('Available', 'trizen-helper'); ?>
                </option>
                <option value="<?php echo esc_attr($not_avl); ?>" <?php echo selected( $not_avl, $default_state, false ); ?>>
                    <?php esc_html_e('Not Available', 'trizen-helper'); ?>
                </option>
            </select>
        </div>
        <div class="calendar-form form-settings">
            <h1 class="title">
                <?php esc_html_e('Calendar', 'trizen-helper'); ?>
            </h1>
            <div class="field-group">
                <label for="calendar_check_in">
                    <?php esc_html_e('Check In', 'trizen-helper'); ?>
                </label>
                <input type="text" class="widefat date-range option-tree-ui-input" name="calendar_check_in" id="calendar_check_in" placeholder="<?php esc_attr_e('Check In', 'trizen-helper'); ?>" readonly>
            </div>

            <div class="field-group">
                <label for="calendar_check_out">
                    <?php esc_html_e('Check Out', 'trizen-helper'); ?>
                </label>
                <input type="text" class="widefat date-range option-tree-ui-input" name="calendar_check_out" id="calendar_check_out" placeholder="<?php esc_attr_e('Check Out', 'trizen-helper'); ?>" readonly>
            </div>

            <div class="field-group">
                <label for="calendar_price">
                    <?php esc_html_e('Price', 'trizen-helper'); echo __(' (', 'trizen-helper').get_woocommerce_currency_symbol().__(')', 'trizen-helper'); ?>
                </label>
                <input type="text" class="widefat" name="calendar_price" id="calendar_price" placeholder="<?php esc_attr_e('Price', 'trizen-helper'); ?>">
            </div>

            <div class="field-group">
                <label for="calendar_status">
                    <?php esc_html_e('Status', 'trizen-helper'); ?>
                </label>
                <select name="calendar_status" id="calendar_status">
                    <option value="availability">
                        <?php esc_html_e('Available', 'trizen-helper'); ?>
                    </option>
                    <option value="unavailable">
                        <?php esc_html_e('Unavailable', 'trizen-helper'); ?>
                    </option>
                </select>
            </div>

            <div class="field-group">
                <div class="form-message">
                    <p></p>
                </div>
            </div>

            <div class="field-group">
                <input type="hidden" name="calendar_post_id" value="<?php echo esc_attr($post->ID); ?>">
                <input type="submit" id="calendar_submit" class="option-tree-ui-button button trizen-btn" name="calendar_submit" value="<?php esc_attr_e('Update', 'trizen-helper'); ?>">
                <button type="button" id="calendar-bulk-edit" class="option-tree-ui-button trizen-btn" style="float: right;">
                    <?php esc_html_e('Bulk Edit', 'trizen-helper'); ?>
                </button>
            </div>


            <!-- Form Bulk Edit -->
            <div id="form-bulk-edit" class="form-bulk-edit-activity-hotel-room">
                <div class="form-container">
                    <div class="form-title form-bulk-header">
                        <h3 class="clearfix" style="font-size: 18px;">
                            <?php esc_html_e('Bulk Price Edit', 'trizen-helper'); ?>
                            <button class="trizen-btn" id="calendar-bulk-close" type="button">
                                <?php esc_html_e('Close', 'trizen-helper'); ?>
                            </button>
                        </h3>
                    </div>
                    <h4 style="margin-top: 10px; font-size: 16px;">
                        <?php esc_html_e('Choose Date: ', 'trizen-helper'); ?>
                    </h4>

                    <div class="form-content clearfix d-flex">
                        <div class="form-group">
                            <div class="form-title">
                                <h4>
                                    <input type="checkbox" class="check-all" data-name="day-of-week"> <?php esc_html_e('Days Of Week', 'trizen-helper'); ?>
                                </h4>
                            </div>
                            <div class="form-content">
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Sunday', 'trizen-helper') ;?>"><?php esc_html_e('Sunday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Monday', 'trizen-helper') ;?>"><?php esc_html_e('Monday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Tuesday', 'trizen-helper') ;?>"><?php esc_html_e('Tuesday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Wednesday', 'trizen-helper') ;?>"><?php esc_html_e('Wednesday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Thursday', 'trizen-helper') ;?>"><?php esc_html_e('Thursday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Friday', 'trizen-helper') ;?>"><?php esc_html_e('Friday', 'trizen-helper'); ?></label>
                                <label class="block"><input type="checkbox" name="day-of-week[]" value="<?php esc_attr_e('Saturday', 'trizen-helper') ;?>"><?php esc_html_e('Saturday', 'trizen-helper'); ?></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-title">
                                <h4>
                                    <input type="checkbox" class="check-all" data-name="day-of-month"> <?php esc_html_e('Day Of Month', 'trizen-helper'); ?>
                                </h4>
                            </div>
                            <div class="form-content">
                                <?php
                                    for($i = 1; $i <= 31; $i ++) {
                                        if($i == 1) {
                                            echo '<div>';
                                        }
                                        ?>
                                            <label for="">
                                                <input type="checkbox" name="day-of-month[]" value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?>
                                            </label>
                                        <?php
                                        if($i != 1 && $i % 5 == 0) echo '</div><div>';
                                        if($i == 31) echo '</div>';
                                    }
                                ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-title">
                                <h4>
                                    <input type="checkbox" class="check-all" data-name="months"> <?php esc_html_e('Months', 'trizen-helper'); ?>
                                </h4>
                            </div>
                            <div class="form-content">
                                <?php
                                $months = array(
                                    'January'   => esc_html__('January', 'trizen-helper'),
                                    'February'  => esc_html__('February', 'trizen-helper'),
                                    'March'     => esc_html__('March', 'trizen-helper'),
                                    'April'     => esc_html__('April', 'trizen-helper'),
                                    'May'       => esc_html__('May', 'trizen-helper'),
                                    'June'      => esc_html__('June', 'trizen-helper'),
                                    'July'      => esc_html__('July', 'trizen-helper'),
                                    'August'    => esc_html__('August', 'trizen-helper'),
                                    'September' => esc_html__('September', 'trizen-helper'),
                                    'October'   => esc_html__('October', 'trizen-helper'),
                                    'November'  => esc_html__('November', 'trizen-helper'),
                                    'December'  => esc_html__('December', 'trizen-helper'),
                                );
                                $i = 0;
                                foreach ($months as $key => $month) {
                                    if( $i == 0 ) {
                                        echo '<div>';
                                    } ?>
                                    <label for="">
                                        <input type="checkbox" name="months[]" value="<?php echo esc_attr($key); ?>"><?php echo esc_html($month); ?>
                                    </label>
                                <?php
                                    if($i != 0 && ($i +1) % 2 == 0) echo '</div><div>';
                                    if($i + 1 == count($months)) echo '</div>';
                                    $i++;
                                }
                                ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-title">
                                <h4>
                                    <input type="checkbox" class="check-all" data-name="years"> <?php esc_html_e('Years', 'trizen-helper'); ?>
                                </h4>
                            </div>
                            <div class="form-content">
                                <?php
                                    $year = date('Y');
                                    $j = $year -1;
                                    for ($i = $year; $i <= $year + 2; $i ++) {
                                        if($i == $year) {
                                            echo '<div>';
                                        }
                                        ?>
                                        <label for="">
                                            <input type="checkbox" name="years[]" value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?>
                                        </label>
                                        <?php
                                        if( $i != $year && ($i == $j + 2 ) ) { echo '</div><div>'; $j = $i; }
                                        if( $i == $year + 2 ) echo '</div>';

                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-content form-bulk-price-op clearfix">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <label for="base-price-bulk" class="block">
                                    <?php esc_html_e('Price', 'trizen-helper'); ?>
                                    <input type="text" class="form-control" value="0" name="price-bulk" id="base-price-bulk" placeholder="<?php esc_attr_e('Price', 'trizen-helper'); ?>">
                                </label>

                                <label class="block">
                                    <?php esc_html_e('Status' ,'trizen-helper'); ?>
                                    <select name="status" id="" class="form-control">
                                        <option value="available">
                                            <?php esc_html_e('Available', 'trizen-helper'); ?>
                                        </option>
                                        <option value="unavailable">
                                            <?php esc_html_e('Unavailable', 'trizen-helper'); ?>
                                        </option>
                                    </select>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="post-id" value="<?php echo esc_attr($post_id); ?>">
                        <div class="form-message"></div>
                    </div>
                    <div class="form-footer">
                        <button id="calendar-bulk-save" class="button trizen-btn" type="button">
                            <?php esc_html_e('Save', 'trizen-helper'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="right-side">
        <div class="form-settings">
            <div class="calendar-content"></div>

            <div class="overlay">
                <span class="spinner is-active"></span>
            </div>
        </div>
    </div>
</div>



