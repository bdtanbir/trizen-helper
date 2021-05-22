<?php
$order_id = 0;
if (!class_exists('TSAdminCars')) {

    class TSAdminCars {

        static $booking_page;
        static $data_term;
        static $_table_version = "1.0";
        protected $post_type = "ts_cars";


        function __construct()
        {

        }

        static function inst() {
            static $instance;
            if (is_null($instance)) {
                $instance = new self();
            }
            return $instance;
        }

        public function get_data_location_from_to($post_id) {
            return ts_get_data_location_from_to($post_id);
        }

        function init() {

        }

    }

    $a = new TSAdminCars();
    $a->init();
}