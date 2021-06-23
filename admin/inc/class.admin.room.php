<?php

if ( !class_exists( 'TSAdminRoom' ) ) {

    class TSAdminRoom {
        protected static $_inst;
        static $_table_version = "1.0";
        static $booking_page;
        protected $post_type = 'hotel_room';
        protected static $_cachedAlCurrency = [];
        private static $_check_table_duplicate = [];
//        private static $_booking_primary_currency;

        protected $order_id=false;

        /**
         * @since 1.0.0
         * */
        function __construct() {
            add_action('plugins_loaded', [$this, '_check_table_hotel_room']);

            add_filter('ts_change_column_ts_hotel_room', [$this, 'ts_change_column_ts_hotel_room_fnc']);

            add_action( 'wp_ajax_ts_get_cancel_booking_step_1', [ $this, 'ts_get_cancel_booking_step_1' ] );
            add_action( 'wp_ajax_ts_get_cancel_booking_step_2', [ $this, 'ts_get_cancel_booking_step_2' ] );
            add_action( 'wp_ajax_ts_get_cancel_booking_step_3', [ $this, 'ts_get_cancel_booking_step_3' ] );
            add_action( 'parse_query', [ $this, 'parse_query_hotel_room' ] );
            add_action( 'save_post', [ $this, '_update_list_location' ], 999999, 2 );
            add_action( 'added_post_meta', [ $this, 'hotel_update_min_price' ], 10, 4 );
            add_filter( 'posts_where', [__CLASS__, '_alter_search_query'] );

            add_action('before_delete_post', [$this, '_delete_data'], 50);

            add_action('init', array($this, '__register_cronjob'), 1);
            add_action('ts_availability_cronjob', array($this, '__cronjob_fill_availability'));


            //Check booking edit and redirect
            if (self::is_booking_page()) {
//                add_action('admin_enqueue_scripts', [__CLASS__, 'add_edit_scripts']);
                add_action('admin_init', [$this, '_do_save_booking']);
            }
            add_action('admin_init', [$this, '_upgradeRoomTable135']);
        }

        public function _delete_data($post_id){
            if (get_post_type($post_id) == 'hotel_room') {
                global $wpdb;
                $table = $wpdb->prefix . 'hotel_room';
                $rs = TravelHelper::deleteDuplicateData($post_id, $table);
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
                $sql   = "Update {$table} as t inner join {$wpdb->posts} as m on (t.post_id = m.ID and m.post_type='hotel_room') set t.`status` = m.post_status";
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

        static function check_ver_working() {
            $dbhelper = new DatabaseHelper(self::$_table_version);
            return $dbhelper->check_ver_working('ts_hotel_room_table_version');
        }

        static function _check_table_hotel_room(){
            $dbhelper = new DatabaseHelper(self::$_table_version);
            $dbhelper->setTableName('hotel_room');
            $column = [
                'post_id'    => [
                    'type'   => 'INT',
                    'length' => 11,
                ],
                'room_parent' => [
                    'type'    => 'INT',
                    'length'  => 11,
                ],
                'multi_location' => [
                    'type' => 'text',
                ],
                'id_location' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'address' => [
                    'type' => 'text',
                ],
                'allow_full_day' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'price' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'number_room' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'discount_rate' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'adult_number' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'child_number' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'status' => [
                    'type'   => 'varchar',
                    'length' => 20
                ],
            ];
            $column = apply_filters('ts_change_column_ts_hotel_room', $column);
            $dbhelper->setDefaultColums($column);
            $dbhelper->check_meta_table_is_working('ts_hotel_room_table_version');

            return array_keys($column);
        }

        /**
         * @since 1.0
         **/
        static function is_booking_page() {
            if ( is_admin()
                and isset( $_GET[ 'post_type' ] )
                and $_GET[ 'post_type' ] == 'hotel_room'
                and isset( $_GET[ 'page' ] )
                and $_GET[ 'page' ] = 'ts_hotel_room_booking'
            ) return TRUE;

            return FALSE;
        }

        /**
         * @since 1.0
         **/
        function _delete_items() {
            if ( empty( $_POST ) or !check_admin_referer( 'shb_action', 'shb_field' ) ) {
                //// process form data, e.g. update fields
                return;
            }
            $ids = isset( $_POST[ 'post' ] ) ? $_POST[ 'post' ] : [];
            if ( !empty( $ids ) ) {
                foreach ( $ids as $id )
                    wp_delete_post( $id, TRUE );

            }
            set_message( __( "Delete item(s) success", 'trizen-helper' ), 'updated' );
        }

        /**
         * @since 1.0
         **/
        function _resend_mail() {
            /*$order_item = isset($_GET['order_item_id']) ? $_GET['order_item_id'] : FALSE;
            $test       = isset($_GET['test']) ? $_GET['test'] : FALSE;
            if ($order_item) {
                $order = $order_item;
                if ($test) {
                    global $order_id;
                    $order_id       = $order_item;
                    $email_to_admin = st()->get_option('email_for_admin', '');
                    $email          = st()->load_template('email/header');
                    $email         .= TravelHelper::_get_template_email($email, $email_to_admin);
                    $email         .= st()->load_template('email/footer');
                    echo($email);
                    die;

                }
                if ($order) {
                    $booking_by = get_post_meta($order_item, 'booking_by', true);
                    $made_by_admin = false;
                    if ($booking_by && $booking_by == 'admin') {
                        $made_by_admin = true;
                    }
                    TSCart::send_mail_after_booking($order, $made_by_admin);
                }
            }
            wp_safe_redirect(self::$booking_page . '&send_mail=success');*/
        }

        /**
         * @since 1.0
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
                case 'resend_email':
                    $this->_resend_mail();
                    break;
            }
        }

        /**
         * @since 1.0
         **/
        function _save_booking( $order_id ) {
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

//                TSCart::send_mail_after_booking($order_id, true);
                wp_safe_redirect(self::$booking_page);
            }
        }

        /**
         * @since 1.0
         **/
        public function _check_validate() {

            $ts_first_name = request( 'ts_first_name', '' );
            if ( empty( $ts_first_name ) ) {
                set_message( __( 'The firstname field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_last_name = request( 'ts_last_name', '' );
            if ( empty( $ts_last_name ) ) {
                set_message( __( 'The lastname field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_email = request( 'ts_email', '' );
            if ( empty( $ts_email ) ) {
                set_message( __( 'The email field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            if ( !filter_var( $ts_email, FILTER_VALIDATE_EMAIL ) ) {
                set_message( __( 'Invalid email format.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_phone = request( 'ts_phone', '' );
            if ( empty( $ts_phone ) ) {
                set_message( __( 'The phone field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            return true;
        }

        /**
         * @since 1.0
         **/
        function _update_list_location( $id, $data ) {
            $location = request( 'multi_location', '' );
            if ( isset( $_REQUEST[ 'multi_location' ] ) ) {
                if ( is_array( $location ) && count( $location ) ) {
                    $location_str = '';
                    foreach ( $location as $item ) {
                        if ( empty( $location_str ) ) {
                            $location_str .= $item;
                        } else {
                            $location_str .= ',' . $item;
                        }
                    }
                } else {
                    $location_str = '';
                }
                update_post_meta( $id, 'multi_location', $location_str );
                update_post_meta( $id, 'id_location', '' );
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
                        FROM $wpdb->posts WHERE $wpdb->posts.post_title LIKE '%{$_GET['s']}%'
                    )
             )  ";
                $where .= $add_where;
            }
            return $where;
        }

        static function _cancel_booking( $order_id )
        {
            /*$check_cancel_able = check_cancel_able( $order_id );
            if ( $check_cancel_able ) {
                global $wpdb;
                $user_id       = get_current_user_id();
                $order_item_id = $order_id;
                $check_order = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}ts_order_item_meta where user_id={$user_id} and  order_item_id = {$order_item_id} and `status`!='canceled' and `status`!='wc-canceled' LIMIT 0,1" );
                if ( $check_order ) {
                    $item_id = $check_order->ts_booking_id;
                    if ( $check_order->room_id ) $item_id = $check_order->room_id;
                    $cancel_percent = get_post_meta( $item_id, 'st_cancel_percent', true );
                    $query = "UPDATE {$wpdb->prefix}ts_order_item_meta set `status`='canceled' , cancel_percent={$cancel_percent} where order_item_id={$order_item_id}";
                    $wpdb->query( $query );
                    update_post_meta( $order_item_id, 'status', 'canceled' );
                    return true;
                }
            } else {
                return false;
            }*/
        }

//        public function ts_get_cancel_booking_step_1() {
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
////                $message = st()->load_template( 'user/cancel-booking/cancel', 'step-1', [ 'order_id' => $order_id ] );
//                $message = '';
//
//                $_SESSION[ 'cancel_data' ][ 'order_id' ]      = $order_id;
//                $_SESSION[ 'cancel_data' ][ 'order_encrypt' ] = $order_encrypt;
//                $status                                       = get_post_meta( $order_id, 'status', true );
//                $total_price                                  = (float) get_post_meta( $order_id, 'total_price', true);
//                $item_id                                      = (int) get_post_meta( $order_id, 'ts_booking_id', true);
//                $percent                                      = (int) get_post_meta( $item_id, 'ts_cancel_percent', true );
//                $refunded                                     = $total_price - ( $total_price * $percent / 100 );
//
//                $step = 'next-to-step-2';
//                if ( $status != 'complete' || ($percent == 100 && $refunded == 0 ) ) {
//                    $step = 'next-to-step-3';
//                }
//                echo json_encode( [
//                    'status'        => 1,
//                    'message'       => $message,
//                    'order_id'      => $order_id,
//                    'order_encrypt' => $order_encrypt,
//                    'step'          => $step
//                ] );
//                die;
//            }
//            echo json_encode( [
//                'status'        => 0,
//                'message'       => '<div class="text-danger">' . __( 'Have an error when get data. Try again!', 'trizen-helper' ) . '</div>',
//                'order_id'      => $order_id,
//                'order_encrypt' => $order_encrypt,
//                'step'          => ''
//            ] );
//            die;
//        }

//        public function ts_get_cancel_booking_step_2() {
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//            $why_cancel    = request( 'why_cancel', '' );
//            $detail        = request( 'detail', '' );
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
////                $message = st()->load_template( 'user/cancel-booking/cancel', 'step-2', [ 'order_id' => $order_id ] );
//                $message = '';
//                $_SESSION[ 'cancel_data' ][ 'why_cancel' ] = $why_cancel;
//                $_SESSION[ 'cancel_data' ][ 'detail' ]     = $detail;
//                echo json_encode( [
//                    'status'        => 1,
//                    'message'       => $message,
//                    'order_id'      => $order_id,
//                    'order_encrypt' => $order_encrypt,
//                    'step'          => 'next-to-step-3'
//                ] );
//                die;
//            }
//            echo json_encode( [
//                'status'        => 0,
//                'message'       => '<div class="text-danger">' . __( 'Have an error when get data. Try again!', 'trizen-helper' ) . '</div>',
//                'order_id'      => $order_id,
//                'order_encrypt' => $order_encrypt,
//                'step'          => ''
//            ] );
//            die;
//        }

//        public function ts_get_cancel_booking_step_3()
//        {
//            global $wpdb;
//            global $cancel_order_id, $cancel_cancel_data;
//
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
//                $item_id        = (int)get_post_meta( $order_id, 'ts_booking_id', true );
//                $post_type      = get_post_meta( $order_id, 'ts_booking_post_type', true );
//                $post_author_id = get_post_field( 'post_author', $item_id );
//                if ( $post_type == 'ts_hotel' ) {
//                    $room_id        = (int)get_post_meta( $order_id, 'room_id', true );
//                    $post_author_id = get_post_field( 'post_author', $room_id );
//                }
//
//                $author_obj = get_userdata( $post_author_id );
//                $user_email = $author_obj->data->user_email;
//                $user_role  = array_shift( $author_obj->roles );
//
//                $total_price = (float)get_post_meta( $order_id, 'total_price', true );
//                $currency    = _get_currency_book_history( $order_id );
//
//                $percent = (int)get_post_meta( $item_id, 'ts_cancel_percent', true );
//                if ( $post_type == 'ts_hotel' && isset( $room_id ) ) {
//                    $percent = (int)get_post_meta( $room_id, 'ts_cancel_percent', true );
//                }
//
//                $refunded             = $total_price - ( $total_price * $percent / 100 );
//                $status               = get_post_meta( $order_id, 'status', true );
//                $cancel_refund_status = 'pending';
//
//                if ( $status != 'complete' ) {
//                    $refunded             = 0;
//                    $cancel_refund_status = 'complete';
//                }
//
//                $select_account = request( 'select_account', '' );
//
//                $refund_for_partner  = 'false';
//                $percent_for_partner = 'false';
//
//                $enable_email_cancel         = st()->get_option( 'enable_email_cancel', 'on' );
//                $enable_partner_email_cancel = st()->get_option( 'enable_partner_email_cancel', 'on' );
//                $enable_email_cancel_user    = st()->get_option( 'enable_email_cancel_success', 'on' );
//
//                if ( empty( $select_account ) ) {
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//                        //Update number_booked
//                        AvailabilityHelper::syncAvailabilityAfterCanceled( $order_id );
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'none', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        if ( $status == 'incomplete' ) {
//                            /*if ( $enable_email_cancel == 'on' ) {
//                                $this->_send_email_refund( $order_id, 'has-refund' );
//                            }
//                            if ( $enable_email_cancel_user == 'on' ) {
//                                $this->_send_email_refund( $order_id, 'success' );
//                            }
//
//                            if ( $enable_partner_email_cancel == 'on' ) {
//                                if ( $user_role == 'partner' ) {
//                                    $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                                }
//                            }*/
//                        }
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//                }
//                if ( $select_account == 'your_bank' ) {
//                    $account_name   = request( 'account_name', '' );
//                    $account_number = request( 'account_number', '' );
//                    $bank_name      = request( 'bank_name', '' );
//                    $swift_code     = request( 'swift_code', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => [
//                            'account_name'   => $account_name,
//                            'account_number' => $account_number,
//                            'bank_name'      => $bank_name,
//                            'swift_code'     => $swift_code
//                        ],
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'bank', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        /*if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }*/
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_paypal' ) {
//
//                    $paypal_email = request( 'paypal_email', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => [
//                            'paypal_email' => $paypal_email
//                        ],
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'paypal', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        /*if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }*/
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_stripe' ) {
//
//                    $transaction_id = request( 'transaction_id', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'your_stripe'          => [
//                            'transaction_id' => $transaction_id
//                        ],
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'stripe', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_payfast' ) {
//
//                    $transaction_id = request( 'transaction_id', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => [
//                            'transaction_id' => $transaction_id
//                        ],
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'payfast', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        //3rd action
//                        do_action( 'st_booking_cancel_order_item', $order_id );
//
//                        if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//
//            }
//            echo json_encode( [
//                'status'  => 1,
//                'message' => '<div class="text-danger">' . __( 'You can not cancel this booking', 'trizen-helper' ) . '</div>',
//                'step'    => ''
//
//            ] );
//            die;
//        }


        public function __cronjob_fill_availability($offset=0, $limit=-1, $day=null) {
            global $wpdb;
            if(!$day){
                $today = new DateTime(date('Y-m-d'));
                $today->modify('+ 6 months');
                $day=$today->modify('+ 1 day');
            }

            $table = 'ts_room_availability';
            $rooms = new WP_Query(array(
                'posts_per_page' => $limit,
                'post_type'      => 'hotel_room',
                'offset'         => $offset
            ));
            $insertBatch = [];
            $ids         = [];

            while ($rooms->have_posts()) {
                $rooms->the_post();
                $price          = get_post_meta(get_the_ID(),'price',true);
                $parent         = get_post_meta(get_the_ID(),'room_parent',true);
                $status         = get_post_meta(get_the_ID(),'default_state',true);
                $number         = get_post_meta(get_the_ID(),'number_room',true);
                $allow_full_day = get_post_meta(get_the_ID(),'allow_full_day',true);
                $adult_number   = intval( get_post_meta( get_the_ID(), 'adult_number', true ) );
                $child_number   = intval( get_post_meta( get_the_ID(), 'children_number', true ) );
                $booking_period = intval(get_post_meta($parent, 'hotel_booking_period', true));
                if(empty($booking_period)) $booking_period = 0;
                if(!$allow_full_day) $allow_full_day='on';
                $adult_price = get_post_meta( get_the_ID(), 'adult_price', true );
                $child_price = get_post_meta( get_the_ID(), 'child_price', true );

                $insertBatch[] = $wpdb->prepare("(%d,%d,%d,%d,%s,%d,%s,%d,%s,%d,%d,%d,%d,%d,%d)",$day->getTimestamp(),$day->getTimestamp(),get_the_ID(),$parent,'hotel_room',$number,$status,$price,$allow_full_day,$adult_number,$child_number,1,$booking_period, $adult_price, $child_price);

                $ids[]=get_the_ID();
            }

            if(!empty($insertBatch)) {
                $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$table} (check_in,check_out,post_id,parent_id,post_type,`number`,`status`,price,	allow_full_day,adult_number,child_number,is_base,booking_period, adult_price, child_price) VALUES ".implode(",\r\n",$insertBatch));

                // add log
                //ST_Cronjob_Log_Model::inst()->log('room_fill_availability_'.$day->format('Y_m_d'),json_encode($ids));
            }

            wp_reset_postdata();
        }

        public static function fill_post_availability($post_id,$timestamp=null) {
            $data  = [];
            global $wpdb;
            $table = 'ts_room_availability';

            $price          = get_post_meta($post_id,'price',true);
            $parent         = get_post_meta($post_id,'room_parent',true);
            $status         = get_post_meta($post_id,'default_state',true);
            $number         = get_post_meta($post_id,'number_room',true);
            $allow_full_day = get_post_meta($post_id,'allow_full_day',true);
            if(!$allow_full_day) $allow_full_day='on';
            $rs = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin',$post_id)
                ->where('check_in_timestamp <=',$timestamp)
                ->where('check_out_timestamp >=',$timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')",false,true)
                ->get(1)->row();
            $number_end = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin',$post_id)
                ->where('check_out_timestamp',$timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')",false,true)
                ->get(1)->row();
            $adult_number = intval( get_post_meta( get_the_ID(), 'adult_number', true ) );
            $child_number = intval( get_post_meta( get_the_ID(), 'child_number', true ) );
            $adult_price = get_post_meta( $post_id, 'adult_price', true );
            $child_price = get_post_meta( $post_id, 'child_price', true );

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

        public function parse_query_hotel_room( $query ) {
            global $pagenow;
            if ( isset( $_GET[ 'post_type' ] ) ) {
                $type = $_GET[ 'post_type' ];
                if ( 'hotel_room' == $type && is_admin() && $pagenow == 'edit.php' && isset( $_GET[ 'filter_ts_hotel' ] ) && $_GET[ 'filter_ts_hotel' ] != '' ) {
                    add_filter( 'posts_where', [ $this, 'posts_where_hotel_room' ] );
                    add_filter( 'posts_join', [ $this, 'posts_join_hotel_room' ] );
                }
            }

        }

        public function posts_where_hotel_room( $where ){
            global $wpdb;
            $hotel_name = $_GET[ 'filter_ts_hotel' ];
            $where .= " AND mt2.meta_value in (select ID from {$wpdb->prefix}posts where post_title like '%{$hotel_name}%' and post_type = 'st_hotel' and post_status in ('publish', 'private') ) ";

            return $where;
        }

        public function posts_join_hotel_room( $join ) {
            global $wpdb;
            $join .= " inner join {$wpdb->prefix}postmeta as mt2 on mt2.post_id = {$wpdb->prefix}posts.ID and mt2.meta_key='room_parent' ";

            return $join;
        }

        static function isset_table( $table_name ) {
            global $wpdb;
            $table = $wpdb->prefix . $table_name;
            if ( !empty( self::$_check_table_duplicate[ $table_name ] ) ) return true;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                return false;
            }
            return true;
        }

        static function _update_avg_price($post_id = false) {
            if (!$post_id) {
                $post_id = get_the_ID();
            }
            $post_type = get_post_type($post_id);
            if ($post_type == 'ts_hotel') {
                $hotel_id = $post_id;
                $is_auto_caculate = get_post_meta($hotel_id, 'enable_is_auto_calculate', true);
                if ($is_auto_caculate == 1) {
                    $query = [
                        'post_type'      => 'hotel_room',
                        'posts_per_page' => -1,
                        'meta_key'       => 'room_parent',
                        'meta_value'     => $hotel_id,
                        'post_status'    => array( 'publish' )
                    ];
                    $traver = new WP_Query($query);
                    $price = 0;
                    while ($traver->have_posts()) {
                        $traver->the_post();
                        /*if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                            $item_price = (float)get_post_meta(get_the_ID(), 'adult_price', true);
                        } else {*/
                            $item_price = (float)get_post_meta(get_the_ID(), 'price', true);
//                        }

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
            if ( $post_type == 'hotel_room' ) {
                $hotel_id = get_post_meta( $post_id, 'room_parent', true );
                if ( !empty( $hotel_id ) ) {
                    $is_auto_caculate = get_post_meta( $hotel_id, 'enable_is_auto_calculate', true );
                    if ( $is_auto_caculate == 1 ) {
                        $query  = [
                            'post_type'      => 'hotel_room',
                            'posts_per_page' => 999,
                            'meta_key'       => 'room_parent',
                            'meta_value'     => $hotel_id
                        ];
                        $traver = new WP_Query( $query );
                        $price  = 0;
                        while ( $traver->have_posts() ) {
                            $traver->the_post();
                            $discount   = get_post_meta( get_the_ID(), 'discount_rate', TRUE );
                            /*if ( get_post_meta( get_the_ID(), 'price_by_per_person', true ) == 'on' ) {
                                $adult_price = floatval( get_post_meta( get_the_ID(), 'adult_price', true ) );
                                $child_price = floatval( get_post_meta( get_the_ID(), 'child_price', true ) );
                                $item_price  = max( $adult_price, $child_price );
                            } else {*/
                                $item_price = get_post_meta( get_the_ID(), 'price', TRUE );
//                            }
                            if ( $discount ) {
                                if ( $discount > 100 ) $discount = 100;
                                $item_price = $item_price - ( $item_price / 100 ) * $discount;
                            }
                            $price += $item_price;
                        }
                        wp_reset_query();
                        $avg_price = 0;
                        if ( $traver->post_count ) {
                            $avg_price = $price / $traver->post_count;
                        }
                        update_post_meta( $hotel_id, 'price_avg', $avg_price );
                    }
                }
            }
        }

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
                    /*if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                        $query_price = TS_Hotel_Room_Availability::inst()
                            ->select("min(CAST(adult_price as DECIMAL)) as min_price")
                            ->where('status', 'available')
                            ->where('post_id', get_the_ID())
                            ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                            ->get()->result();

                        if (!empty($query_price)) {
                            $item_price = floatval($query_price[0]['min_price']);
                        } else {
                            $item_price = floatval(get_post_meta(get_the_ID(), 'price', true));
                        }
                    } else {*/
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
//                    }

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
                    /*if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                        $query_price = TS_Hotel_Room_Availability::inst()
                            ->select("min(CAST(adult_price as DECIMAL)) as min_price")
                            ->where('status', 'available')
                            ->where('post_id', get_the_ID())
                            ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                            ->get()->result();

                        if (!empty($query_price)) {
                            $item_price = floatval($query_price[0]['min_price']);
                        } else {
                            $item_price = floatval(get_post_meta(get_the_ID(), 'price', true));
                        }
                    } else {*/
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
//                    }

                    update_post_meta($post_id, 'min_price', $item_price);
                }
            }
        }

        static function _update_duplicate_data($id, $data) {
            if (!TravelHelper::checkTableDuplicate('ts_hotel')) return;
            if (get_post_type($id) == 'ts_hotel') {
                $num_rows       = TravelHelper::checkIssetPost($id, 'ts_hotel');
                $location_str   = get_post_meta($id, 'multi_location', true);
                $location_id    = ''; // location_id
                $address        = get_post_meta($id, 'address', true); // address
                $allow_full_day = get_post_meta($id, 'allow_full_day', true); // address

                $rate_review          = TSReview::get_avg_rate($id); // rate review
                $hotel_star           = get_post_meta($id, 'hotel_star', true); // hotel star
                $price_avg            = get_post_meta($id, 'price_avg', true); // price avg
                $min_price            = get_post_meta($id, 'min_price', true); // price avg
                $hotel_booking_period = get_post_meta($id, 'hotel_booking_period', true); // price avg
                $map_lat              = get_post_meta($id, 'lat', true); // lat
                $map_lng              = get_post_meta($id, 'lng', true); // lng

                if ($num_rows == 1) {
                    $data = [
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allow_full_day,
                        'rate_review'          => $rate_review,
                        'hotel_star'           => $hotel_star,
                        'price_avg'            => $price_avg,
                        'min_price'            => $min_price,
                        'hotel_booking_period' => $hotel_booking_period,
                        'map_lat'              => $map_lat,
                        'map_lng'              => $map_lng,
                    ];
                    $where = [
                        'post_id' => $id
                    ];
                    TravelHelper::updateDuplicate('ts_hotel', $data, $where);
                } elseif ($num_rows == 0) {
                    $data = [
                        'post_id'              => $id,
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allow_full_day,
                        'rate_review'          => $rate_review,
                        'hotel_star'           => $hotel_star,
                        'price_avg'            => $price_avg,
                        'min_price'            => $min_price,
                        'hotel_booking_period' => $hotel_booking_period,
                        'map_lat'              => $map_lat,
                        'map_lng'              => $map_lng,
                    ];
                    TravelHelper::insertDuplicate('ts_hotel', $data);
                }
            }

            // for room
            if ( get_post_type( $id ) == 'hotel_room' ) {
                $num_rows       = TravelHelper::checkIssetPost( $id, 'hotel_room' );
                $allow_full_day = get_post_meta( $id, 'allow_full_day', true ); // address
                $data           = [
                    'room_parent'    => get_post_meta( $id, 'room_parent', true ),
                    'multi_location' => get_post_meta( $id, 'multi_location', true ),
                    'id_location'    => get_post_meta( $id, 'id_location', true ),
                    'address'        => get_post_meta( $id, 'address', true ),
                    'allow_full_day' => $allow_full_day,
                    'price'          => get_post_meta( $id, 'price', true ),
                    'number_room'    => get_post_meta( $id, 'number_room', true ),
                    'discount_rate'  => get_post_meta( $id, 'discount_rate', true ),
                    'adult_number'   => get_post_meta( $id, 'adult_number', true),
                    'child_number'   => get_post_meta( $id, 'children_number', true),
                    'adult_price'    => get_post_meta( $id, 'adult_price', true ),
                    'child_price'    => get_post_meta( $id, 'child_price', true ),
                    'status'         => get_post_field('post_status', $id)
                ];
                if ( $num_rows == 1 ) {
                    $where = [
                        'post_id' => $id
                    ];
                    TravelHelper::updateDuplicate( 'hotel_room', $data, $where );
                } elseif ( $num_rows == 0 ) {
                    $data[ 'post_id' ] = $id;
                    TravelHelper::insertDuplicate( 'hotel_room', $data );
                }

                // Update Availability
                $model = Ts_Hotel_Room_Availability::inst();
                $model->where('post_id',$id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->update(array(
                        'parent_id'      => $data['room_parent'],
                        'allow_full_day' => $data['allow_full_day'],
                        'number'         => $data['number_room'],
                        'adult_number'   => $data['adult_number'],
                        'child_number'   => $data['child_number']
                    ));

                $model->where('post_id',$id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->where('is_base', '1')
                    ->update(array(
                        'price'       => $data['price'],
                        'adult_price' => $data['adult_price'],
                        'child_price' => $data['child_price'],
                    ));
                $model->where('post_id', $id)->update(['parent_id' => get_post_meta( $id, 'room_parent', true )]);
            }
        }

        function _do_update_hotel_min_price( $hotel_id, $current_meta_price = false, $room_id = false ) {
            if ( !$hotel_id ) return;
            $query = [
                'post_type'      => 'hotel_room',
                'posts_per_page' => 100,
                'meta_key'       => 'room_parent',
                'meta_value'     => $hotel_id
            ];
            if ( $room_id ) {
                $query[ 'posts_not_in' ] = [ $room_id ];
            }
            $q = new WP_Query( $query );
            $min_price = 0;
            $i         = 1;
            while ( $q->have_posts() ) {
                $q->the_post();
                /*if ( get_post_meta( get_the_ID(), 'price_by_per_person', true ) == 'on' ) {
                    $adult_price = floatval( get_post_meta( get_the_ID(), 'adult_price', true ) );
                    $child_price = floatval( get_post_meta( get_the_ID(), 'child_price', true ) );
                    $price = min($adult_price, $child_price);
                } else {*/
                    $price = get_post_meta( get_the_ID(), 'price', true );
//                }
                if ( $i == 1 ) {
                    $min_price = $price;
                } else {
                    if ( $price < $min_price ) {
                        $min_price = $price;
                    }
                }
                $i++;
            }

            wp_reset_query();

            if ( $current_meta_price !== FALSE ) {
                if ( $current_meta_price < $min_price ) {
                    $min_price = $current_meta_price;
                }
            }
            update_post_meta( $hotel_id, 'min_price', $min_price );
        }

        function hotel_update_min_price( $meta_id, $object_id, $meta_key, $meta_value ) {
            $post_type = get_post_type( $object_id );
            if ( wp_is_post_revision( $object_id ) )
                return;
            if ( $post_type == 'hotel_room' ) {
                //Update old room and new room
                if ( $meta_key == 'room_parent' ) {
                    $old = get_post_meta( $object_id, $meta_key, true );
                    if ( $old != $meta_value ) {
                        $this->_do_update_hotel_min_price( $old, false, $object_id );
                        $this->_do_update_hotel_min_price( $meta_value );
                    } else {
                        $this->_do_update_hotel_min_price( $meta_value );
                    }
                }
            }
        }

        function meta_updated_update_min_price( $meta_id, $object_id, $meta_key, $meta_value ){
            if ( $meta_key == 'price' ) {
                $hotel_id = get_post_meta( $object_id, 'room_parent', true );
                $this->_do_update_hotel_min_price( $hotel_id );
            }
        }

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }
    TSAdminRoom::inst();
}

