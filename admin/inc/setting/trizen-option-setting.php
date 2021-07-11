<?php

class TS_Admin_Settings {
    public static $_inst;
    private static $_allSettings = [];

    public function __construct() {
        add_action('admin_menu', [$this, '__registerPage'], 9);

        add_action( 'admin_init',  array( $this, 'trizen_register_setting_panel') );
    }

    public function __registerPage() {
        add_menu_page(
            'Theme Settings',
            'Theme Settings ',
            'manage_options',
            'trizen_setting_panel_slug',
            [$this, '__showPage'],
            TRIZEN_HELPER_URI . '/admin/img/favicon.png',
            75 );
    }

    public function __showPage() {

        ?>
        <div id="trizen_settings_app">
            <form action="options.php" method="POST" id="trizen_admin_settings_panel_form" name="trizen_admin_settings_panel_form">
                <?php
                include_once TRIZEN_HELPER_PATH . 'admin/inc/setting/trizen-option-setting-content.php';
                ?>
            </form>
        </div>
        <?php
    }

    public function trizen_register_setting_panel(){
        register_setting(
            'trizen_settings_panel_group_hotel_option', // settings group name
            'disable_availability_check', // option name
            '' // sanitization function
        );
        register_setting(
            'trizen_settings_panel_group_hotel_option', // settings group name
            'hotel_review', // option name
            '' // sanitization function
        );
        register_setting(
            'trizen_settings_panel_group_hotel_option', // settings group name
            'hotel_review_stars', // option name
            '' // sanitization function
        );
        add_settings_section(
            'trizen_settings_panel_main_section_id', // section ID
            '', // title (if needed)
            '', // callback function (if needed)
            'trizen_setting_panel_slug' // page slug
        );

        /* Disabled Availability Check */
        add_settings_field(
            'disable_availability_check',
            'Disable Availability Check',
            [$this, 'tsp_disable_availability_check_callback'], // function which prints the field
            'trizen_setting_panel_slug', // page slug
            'trizen_settings_panel_main_section_id', // section ID
            array(
                'label_for' => 'disable_availability_check',
                'class'     => 'trizen-setting-tabs-content-control', // for <tr> element
            )
        );

        /* Enable Hotel Review Switcher */
        add_settings_field(
            'hotel_review',
            'Enable Review',
            [$this, 'tsp_hotel_review_callback'], // function which prints the field
            'trizen_setting_panel_slug', // page slug
            'trizen_settings_panel_main_section_id', // section ID
            array(
                'label_for' => 'hotel_review',
                'class'     => 'trizen-setting-tabs-content-control', // for <tr> element
            )
        );

        /* Hotel Review Stars Repeater Field */
        $is_hotel_review = get_option( 'hotel_review' );
        if($is_hotel_review == 'on') {
            add_settings_field(
                'hotel_review_stars',
                'Review Criterias',
                [$this, 'tsp_hotel_stars_callback'], // function which prints the field
                'trizen_setting_panel_slug', // page slug
                'trizen_settings_panel_main_section_id', // section ID
                array(
                    'label_for' => 'hotel_review_stars',
                    'class'     => 'trizen-setting-tabs-content-control', // for <tr> element
                )
            );
        }

    }

    public function tsp_disable_availability_check_callback(){
        $is_disable_avl_check = get_option( 'disable_availability_check' );
        if($is_disable_avl_check == 'on') {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        echo '<input type="checkbox" id="disable_availability_check" name="disable_availability_check" '.$checked.' />';
        ?>
        <span class="description">
            <?php echo __('<strong>OFF: </strong>Dont Check availability in search results.', 'trizen-helper'); ?>
        </span>
        <?php
    }
    public function tsp_hotel_review_callback(){
        $is_hotel_review = get_option( 'hotel_review' );
        if($is_hotel_review == 'on') {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        echo '<input type="checkbox" id="hotel_review" name="hotel_review" '.$checked.' />';
        ?>
        <span class="description">
            <?php echo __('<strong>ON: </strong>Users can review for hotel. <strong>OFF: </strong>Users can not review for hotel', 'trizen-helper'); ?>
        </span>
        <?php
    }
    /* Repeater Field Testing */
    public function tsp_hotel_stars_callback(){
        $text = get_option( 'hotel_review_stars' );
        ?>

        <div class="hotel-review-wrap">
            <script type="text/html" id="tmpl-repeater">
                <p>
                    <label for="hotel_review_stars" class="title">
                        <?php esc_html_e('Title', 'trizen-helper'); ?>
                        <input type="text" id="hotel_review_stars" size="20" name="hotel_review_stars[]" value="" />
                    </label>
                    <a href="#" id="remove_hotel_review_star">
                        <?php esc_html_e('Remove', 'trizen-helper'); ?>
                    </a>
                </p>
            </script>

            <div id="hotel_review_star_group">
                <?php
                if(!empty($text)) {
                    foreach ($text as $key=> $item) { ?>
                        <p>
                            <label for="hotel_review_stars<?php echo esc_attr($key); ?>" class="title">
                                <?php esc_html_e('Title', 'trizen-helper'); ?>
                                <input type="text" id="hotel_review_stars<?php echo esc_attr($key); ?>" size="20" name="hotel_review_stars[]" value="<?php echo esc_attr($item); ?>" />
                            </label>
                            <a href="#" id="remove_hotel_review_star">
                                <?php esc_html_e('Remove', 'trizen-helper'); ?>
                            </a>
                        </p>
                    <?php }
                } ?>
            </div>
            <a href="#" id="add_hotel_review_star">
                <?php esc_html_e('Add New Field', 'trizen-helper'); ?>
            </a>
            <span class="description">
                <?php esc_html_e('You can add, edit, delete and review criteria for hotel.', 'trizen-helper'); ?>
            </span>
        </div>


        <?php
    }



    public static function inst() {
        if (!self::$_inst)
            self::$_inst = new self();

        return self::$_inst;
    }

}

Ts_Admin_Settings::inst();



