<?php
    wp_enqueue_script('trizen-setting-panel-js');
?>

<div class="trizen-setting-header">
    <div class="left">
        <img src="<?php echo TRIZEN_HELPER_URI . '/admin/img/logo.png'; ?>" alt="">
        <h1><?php esc_html_e(' - VERSION 1.0.0', 'trizen-helper'); ?></h1>
    </div>
    <div class="right">
        <?php submit_button(); ?>
    </div>
</div>


<div class="trizen-setting-tabs">
    <ul class="trizen-setting-tabs-nav">
        <li>
            <a href="#" class="trizen-setting-tabs-btn active" data-id="hotel_option">
                <i class="dashicons dashicons-building"></i> <?php esc_html_e('Hotel Options', 'trizen-helper'); ?>
            </a>
        </li>
        <li>
            <a href="#" class="trizen-setting-tabs-btn" data-id="room_option">
                <i class="dashicons dashicons-building"></i> <?php esc_html_e('Room Options', 'trizen-helper'); ?>
            </a>
        </li>
    </ul>
    <div class="trizen-setting-tabs-content">
        <div id="hotel_option" class="trizen-setting-tab">
            <div class="form-settings" id="hotel_review">
                <?php
                    settings_fields( 'trizen_settings_panel_group_hotel_option' ); // settings group name
                    do_settings_sections( 'trizen_setting_panel_slug' ); // just a page slug
                ?>
            </div>
        </div>

        <div id="room_option" class="trizen-setting-tab hidden">
            <h1>I am from a spacial room</h1>
        </div>
    </div>
</div>


