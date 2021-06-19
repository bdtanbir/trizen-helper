<?php

class TS_Admin_Settings {

    public static $_inst;
    private static $_allSettings = [];

    public function __construct() {
        add_action('admin_menu', [$this, '__registerPage'], 9);
    }

    public function __registerPage() {
        add_menu_page(
            'Theme Settings',
            'Theme Settings ',
            'manage_options',
            'ts_trizen_option',
            [$this, '__showPage'],
            TRIZEN_HELPER_URI . '/admin/img/favicon.png',
            35 );
    }

    public function __showPage() {
        ?>
        <div class="wrap">
            <div id="traveler_settings_app"></div>
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



