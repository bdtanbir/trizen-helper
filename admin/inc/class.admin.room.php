<?php
/**
 * @package    WordPress
 * @subpackage Traveler
 * @since      1.0
 *
 * Class TSAdminRoom
 *
 * Created by ShineTheme
 *
 */
if (!class_exists('TSAdminRoom')) {

    class TSAdminRoom {
        protected static $_inst;
        static $_table_version = "1.0";
        static $booking_page;
        protected $post_type = 'hotel_room';

        /**
         *
         *
         * @update 1.1.3
         * */
        function __construct() {
            add_filter('ts_hotel_room_layout', [$this, 'custom_hotel_room_layout']);

            self::$booking_page = admin_url('edit.php?post_type=hotel_room&page=ts_hotel_room_booking');

            //alter where for search room
            add_filter('posts_where', [__CLASS__, '_alter_search_query']);


            //Hotel Hook
            /*
             * todo Re-cal hotel min price
             * */
            /*
            add_action( 'update_post_meta', [ $this, 'hotel_update_min_price' ], 10, 4 );
            add_action( 'updated_post_meta', [ $this, 'meta_updated_update_min_price' ], 10, 4 );
            add_action( 'added_post_meta', [ $this, 'hotel_update_min_price' ], 10, 4 );*/
            //add_action('save_post', [$this, '_update_avg_price'], 50);
            add_action('pre_post_update', [$this, '_update_min_price'], 50, 2);
            add_action('save_post', [$this, '_update_min_price'], 50);
            add_action('save_post', [$this, '_update_duplicate_data'], 51, 2);
            add_action('before_delete_post', [$this, '_update_min_price'], 50);

            add_action('save_post', [$this, '_update_list_location'], 10, 2);

            /**
             *   @since 1.0
             *   auto create & update table ts_hotel
             **/
            add_action('plugins_loaded', [__CLASS__, '_check_table_hotel_room']);

            //Check booking edit and redirect
            if (self::is_booking_page()) {
                add_action('admin_init', [$this, '_do_save_booking']);
            }

            /**
             * @since 1.2.8
             **/
            add_action('parse_query', [$this, 'parse_query_hotel_room']);

            add_action('init', array($this, '__register_cronjob'), 1);
            add_action('ts_availability_cronjob', array($this, '__cronjob_fill_availability'));

            add_filter('ts_change_column_ts_hotel_room', [$this, 'ts_change_column_ts_hotel_room_fnc']);

            add_action('admin_init', [$this, '_upgradeRoomTable135']);

            add_action('before_delete_post', [$this, '_delete_data'], 50);
        }


        public function _delete_data($post_id) {
            if (get_post_type($post_id) == 'hotel_room') {
                global $wpdb;
                $table = $wpdb->prefix . 'hotel_room';
                $rs    = TravelHelper::deleteDuplicateData($post_id, $table);
                if (!$rs)
                    return false;

                return true;
            }
        }

        public function _upgradeRoomTable135() {
            $updated = get_option('_upgradeRoomTable135', false);
            if (!$updated) {
                global $wpdb;
                $table = $wpdb->prefix . $this->post_type;
                $sql = "Update {$table} as t inner join {$wpdb->posts} as m on (t.post_id = m.ID and m.post_type='hotel_room') set t.`status` = m.post_status";
                $wpdb->query($sql);
                update_option('_upgradeRoomTable135', 'updated');
            }
        }

        public function __run_fill_old_order($key = '') {
            $ids = [];
            global $wpdb;
            $table = $wpdb->prefix . 'ts_availability';
            $model = TS_Order_Item_Model::inst();
            $orderItems = $model->where("ts_booking_post_type in ('ts_hotel','hotel_room')", false, true)
                ->where("STATUS NOT IN('canceled','trash')", false, true)->get()->result();
            if (!empty($orderItems)) {

                foreach ($orderItems as $data) {
                    if (!empty($data['room_origin'])) {
                        if (in_array($data['id'], $ids)) continue;
                        $ids[]  = $data['id'];
                        $booked = !empty($data['room_num_search']) ? intval($data['room_num_search']) : 1;

                        $sql = $wpdb->prepare("UPDATE {$table} SET number_booked = IFNULL(number_booked, 0) + %d WHERE post_id = %d AND check_in = %s", $booked, $data['room_origin'], $data['check_in_timestamp']);
                        $wpdb->query($sql);
                        // Check allowed to set Number End
                        if (get_post_meta($data['ts_booking_id'], 'allow_full_day', true) == 1) {
                            $sql = $wpdb->prepare("UPDATE {$table} SET number_end = IFNULL(number_end, 0) + %d WHERE post_id = %d AND check_in = %s", $booked, $data['room_origin'], $data['check_out_timestamp']);
                            $wpdb->query($sql);
                        }

                    }
                }
            }
        }

        public function __cronjob_fill_availability($offset = 0, $limit = -1, $day = null) {
            global $wpdb;
            if (!$day) {
                $today = new DateTime(date('Y-m-d'));
                $today->modify('+ 6 months');
                $day = $today->modify('+ 1 day');
            }

            $table = 'ts_room_availability';

            $rooms = new WP_Query(array(
                'posts_per_page' => $limit,
                'post_type'      => 'hotel_room',
                'offset'         => $offset
            ));
            $insertBatch = [];
            $ids = [];

            while ($rooms->have_posts()) {
                $rooms->the_post();
                $price          = get_post_meta(get_the_ID(), 'price', true);
                $parent         = get_post_meta(get_the_ID(), 'room_parent', true);
                $status         = get_post_meta(get_the_ID(), 'default_state', true);
                $number         = get_post_meta(get_the_ID(), 'number_room', true);
                $allow_full_day = get_post_meta(get_the_ID(), 'allow_full_day', true);
                $adult_number   = intval(get_post_meta(get_the_ID(), 'adult_number', true));
                $child_number   = intval(get_post_meta(get_the_ID(), 'children_number', true));
                $booking_period = intval(get_post_meta($parent, 'hotel_booking_period', true));
                if (empty($booking_period)) $booking_period = 0;
                if ($allow_full_day == 1) {
                    $allow_full_day = 'on';
                } else {
                    $allow_full_day = 'off';
                }
                $adult_price = get_post_meta(get_the_ID(), 'adult_price', true);
                $child_price = get_post_meta(get_the_ID(), 'child_price', true);

                $insertBatch[] = $wpdb->prepare("(%d,%d,%d,%d,%s,%d,%s,%d,%s,%d,%d,%d,%d,%d,%d)", $day->getTimestamp(), $day->getTimestamp(), get_the_ID(), $parent, 'hotel_room', $number, $status, $price, $allow_full_day, $adult_number, $child_number, 1, $booking_period, $adult_price, $child_price);

                $ids[] = get_the_ID();
            }

            if (!empty($insertBatch)) {
                $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$table} (check_in,check_out,post_id,parent_id,post_type,`number`,`status`,price,	allow_full_day,adult_number,child_number,is_base,booking_period, adult_price, child_price) VALUES " . implode(",\r\n", $insertBatch));
            }

            wp_reset_postdata();
        }

        public static function fill_post_availability($post_id, $timestamp = null) {
            $data = [];
            global $wpdb;
            $table = 'ts_room_availability';

            $price  = get_post_meta($post_id, 'price', true);
            $parent = get_post_meta($post_id, 'room_parent', true);
            $status = get_post_meta($post_id, 'default_state', true);
            $number = get_post_meta($post_id, 'number_room', true);
            $allow_full_day = get_post_meta($post_id, 'allow_full_day', true);
            if ($allow_full_day == 1) {
                $allow_full_day = 'on';
            } else {
                $allow_full_day = 'off';
            }
            $rs = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin', $post_id)
                ->where('check_in_timestamp <=', $timestamp)
                ->where('check_out_timestamp >=', $timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')", false, true)
                ->get(1)->row();
            $number_end = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin', $post_id)
                ->where('check_out_timestamp', $timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')", false, true)
                ->get(1)->row();
            $adult_number = intval(get_post_meta(get_the_ID(), 'adult_number', true));
            $child_number = intval(get_post_meta(get_the_ID(), 'child_number', true));
            $adult_price  = get_post_meta($post_id, 'adult_price', true);
            $child_price  = get_post_meta($post_id, 'child_price', true);

            $data['check_in']       = $timestamp;
            $data['check_out']      = $timestamp;
            $data['parent_id']      = $parent;
            $data['post_type']      = 'hotel_room';
            $data['number']         = $number;
            $data['status']         = $status;
            $data['price']          = $price;
            $data['allow_full_day'] = $allow_full_day;
            $data['number_booked']  = $rs['number_booked'];
            $data['number_end']     = $number_end['number_booked'];
            $data['adult_number']   = $adult_number;
            $data['child_number']   = $child_number;
            $data['adult_price']    = $adult_price;
            $data['child_price']    = $child_price;

//                $model=TS_Availability_Model::inst();
//
//                $data['id']=$model->insert($data);

            $insert = $wpdb->prepare("(%d,%d,%d,%d,%s,%d,%d,%d,%s,%d,%s,%d,%d,%d,%d)", $timestamp, $timestamp, $post_id, $parent, 'hotel_room', $number, $rs['number_booked'], $number_end['number_booked'], $status, $price, $allow_full_day, $adult_number, $child_number, $adult_price, $child_price);

            $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$table} (check_in,check_out,post_id,parent_id,post_type,`number`,number_booked,number_end,`status`,price,allow_full_day,adult_number, child_number, adult_price, child_price) VALUES " . $insert);

            return $data;
        }

        public function __register_cronjob() {
            $key = 'ts_availability_cronjob';
            if (!get_option($key)) {
                if (!wp_next_scheduled($key)) {
                    wp_schedule_event(strtotime('2017-01-01 01:00:00'), 'daily', $key);
                    update_option($key, 1);
                }

            }
        }

        public function parse_query_hotel_room($query) {
            global $pagenow;
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
                if ('hotel_room' == $type && is_admin() && $pagenow == 'edit.php' && isset($_GET['filter_ts_hotel']) && $_GET['filter_ts_hotel'] != '') {
                    add_filter('posts_where', [$this, 'posts_where_hotel_room']);
                    add_filter('posts_join', [$this, 'posts_join_hotel_room']);
                }
            }

        }

        public function posts_where_hotel_room($where) {
            global $wpdb;
            $hotel_name = $_GET['filter_ts_hotel'];
            $where     .= " AND mt2.meta_value in (select ID from {$wpdb->prefix}posts where post_title like '%{$hotel_name}%' and post_type = 'ts_hotel' and post_status in ('publish', 'private') ) ";
            return $where;
        }

        public function posts_join_hotel_room($join) {
            global $wpdb;
            $join .= " inner join {$wpdb->prefix}postmeta as mt2 on mt2.post_id = {$wpdb->prefix}posts.ID and mt2.meta_key='room_parent' ";
            return $join;
        }

        static function check_ver_working() {
            $dbhelper = new DatabaseHelper(self::$_table_version);

            return $dbhelper->check_ver_working('ts_hotel_room_table_version');
        }

        static function _check_table_hotel_room() {
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
                'multi_location' => [
                    'type' => 'text',
                ],
                'id_location' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'address' => [
                    'type' => 'text',
                ],
                'allow_full_day' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'price' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'number_room' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'discount_rate' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
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

        /**
         * @since 1.0.0
         **/
        static function is_booking_page() {
            if (is_admin()
                and isset($_GET['post_type'])
                and $_GET['post_type'] == 'hotel_room'
                and isset($_GET['page'])
                and $_GET['page'] = 'ts_hotel_room_booking'
            ) return TRUE;

            return FALSE;
        }

        /**
         * @since 1.0.0
         **/
        function _delete_items() {
            if (empty($_POST) or !check_admin_referer('shb_action', 'shb_field')) {
                //// process form data, e.g. update fields
                return;
            }
            $ids = isset($_POST['post']) ? $_POST['post'] : [];
            if (!empty($ids)) {
                foreach ($ids as $id)
                    wp_delete_post($id, TRUE);

            }

            set_message(__("Delete item(s) success", "trizen-helper"), 'updated');

        }

        /**
         * @since 1.0.0
         **/
        function _do_save_booking() {
            $section = isset($_GET['section']) ? $_GET['section'] : FALSE;
            switch ($section) {
                case "edit_order_item":
                    $item_id = isset($_GET['order_item_id']) ? $_GET['order_item_id'] : FALSE;
                    if (!$item_id or get_post_type($item_id) != 'ts_order') {
                        return FALSE;
                    }
                    if (isset($_POST['submit']) and $_POST['submit']) $this->_save_booking($item_id);
                    break;
            }
        }

        /**
         * @since 1.0.0
         **/
        function _save_booking($order_id) {
            if (!check_admin_referer('shb_action', 'shb_field')) die;
            if ($this->_check_validate()) {

                $item_data = [
                    'status' => $_POST['status'],
                ];

                foreach ($item_data as $val => $value) {
                    update_post_meta($order_id, $val, $value);
                }

                $check_out_field = TSCart::get_checkout_fields();

                if (!empty($check_out_field)) {
                    foreach ($check_out_field as $field_name => $field_desc) {
                        if ($field_name != 'ts_note') {
                            update_post_meta($order_id, $field_name, post($field_name));
                        }
                    }
                }

                if (TravelHelper::checkTableDuplicate('hotel_room')) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'ts_order_item_meta';
                    $where = [
                        'order_item_id' => $order_id
                    ];
                    $data = [
                        'status' => $_POST['status']
                    ];
                    $wpdb->update($table, $data, $where);
                }

                do_action('update_booking_hotel_room', $order_id);
                do_action('ts_admin_edit_booking_status',$item_data['status'],$order_id);

                wp_safe_redirect(self::$booking_page);
            }
        }

        /**
         * @since 1.2.6
         **/
        public function _check_validate() {
            $ts_first_name = request('ts_first_name', '');
            if (empty($ts_first_name)) {
                set_message(__('The firstname field is not empty.', 'trizen-helper'), 'danger');
                return false;
            }

            $ts_last_name = request('ts_last_name', '');
            if (empty($ts_last_name)) {
                set_message(__('The lastname field is not empty.', 'trizen-helper'), 'danger');
                return false;
            }

            $ts_email = request('ts_email', '');
            if (empty($ts_email)) {
                set_message(__('The email field is not empty.', 'trizen-helper'), 'danger');
                return false;
            }

            if (!filter_var($ts_email, FILTER_VALIDATE_EMAIL)) {
                set_message(__('Invalid email format.', 'trizen-helper'), 'danger');
                return false;
            }

            $ts_phone = request('ts_phone', '');
            if (empty($ts_phone)) {
                set_message(__('The phone field is not empty.', 'trizen-helper'), 'danger');
                return false;
            }

            return true;
        }

        /**
         * @since 1.0.0
         **/
        public function custom_hotel_room_layout($old_layout_id = false) {

            if (is_singular('hotel_room')) {
                $meta = get_post_meta(get_the_ID(), 'ts_custom_layout', true);
                if ($meta) {
                    return $meta;
                }
            }

            return $old_layout_id;
        }

        /**
         * @since 1.2.6
         **/
        function _update_list_location($id, $data) {
            $location = request('multi_location', '');
            if (isset($_REQUEST['multi_location'])) {
                if (is_array($location) && count($location)) {
                    $location_str = '';
                    foreach ($location as $item) {
                        if (empty($location_str)) {
                            $location_str .= $item;
                        } else {
                            $location_str .= ',' . $item;
                        }
                    }
                } else {
                    $location_str = '';
                }
                update_post_meta($id, 'multi_location', $location_str);
                update_post_meta($id, 'id_location', '');
            }
        }

        /**
         * @since 1.0.0
         */
        static function _update_avg_price($post_id = false) {
            if (empty($post_id)) {
                $post_id = get_the_ID();
            }
            $post_type = get_post_type($post_id);
            if ($post_type == 'hotel_room') {
                $hotel_id = get_post_meta($post_id, 'room_parent', true);
                if (!empty($hotel_id)) {
                    $is_auto_caculate = get_post_meta($hotel_id, 'is_auto_caculate', true);
                    if ($is_auto_caculate == 1) {
                        $query = [
                            'post_type'      => 'hotel_room',
                            'posts_per_page' => 999,
                            'meta_key'       => 'room_parent',
                            'meta_value'     => $hotel_id
                        ];
                        $traver = new WP_Query($query);
                        $price  = 0;
                        while ($traver->have_posts()) {
                            $traver->the_post();
                            $item_price = (float)get_post_meta(get_the_ID(), 'price', true);
                            $price += $item_price;
                        }
                        wp_reset_query();
                        wp_reset_postdata();
                        $avg_price = 0;
                        if ($traver->post_count) {
                            $avg_price = $price / $traver->post_count;
                        }
                        update_post_meta($hotel_id, 'price_avg', $avg_price);
                    }
                }
            }
        }

        /**
        * @since 1.0.0
        */
        static function _update_min_price($post_id = false) {
            if (empty($post_id)) {
                $post_id = get_the_ID();
            }

            $post_type = get_post_type($post_id);
            if ($post_type == 'hotel_room') {
                $hotel_id     = get_post_meta($post_id, 'room_parent', true);
                $new_hotel_id = post('room_parent');

                $query = [
                    'post_type'      => 'hotel_room',
                    'posts_per_page' => -1,
                    'meta_key'       => 'room_parent',
                    'meta_value'     => $hotel_id
                ];

                if (!empty($hotel_id) && empty($new_hotel_id)) {
                    $query['post__not_in'] = [$post_id];
                }

                $traver = new WP_Query($query);

                $prices = [];
                while ($traver->have_posts()) {
                    $traver->the_post();
                    $query_price = TS_Hotel_Room_Availability::inst()
                        ->select("min(CAST(price as DECIMAL)) as min_price")
                        ->where('status', 'available')
                        ->where('post_id', get_the_ID())
                        ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                        ->get()->result();

                    if (!empty($query_price)) {
                        $item_price = $query_price[0]['min_price'];
                    } else {
                        $item_price = get_post_meta(get_the_ID(), 'price', true);
                    }
                    $prices[] = $item_price;
                }
                wp_reset_query();
                wp_reset_postdata();
                if (!empty($prices)) {
                    $min_price = min($prices);
                    update_post_meta($hotel_id, 'min_price', $min_price);
                } else {
                    update_post_meta($hotel_id, 'min_price', '0');
                }

                if (empty($new_hotel_id)) {
                    $query_price = TS_Hotel_Room_Availability::inst()
                        ->select("min(CAST(price as DECIMAL)) as min_price")
                        ->where('status', 'available')
                        ->where('post_id', get_the_ID())
                        ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                        ->get()->result();

                    if (!empty($query_price)) {
                        $item_price = $query_price[0]['min_price'];
                    } else {
                        $item_price = get_post_meta(get_the_ID(), 'price', true);
                    }
                    update_post_meta($post_id, 'min_price', $item_price);
                }
            }
        }

        /**
         * @since 1.0.0
         */
        function _update_duplicate_data($id, $data) {
            // for room
            if (!TravelHelper::checkTableDuplicate('hotel_room')) return;
            if (get_post_type($id) == 'hotel_room') {
                $num_rows = TravelHelper::checkIssetPost($id, 'hotel_room');
                $allow_full_day = get_post_meta($id, 'allow_full_day', true);
                if($allow_full_day == 1) {
                    $allowed_fullday = 'on';
                } else {
                    $allowed_fullday = 'off';
                }
                $data = [
                    'room_parent'    => get_post_meta( $id, 'room_parent', true ),
                    'multi_location' => get_post_meta( $id, 'multi_location', true ),
                    'id_location'    => get_post_meta( $id, 'id_location', true ),
                    'address'        => get_post_meta( $id, 'address', true ),
                    'allow_full_day' => $allowed_fullday,
                    'price'          => get_post_meta( $id, 'price', true ),
                    'number_room'    => get_post_meta( $id, 'number_room', true ),
                    'discount_rate'  => get_post_meta( $id, 'discount_rate', true ),
                    'adult_number'   => get_post_meta( $id, 'adult_number', true ),
                    'child_number'   => get_post_meta( $id, 'children_number', true ),
                    'adult_price'    => get_post_meta( $id, 'adult_price', true ),
                    'child_price'    => get_post_meta( $id, 'child_price', true ),
                    'status'         => get_post_field('post_status', $id)
                ];
                if ($num_rows == 1) {
                    $where = [
                        'post_id' => $id
                    ];
                    TravelHelper::updateDuplicate('hotel_room', $data, $where);
                } elseif ($num_rows == 0) {
                    $data['post_id'] = $id;
                    TravelHelper::insertDuplicate('hotel_room', $data);
                }


                // Update Availability
                $model = TS_Hotel_Room_Availability::inst();
                $model->where('post_id', $id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->update(array(
                        'parent_id'      => $data['room_parent'],
                        'allow_full_day' => $data['allow_full_day'],
                        'number'         => $data['number_room'],
                        'adult_number'   => $data['adult_number'],
                        'child_number'   => $data['child_number']
                    ));

                $model->where('post_id', $id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->where('is_base', '1')
                    ->update(array(
                        'price'       => $data['price'],
                        'adult_price' => $data['adult_price'],
                        'child_price' => $data['child_price'],
                    ));

                $model->where('post_id', $id)->update(['parent_id' => get_post_meta($id, 'room_parent', true)]);
            }

            // for hotel
            if (!TravelHelper::checkTableDuplicate('ts_hotel')) return;
            if (get_post_type($id) == 'hotel_room') {
                $hotel_id = get_post_meta($id, 'room_parent', true);

                $price_avg = (get_post_meta($hotel_id, 'price_avg', true));
                $min_price = (get_post_meta($hotel_id, 'min_price', true));
                if (!$price_avg) {
                    return;
                }


                $data = [
                    'multi_location'       => get_post_meta($hotel_id, 'multi_location', true),
                    'id_location'          => get_post_meta($hotel_id, 'id_location', true),
                    'address'              => get_post_meta($hotel_id, 'address', true),
                    'rate_review'          => get_post_meta($hotel_id, 'rate_review', true),
                    'hotel_star'           => get_post_meta($hotel_id, 'hotel_star', true),
                    'price_avg'            => $price_avg,
                    'min_price'            => $min_price,
                    'hotel_booking_period' => get_post_meta($hotel_id, 'hotel_booking_period', true),
                    'map_lat'              => get_post_meta($hotel_id, 'lat', true),
                    'map_lng'              => get_post_meta($hotel_id, 'lng', true),
                ];
                $where = [
                    'post_id' => $hotel_id
                ];
                TravelHelper::updateDuplicate('ts_hotel', $data, $where);
            }


        }

        static function _alter_search_query($where) {
            global $wp_query;
            if (!is_admin()) return $where;
            if ($wp_query->get('post_type') != 'hotel_room') return $where;
            global $wpdb;
            if ($wp_query->get('s')) {
                $_GET['s'] = isset($_GET['s']) ? sanitize_title_for_query($_GET['s']) : '';
                $add_where = " OR $wpdb->posts.ID IN (SELECT post_id FROM
                     $wpdb->postmeta
                    WHERE $wpdb->postmeta.meta_key ='room_parent'
                    AND $wpdb->postmeta.meta_value IN (SELECT $wpdb->posts.ID
                        FROM $wpdb->posts WHERE  $wpdb->posts.post_title LIKE '%{$_GET['s']}%'
                    )

             )  ";
               $where .= $add_where;
            }
            return $where;
        }

        function hotel_update_min_price($meta_id, $object_id, $meta_key, $meta_value) {
            $post_type = get_post_type($object_id);
            if (wp_is_post_revision($object_id))
                return;
            if ($post_type == 'hotel_room') {
                //Update old room and new room
                if ($meta_key == 'room_parent') {
                    $old = get_post_meta($object_id, $meta_key, true);
                    if ($old != $meta_value) {
                        $this->_do_update_hotel_min_price($old, false, $object_id);
                        $this->_do_update_hotel_min_price($meta_value);
                    } else {
                        $this->_do_update_hotel_min_price($meta_value);
                    }
                }
            }
        }

        function meta_updated_update_min_price($meta_id, $object_id, $meta_key, $meta_value) {
            if ($meta_key == 'price') {
                $hotel_id = get_post_meta($object_id, 'room_parent', true);
                $this->_do_update_hotel_min_price($hotel_id);

            }
        }

        function _do_update_hotel_min_price($hotel_id, $current_meta_price = false, $room_id = false) {
            if (!$hotel_id) return;
            $query = [
                'post_type'      => 'hotel_room',
                'posts_per_page' => -1,
                'meta_key'       => 'room_parent',
                'meta_value'     => $hotel_id
            ];

            if ($room_id) {
                $query['posts_not_in'] = [$room_id];
            }

            $q = new WP_Query($query);

            $min_price = 0;
            $i         = 1;
            while ($q->have_posts()) {
                $q->the_post();
                $price = get_post_meta(get_the_ID(), 'price', true);
                if ($i == 1) {
                    $min_price = $price;
                } else {
                    if ($price < $min_price) {
                        $min_price = $price;
                    }
                }
                $i++;
            }

            wp_reset_query();

            if ($current_meta_price !== FALSE) {
                if ($current_meta_price < $min_price) {
                    $min_price = $current_meta_price;
                }
            }
            update_post_meta($hotel_id, 'min_price', $min_price);
        }

        function ts_change_column_ts_hotel_room_fnc($column) {
            $new_column = array_merge($column, [
                'adult_price' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'child_price' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
            ]);
            return $new_column;
        }

        static function inst() {
            if (!self::$_inst) {
                self::$_inst = new self();
            }
            return self::$_inst;
        }
    }

    TSAdminRoom::inst();
}
