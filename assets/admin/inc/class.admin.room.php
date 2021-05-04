<?php

if ( !class_exists( 'TSAdminRoom' ) ) {

    class TSAdminRoom
    {

        protected static $_inst;
        static $_table_version = "1.3.6";
        static $booking_page;
        protected $post_type = 'hotel_room';

        protected $order_id=false;

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


        public function getMeta($key)
        {
            return get_post_meta($this->order_id,$key,true);
        }

        /**
         * @todo Get Type of Order: normal_booking or woocommerce
         *
         * @return string
         */
        public function getType()
        {

        }


        /**
         * @todo Check if current order is using Woocommerce Checkout
         *
         * @return bool
         */
        public function isWoocommerceCheckout()
        {
            return $this->getType()=='woocommerce'?true:false;
        }

        /**
         * @return WC_Order
         */
        public function getWoocommerceOrder()
        {
            global $wpdb;

            return new WC_Order($this->order_id);
        }

        /**
         * @todo Get total amount
         *
         * @return float
         */
        public function getTotal()
        {
            if ($this->isWoocommerceCheckout()) {
                global $wpdb;
                $querystr = "SELECT meta_value FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
                            WHERE
                            1=1
                            AND order_item_id = '{$this->order_id}'
                            AND (
                                meta_key = '_line_total'
                                OR meta_key = '_line_tax'
                                OR meta_key = '_ts_booking_fee_price'
                            )
                            ";
                $price = $wpdb->get_results($querystr, OBJECT);
                $data_price = 0;
                if (!empty($price)) {
                    foreach ($price as $k => $v) {
                        $data_price += $v->meta_value;
                    }
                }
                return $data_price;
            } else {
                return $this->getMeta('total_price');
            }
        }

        public function getItems()
        {
            global $wpdb;
            if($this->isWoocommerceCheckout())
            {
                if($order=$this->getWoocommerceOrder())
                {
                    return $order->get_items();
                }
                return [];
            }

            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}ts_order_item_meta"));

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
