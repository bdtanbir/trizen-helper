<?php

class TS_Admin_Settings {
    public static $_inst;
    private static $_allSettings = [];

    public function __construct() {
        add_action('admin_menu', [$this, '__registerPage'], 9);

        add_action( 'wp_ajax_save_settings_with_ajax', array( $this, 'trizen_save_settings_panel_with_ajax' ) );
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
            35 );
    }

    public function __showPage() {

//        $ajax_url = array(
//            'ajaxurl' => admin_url( 'admin-ajax.php' ),
//        );
//        wp_localize_script( 'trizen-admin-setting-panel-js', 'trizen_settings_panel_param', $ajax_url );
//
//        /**
//         * This section will handle the "trizen_save_settings_panel" array. If any new settings options is added
//         * then it will matches with the older array and then if it founds anything new then it will update the entire array.
//         */
//        $this->trizen_default_settings_panel = array_fill_keys( $this->trizen_setting_default_keys, true );
//        $this->trizen_get_settings_panel     = get_option( 'trizen_save_settings_panel', $this->trizen_default_settings_panel );
//        $trien_new_settings_panel           = array_diff_key( $this->trizen_default_settings_panel, $this->trizen_get_settings_panel );
//
//        if( ! empty( $trien_new_settings_panel ) ) {
//            $trizen_updated_settings_panel = array_merge( $this->trizen_get_settings_panel, $trien_new_settings_panel );
//            update_option( 'trizen_save_settings_panel', $trizen_updated_settings_panel );
//        }
//        $this->trizen_get_settings_panel = get_option( 'trizen_save_settings_panel', $this->trizen_default_settings_panel );
        ?>
        <div id="trizen_settings_app">
            <?php echo '<form action="options.php" method="POST" id="trizen_admin_settings_panel_form" name="trizen_admin_settings_panel_form">'; ?>
                <?php include_once TRIZEN_HELPER_PATH . 'admin/inc/setting/trizen-option-setting-content.php'; ?>
            <?php echo '</form>'; ?>
        </div>
        <?php
    }

    public function trizen_register_setting_panel(){
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

    /**
     * Saving data with ajax request
     * @param
     * @return  array
     * @since 1.0
     */
    public function trizen_save_settings_panel_with_ajax() {

        if( isset( $_POST['fields'] ) ) {
            parse_str( $_POST['fields'], $settings );
        } else {
            return;
        }

        $this->trizen_settings_panel = [];

        foreach( $this->trizen_setting_default_keys as $key ){
            if( isset( $settings[ $key ] ) ) {
                $this->trizen_settings_panel[ $key ] = 1;
            } else {
                $this->trizen_settings_panel[ $key ] = 0;
            }
        }
        update_option( 'trizen_save_settings_panel', $this->trizen_settings_panel );
        return true;
        die();

    }

}

Ts_Admin_Settings::inst();



