<?php
global $post;

?>

<div class="calendar-wrapper" data-post-id="<?php echo esc_attr($post->ID); ?>">
    <div class="left-side">
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
                    <?php esc_html_e('Price ($)', 'trizen-helper'); ?>
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



