<?php

if ( !class_exists( 'TSAdminRoom' ) ) {

    class TSAdminRoom
    {

        protected static $_inst;
        static $_table_version = "1.3.6";
        static $booking_page;
        protected $post_type = 'hotel_room';

        /**
         *
         *
         * @update 1.1.3
         * */
        function __construct()
        {
            add_action('plugins_loaded', [__CLASS__, '_check_table_hotel_room']);

            add_filter('ts_change_column_ts_hotel_room', [$this, 'ts_change_column_ts_hotel_room_fnc']);
        }

        function ts_change_column_ts_hotel_room_fnc($column) {
            $new_column = array_merge( $column, [
                'adult_price'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'child_price'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
            ]);
            return $new_column;
        }

        static function check_ver_working()
        {
            $dbhelper = new DatabaseHelper(self::$_table_version);

            return $dbhelper->check_ver_working('ts_hotel_room_table_version');
        }

        static function _check_table_hotel_room()
        {
            var_dump('database ');
            $dbhelper = new DatabaseHelper(self::$_table_version);
            $dbhelper->setTableName('hotel_room');
            $column = [
                'post_id' => [
                    'type' => 'INT',
                    'length' => 11,
                ],
                'room_parent' => [
                    'type' => 'INT',
                    'length' => 11,
                ],
                /*'multi_location' => [
                    'type' => 'text',
                ],*/
                'id_location' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'address' => [
                    'type' => 'text',
                ],
                /*'allow_full_day' => [
                    'type' => 'varchar',
                    'length' => 255
                ],*/
                'price' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'number_room' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                /*'discount_rate' => [
                    'type' => 'varchar',
                    'length' => 255
                ],*/
                'adult_number' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'child_number' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'status' => [
                    'type' => 'varchar',
                    'length' => 20
                ],
            ];

            $column = apply_filters('ts_change_column_ts_hotel_room', $column);

            $dbhelper->setDefaultColums($column);
            $dbhelper->check_meta_table_is_working('ts_hotel_room_table_version');

            return array_keys($column);
        }

        static function inst()
        {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }
    TSAdminRoom::inst();
}

