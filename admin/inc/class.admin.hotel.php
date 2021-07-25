<?php
/**
 * @package    WordPress
 * @subpackage Trizen
 * @since      1.0
 * Class TSAdminHotel
 * Created by TechyDevs
 */

$order_id = 0;
if ( !class_exists( 'TSAdminHotel' ) ) {

    class TSAdminHotel {
        static    $parent_key     = 'room_parent';
        static    $booking_page;
        static    $_table_version = "1.0";
        protected $post_type      = 'ts_hotel';

        /**
         * @update 1.0
         * */
        function __construct()
        {

//            if ( !st_check_service_available( $this->post_type ) ) return;

            self::$booking_page = admin_url( 'edit.php?post_type=ts_hotel&page=ts_hotel_booking' );

            add_action( 'wp_ajax_ts_get_availability_hotel_room', [ &$this, '_get_availability_hotel_room' ] );
            add_action( 'wp_ajax_nopriv_ts_get_availability_hotel_room', [ &$this, '_get_availability_hotel_room' ] );

            //add colum for rooms
            add_filter( 'manage_hotel_room_posts_columns', [ $this, 'add_col_header' ], 10 );
            add_action( 'manage_hotel_room_posts_custom_column', [ $this, 'add_col_content' ], 10, 2 );

            //add colum for rooms
            add_filter( 'manage_ts_hotel_posts_columns', [ $this, 'add_hotel_col_header' ], 10 );
            add_action( 'manage_ts_hotel_posts_custom_column', [ $this, 'add_hotel_col_content' ], 10, 2 );

            //Check booking edit and redirect
            if ( self::is_booking_page() ) {

                add_action( 'admin_enqueue_scripts', [ __CLASS__, 'add_edit_scripts' ] );

                add_action( 'admin_init', [ $this, '_do_save_booking' ] );
            }


            if ( isset( $_GET[ 'send_mail' ] ) and $_GET[ 'send_mail' ] == 'success' ) {
                set_message( __( 'Email sent', 'trizen-helper' ), 'updated' );
            }

            add_action( 'wp_ajax_ts_room_select_ajax_admin', [ __CLASS__, 'ts_room_select_ajax' ] );

            //parent::__construct();

            add_action( 'save_post', [ $this, '_update_avg_price' ], 50 );
            add_action( 'save_post', [ $this, '_update_min_price' ], 50 );
            add_action( 'save_post', [ $this, '_update_list_location' ], 999999, 2 );
            add_action( 'save_post', [ $this, '_update_duplicate_data' ], 51, 2 );
            add_action( 'before_delete_post', [ $this, '_delete_data' ], 50 );

            add_action( 'wp_ajax_ts_getRoomHotelInfo', [ __CLASS__, 'getRoomHotelInfo' ], 9999 );
            add_action( 'wp_ajax_ts_getRoomHotel', [ __CLASS__, 'getRoomHotel' ], 9999 );


            /**
             *   @since 1.0
             *   auto create & update table ts_hotel
             **/
            add_action( 'plugins_loaded', [ __CLASS__, '_check_table_hotel' ] );

            add_action('admin_init', [$this, '_upgradeHotelTable136']);
        }
        public function _upgradeHotelTable136(){
            $updated = get_option('_upgradeHotelTable136', false);
            if(!$updated){
                global $wpdb;
                $table = $wpdb->prefix. $this->post_type;
                $sql   = "Update {$table} as t inner join {$wpdb->postmeta} as m on (t.post_id = m.post_id and m.meta_key = 'is_featured') set t.is_featured = m.meta_value";
                $wpdb->query($sql);
                update_option('_upgradeHotelTable136', 'updated');
            }
        }

        static function check_ver_working() {
            $dbhelper = new DatabaseHelper( self::$_table_version );

            return $dbhelper->check_ver_working( 'ts_hotel_table_version' );
        }

        static function _check_table_hotel() {
            $dbhelper = new DatabaseHelper( self::$_table_version );
            $dbhelper->setTableName( 'ts_hotel' );
            $column = [
                'post_id'              => [
                    'type'   => 'INT',
                    'length' => 11,
                ],
                'multi_location'       => [
                    'type' => 'text',
                ],
                'id_location'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'address'              => [
                    'type' => 'text',
                ],
                'allow_full_day'       => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'rate_review'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'hotel_star'           => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'price_avg'            => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'min_price'            => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'hotel_booking_period' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'map_lat'              => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'map_lng'              => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'is_sale_schedule'     => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'post_origin'     => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'is_featured'        => [
                    'type'   => 'varchar',
                    'length' => 5
                ]
            ];

            $column = apply_filters( 'ts_change_column_ts_hotel', $column );

            $dbhelper->setDefaultColums( $column );
            $dbhelper->check_meta_table_is_working( 'ts_hotel_table_version' );

            return array_keys( $column );
        }

        function _do_save_booking() {
            $section = isset( $_GET[ 'section' ] ) ? $_GET[ 'section' ] : FALSE;
            switch ( $section ) {
                case "edit_order_item":
                    $item_id = isset( $_GET[ 'order_item_id' ] ) ? $_GET[ 'order_item_id' ] : FALSE;
                    if ( !$item_id or get_post_type( $item_id ) != 'ts_order' ) {
                        return FALSE;
                    }
                    if ( isset( $_POST[ 'submit' ] ) and $_POST[ 'submit' ] ) $this->_save_booking( $item_id );
                    break;
                case 'resend_email':
                    $this->_resend_mail();
                    break;
            }
        }

        /**
         * @since 1.0
         **/
        static function _update_duplicate_data( $id, $data ) {
            if ( !TravelHelper::checkTableDuplicate( 'ts_hotel' ) ) return;
            if ( get_post_type( $id ) == 'ts_hotel' ) {
                $num_rows       = TravelHelper::checkIssetPost( $id, 'ts_hotel' );
                $location_str = maybe_unserialize(get_post_meta($id, 'multi_location', true));
                $location_id    = ''; // location_id
                $address        = get_post_meta( $id, 'address', true ); // address
                $allow_full_day = get_post_meta( $id, 'allow_full_day', true ); // address
                if( $allow_full_day == 1 ) {
                    $allowd_fullday = 'on';
                } else {
                    $allowd_fullday = 'off';
                }

                $rate_review          = TSReview::get_avg_rate( $id ); // rate review
                $hotel_star           = get_post_meta( $id, 'hotel_star', true ); // hotel star
                $price_avg            = get_post_meta( $id, 'price_avg', true ); // price avg
                $min_price            = get_post_meta( $id, 'min_price', true ); // price avg
                $hotel_booking_period = get_post_meta( $id, 'hotel_booking_period', true ); // price avg
                $map_lat              = get_post_meta( $id, 'lat', true ); // lat
                $map_lng              = get_post_meta( $id, 'lng', true ); // lng

                if ( $num_rows == 1 ) {
                    $data  = [
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allowd_fullday,
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
                    TravelHelper::updateDuplicate( 'ts_hotel', $data, $where );
                } elseif ( $num_rows == 0 ) {
                    $data = [
                        'post_id'              => $id,
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allowd_fullday,
                        'rate_review'          => $rate_review,
                        'hotel_star'           => $hotel_star,
                        'price_avg'            => $price_avg,
                        'min_price'            => $min_price,
                        'hotel_booking_period' => $hotel_booking_period,
                        'map_lat'              => $map_lat,
                        'map_lng'              => $map_lng,
                    ];
                    TravelHelper::insertDuplicate( 'ts_hotel', $data );
                }
            }
        }

        public function _delete_data( $post_id ) {
            if ( get_post_type( $post_id ) == 'ts_hotel' ) {
                global $wpdb;
                $table = $wpdb->prefix . 'ts_hotel';
                $rs    = TravelHelper::deleteDuplicateData( $post_id, $table );
                if ( !$rs )
                    return false;
                return true;
            }
        }

        static function _get_list_room_by_hotel( $post_id ) {
            global $wpdb;
            $sql = "SELECT * ,mt1.meta_value as multi_location
                    FROM {$wpdb->postmeta}
                    JOIN {$wpdb->postmeta} as mt1 ON mt1.post_id = {$wpdb->postmeta}.post_id and mt1.meta_key = 'multi_location'
                    WHERE
                    {$wpdb->postmeta}.meta_key = 'room_parent'
                    AND
                    {$wpdb->postmeta}.meta_value = '{$post_id}'
                    GROUP BY {$wpdb->postmeta}.post_id";
            $list_room = $wpdb->get_results( $sql );
            return $list_room;
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


        /**
         * @since 1.0
         */
        static function _update_avg_price( $post_id = FALSE ) {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            $post_type = get_post_type( $post_id );
            if ( $post_type == 'ts_hotel' ) {
                $hotel_id         = $post_id;
                $is_auto_caculate = get_post_meta( $hotel_id, 'enable_is_auto_calculate', TRUE );
                if ( $is_auto_caculate == 1 ) {
                    $query  = [
                        'post_type'      => 'hotel_room',
                        'posts_per_page' => 100,
                        'meta_key'       => 'room_parent',
                        'meta_value'     => $hotel_id
                    ];
                    $traver = new WP_Query( $query );
                    $price  = 0;
                    while ( $traver->have_posts() ) {
                        $traver->the_post();
                        $discount   = get_post_meta( get_the_ID(), 'discount_rate', TRUE );
                        $item_price = get_post_meta( get_the_ID(), 'price', TRUE );
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

        /**
         * @since 1.0
         */
        static function _update_min_price( $post_id = false ) {
            if (!$post_id) {
                $post_id = get_the_ID();
            }

            $post_type = get_post_type($post_id);
            if ($post_type == 'ts_hotel') {
                $hotel_id = $post_id;
                $query = [
                    'post_type'      => 'hotel_room',
                    'posts_per_page' => -1,
                    'meta_key'       => 'room_parent',
                    'meta_value'     => $hotel_id,
                    'post_status'    => array('publish')
                ];
                $traver = new WP_Query($query);

                $prices = [];
                while ($traver->have_posts()) {
                    $traver->the_post();
                    $disable_avai_check = get_option('disable_availability_check');
                    /*if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                        $query_price = Ts_Hotel_Room_Availability::inst()
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
                    // if (!$disable_avai_check == 'on') {
                    //     $item_price = get_post_meta(get_the_ID(), 'price', true);

                    // } else {
                    //     if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                    //         $query_price = TS_Hotel_Room_Availability::inst()
                    //             ->select("min(CAST(adult_price as DECIMAL)) as min_price")
                    //             ->where('status', 'available')
                    //             ->where('post_id', get_the_ID())
                    //             ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                    //             ->get()->result();

                    //         if (!empty($query_price)) {
                    //             $item_price = floatval($query_price[0]['min_price']);
                    //         } else {
                    //             $item_price = floatval(get_post_meta(get_the_ID(), 'price', true));
                    //         }
                    //     } else {
                    //         $query_price = TS_Hotel_Room_Availability::inst()
                    //             ->select("min(CAST(price as DECIMAL)) as min_price")
                    //             ->where('status', 'available')
                    //             ->where('post_id', get_the_ID())
                    //             ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                    //             ->get()->result();

                    //         if (!empty($query_price)) {
                    //             $item_price = $query_price[0]['min_price'];
                    //         } else {
                    //             $item_price = get_post_meta(get_the_ID(), 'price', true);
                    //         }
                    //     }
                    // }

                    $prices[] = $item_price;
                }
                // wp_reset_query();
                wp_reset_postdata();
                if (!empty($prices)) {
                    $min_price = min($prices);
                    update_post_meta($post_id, 'min_price', $min_price);
                } else {
                    update_post_meta($hotel_id, 'min_price', '0');
                }
            }
        }


        function _resend_mail() {
            /*$order_item = isset( $_GET[ 'order_item_id' ] ) ? $_GET[ 'order_item_id' ] : FALSE;
            $test = isset( $_GET[ 'test' ] ) ? $_GET[ 'test' ] : FALSE;
            if ( $order_item ) {
                $order = $order_item;
                if ( $test ) {
                    global $order_id;
                    $order_id       = $order_item;
                    $email_to_admin = st()->get_option( 'email_for_admin', '' );
                    $email          = st()->load_template( 'email/header' );
                    $email .= TravelHelper::_get_template_email($email, $email_to_admin);
                    $email .= st()->load_template( 'email/footer' );
                    echo( $email );
                    die;
                }
                if ( $order ) {
                    $booking_by    = get_post_meta( $order_item, 'booking_by', true );
                    $made_by_admin = false;
                    if ( $booking_by && $booking_by == 'admin' ) {
                        $made_by_admin = true;
                    }
                    STCart::send_mail_after_booking( $order, $made_by_admin );
                }
            }
            wp_safe_redirect( self::$booking_page . '&send_mail=success' );*/
        }

        public function _get_availability_hotel_room() {
            $list_date   = [];
            $room_id     = request( 'post_id', '' );
            $check_in    = request( 'start', '' );
            $check_out   = request( 'end', '' );
            $room_origin = post_origin( $room_id );
            $hotel_id    = intval( get_post_meta( $room_origin, 'room_parent', true ) );

            $discount_type=get_post_meta($room_id,'discount_type_no_day',true);
            $discount=get_post_meta($room_id,'discount_rate',true);
            $is_sale_schedule=false;
            $sale_price_from=false;
            $sale_price_to=false;
            $adult_number = request( 'adult_number', '' );
            $child_number = request( 'child_number', '' );

            //if empty hotel ->>>> room only
            if ( empty( $hotel_id ) ) {
                $hotel_id = $room_id;
            }

            $allow_full_day = get_post_meta( $hotel_id, 'allow_full_day', true );
            if ( $allow_full_day == 1 ) {
                $allow_full_day = 'on';
            } else {
                $allow_full_day = 'off';
            }

            $year = date( 'Y', $check_in );
            if ( empty( $year ) ) $year = date( 'Y' );
            $year2 = date( 'Y', $check_out );
            if ( empty( $year2 ) ) $year2 = date( 'Y' );

            $month = date( 'm', $check_in );
            if ( empty( $month ) ) $month = date( 'm' );

            $month2 = date( 'm', $check_out );
            if ( empty( $month2 ) ) $month2 = date( 'm' );


            //$result = HotelHelper::_get_full_ordered( $room_origin, $month, $month2, $year, $year2 );
            $result =   HotelHelper::_get_full_ordered_new( $room_origin, $check_in, $check_out );

            $number_room = get_post_meta( $room_id, 'number_room', true );
            //$min_max     = HotelHelper::_get_min_max_date_ordered( $room_origin, $year, $year2 );
            $min_max     = HotelHelper::_get_min_max_date_ordered_new( $room_origin, $check_in, $check_out );

            $list_date_fist_half_day = [];
            $list_date_last_half_day = [];
            $array_fist_half_day = [];
            $array_last_half_day = [];

            if ( is_array( $min_max ) && count( $min_max ) && is_array( $result ) && count( $result ) ) {
                $disable = [];
                for ( $i = intval( $min_max[ 'min_date' ] ); $i <= intval( $min_max[ 'max_date' ] ); $i = strtotime( '+1 day', $i ) ) {
                    $num_room = 0;
                    $num_room_first_half_day = 0;
                    $num_room_last_half_day = 0;
                    foreach ( $result as $key => $date ) {
                        if ( $allow_full_day == 1 ) {
                            if ( $i >= intval( $date[ 'check_in_timestamp' ] ) && $i <= intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room += $date[ 'number_room' ];
                            }
                        } else {
                            if ( $i > intval( $date[ 'check_in_timestamp' ] ) && $i < intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room += $date[ 'number_room' ];
                            }

                            if ( $i == intval( $date[ 'check_in_timestamp' ] ) ) {
                                $num_room_first_half_day += $date[ 'number_room' ];
                            }
                            if ( $i == intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room_last_half_day += $date['number_room'];
                            }
                        }
                    }
                    $disable[ $i ] = $num_room;
                    $array_fist_half_day[ $i ] = $num_room_first_half_day;
                    $array_last_half_day[ $i ] = $num_room_last_half_day;
                }
                if ( count( $disable ) ) {
                    foreach ( $disable as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date[] = date( getDateFormat(), $key );
                    }
                }
                if ( count( $array_fist_half_day ) ) {
                    foreach ( $array_fist_half_day as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date_fist_half_day[] = date( getDateFormat(), $key );
                    }
                }
                if ( count( $array_last_half_day ) ) {
                    foreach ( $array_last_half_day as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date_last_half_day[] = date( getDateFormat(), $key );
                    }
                }
            }

            $list_date_2 = AvailabilityHelper::_getDisableCustomDate( $room_origin, $month, $month2, $year, $year2 );

            $date1  = strtotime( $year . '-' . $month . '-01' );
            $date2  = strtotime( $year2 . '-' . $month2 . '-01' );
            $date2  = strtotime( date( 'Y-m-t', $date2 ) );
            $today  = strtotime( date( 'Y-m-d' ) );
            $return = [];

            $booking_period = intval( get_post_meta( $hotel_id, 'hotel_booking_period', true ) );

            $room_available = TS_Hotel_Room_Availability::inst()
                ->where('check_in >=', $check_in)
                ->where('check_out <=', $check_out)
                ->where('post_id', $room_origin)
                ->where('status', 'available')
                ->get()->result();
            $data_price_room = [];
            if(!empty($room_available)){
                foreach ($room_available as $kk => $vv){
                    $data_price_room[$vv['check_in']] = ts_apply_discount($vv['price'],$discount_type,$discount);
                }
            }

            for ( $i = $date1; $i <= $date2; $i = strtotime( '+1 day', $i ) ) {
                $period = dateDiff( date( 'Y-m-d', $today ), date( 'Y-m-d', $i ) );
                $d      = date( getDateFormat(), $i );
                if ( in_array( $d, $list_date ) or ( in_array( $d, $list_date_fist_half_day ) and in_array( $d, $list_date_last_half_day ) ) ) {
                    $return[] = [
                        'start'  => date( 'Y-m-d', $i ),
                        'date'   => date( 'Y-m-d', $i ),
                        'day'    => date( 'd', $i ),
                        'status' => 'booked'
                    ];
                } else {
                    if ( $i < $today ) {
                        $return[] = [
                            'start'  => date( 'Y-m-d', $i ),
                            'date'   => date( 'Y-m-d', $i ),
                            'day'    => date( 'd', $i ),
                            'status' => 'past'
                        ];
                    } else {
                        if ( in_array( $d, $list_date_2 ) ) {
                            $return[] = [
                                'start'  => date( 'Y-m-d', $i ),
                                'date'   => date( 'Y-m-d', $i ),
                                'day'    => date( 'd', $i ),
                                'status' => 'disabled'
                            ];
                        } else {
                            if ( $period < $booking_period ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'disabled'
                                ];
                            } else if ( in_array( $d, $list_date_fist_half_day ) ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available_allow_fist',
                                    'price'  => (isset($data_price_room[$i]) ? TravelHelper::format_money($data_price_room[$i]) : 0)
                                ];
                            } else if ( in_array( $d, $list_date_last_half_day ) ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available_allow_last',
                                    'price'  => (isset($data_price_room[$i]) ? TravelHelper::format_money($data_price_room[$i]) : 0)
                                ];
                            } else {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available',
                                    'price'  => (isset($data_price_room[$i]) ? TravelHelper::format_money($data_price_room[$i]) : 0)
                                ];
                            }

                        }

                    }
                }
            }

            echo json_encode( $return );
            die;
        }

        static function ts_room_select_ajax() {
            extract( wp_parse_args( $_GET, [
                'room_parent' => '',
                'post_type'   => '',
                'q'           => ''
            ] ) );

            $query = [
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'meta_query'     => [
                    [
                        'key'     => 'room_parent',
                        'value'   => $room_parent,
                        'compare' => 'IN',
                    ],
                ],
            ];
            query_posts( $query );

            $r = [
                'items' => [],
            ];
            while ( have_posts() ) {
                the_post();
                $r[ 'items' ][] = [
                    'id'          => get_the_ID(),
                    'name'        => get_the_title(),
                    'description' => ''
                ];
            }

            wp_reset_query();

            echo json_encode( $r );
            die;

        }

        static function add_edit_scripts()
        {
//            wp_enqueue_script( 'ts-hotel-edit-booking', get_template_directory_uri() . '/js/admin/hotel-booking.js', [ 'jquery', 'jquery-ui-datepicker' ], NULL, TRUE );
//            wp_enqueue_style( 'jjquery-ui.theme.min.css', get_template_directory_uri() . '/css/admin/jquery-ui.min.css' );

        }

        static function is_booking_page() {
            if ( is_admin()
                and isset( $_GET[ 'post_type' ] )
                and $_GET[ 'post_type' ] == 'ts_hotel'
                and isset( $_GET[ 'page' ] )
                and $_GET[ 'page' ] = 'ts_hotel_booking'
            ) return TRUE;

            return FALSE;
        }

        function edit_order_item(){
            /*$item_id = isset( $_GET[ 'order_item_id' ] ) ? $_GET[ 'order_item_id' ] : FALSE;
            if ( !$item_id or get_post_type( $item_id ) != 'ts_order' ) {
                return FALSE;
            }

            echo balanceTags( $this->load_view( 'hotel/booking_edit' ) );*/
        }

        function _save_booking( $order_id ){
            if ( !check_admin_referer( 'shb_action', 'shb_field' ) ) die;
            if ( $this->_check_validate() ) {
                $item_data = [
                    'status' => $_POST[ 'status' ],
                ];
                foreach ( $item_data as $val => $value ) {
                    update_post_meta( $order_id, $val, $value );
                }
                /*$check_out_field = TSCart::get_checkout_fields();

                if ( !empty( $check_out_field ) ) {
                    foreach ( $check_out_field as $field_name => $field_desc ) {
                        if($field_name != 'st_note'){
                            update_post_meta( $order_id, $field_name, post( $field_name ) );
                        }
                    }
                }*/

                if ( TravelHelper::checkTableDuplicate( 'ts_hotel' ) ) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'ts_order_item_meta';
                    $where = [
                        'order_item_id' => $order_id
                    ];
                    $data  = [
                        'status' => $_POST[ 'status' ]
                    ];
                    $wpdb->update( $table, $data, $where );
                }

                do_action( 'update_booking_hotel', $order_id );

//                STCart::send_mail_after_booking( $order_id, true );

                do_action('ts_admin_edit_booking_status',$item_data['status'],$order_id);

                wp_safe_redirect( self::$booking_page );
            }
        }

        public function _check_validate()
        {

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

        function is_able_edit(){
            $item_id = isset( $_GET[ 'order_item_id' ] ) ? $_GET[ 'order_item_id' ] : FALSE;
            if ( !$item_id or get_post_type( $item_id ) != 'ts_order' ) {
                wp_safe_redirect( self::$booking_page );
                die;
            }

            return TRUE;
        }


        function add_col_header( $defaults ){
            $this->array_splice_assoc( $defaults, 2, 0, [ 'room_number' => __( 'Room(s)', 'trizen-helper' ) ] );
            $this->array_splice_assoc( $defaults, 2, 0, [ 'hotel_parent' => __( 'Hotel', 'trizen-helper' ) ] );
            return $defaults;
        }

        function add_hotel_col_header( $defaults ){
            $this->array_splice_assoc( $defaults, 2, 0, [ 'hotel_layout' => __( 'Layout', 'trizen-helper' ) ] );
            return $defaults;
        }

        function array_splice_assoc( &$input, $offset, $length = 0, $replacement = [] ){
            $tail      = array_splice( $input, $offset );
            $extracted = array_splice( $tail, 0, $length );
            $input += $replacement + $tail;
            return $extracted;
        }

        function add_col_content( $column_name, $post_ID ){
            if ( $column_name == 'hotel_parent' ) {
                // show content of 'directors_name' column
                $parent = get_post_meta( $post_ID, 'room_parent', TRUE );
                if ( $parent ) {
                    echo "<a href='" . get_edit_post_link( $parent ) . "'>" . get_the_title( $parent ) . "</a>";
                }
            }
            if ( $column_name == 'room_number' ) {
                echo get_post_meta( $post_ID, 'number_room', TRUE );
            }
        }

        function add_hotel_col_content( $column_name, $post_ID ) {
            if ( $column_name == 'hotel_layout' ) {
                // show content of 'directors_name' column
                $parent = get_post_meta( $post_ID, 'ts_custom_layout', TRUE );
                if ( $parent ) {
                    echo "<a href='" . get_edit_post_link( $parent ) . "'>" . get_the_title( $parent ) . "</a>";
                } else {
                    echo __( 'Default', 'trizen-helper' );
                }


            }
        }

        static function ts_get_custom_price_by_date( $post_id, $start_date = null, $price_type = 'default' ) {
            global $wpdb;
            if ( !$post_id )
                $post_id = get_the_ID();
            if ( empty( $start_date ) )
                $start_date = date( "Y-m-d" );
            $rs = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . "ts_price WHERE post_id=" . $post_id . " AND price_type='" . $price_type . "'  AND start_date <='" . $start_date . "' AND end_date >='" . $start_date . "' AND status=1 ORDER BY priority DESC LIMIT 1" );
            if ( !empty( $rs ) ) {
                return $rs[ 0 ]->price;
            } else {
                return false;
            }
        }

        static function getRoomHotelInfo() {
            $room_id = intval( request( 'room_id', '' ) );
            $data    = [
                'price'       => '',
                'extras'      => 'None',
                'adult_html'  => '',
                'child_html'  => '',
                'room_html'   => '',
                'adult_price' => '',
                'child_price' => '',
            ];
            if ( $room_id <= 0 || get_post_type( $room_id ) != 'hotel_room' ) {
                echo json_encode( $data );
            } else {
                $html         = '';
                $price        = floatval( get_post_meta( $room_id, 'price', true ) );
                $adult_number = intval( get_post_meta( $room_id, 'adult_number', true ) );
                $adult_price  = floatval( get_post_meta( $room_id, 'adult_price', true ) );
                $child_price  = floatval( get_post_meta( $room_id, 'child_price', true ) );
                if ( $adult_number <= 0 ) $adult_number = 1;
                $adult_html = '<select name="adult_number" class="form-control" style="width: 100%">';
                for ( $i = 1; $i <= $adult_number; $i++ ) {
                    $adult_html .= '<option value="' . $i . '">' . $i . '</option>';
                }
                $adult_html .= '</select>';

                $child_number = intval( get_post_meta( $room_id, 'children_number', true ) );
                if ( $child_number <= 0 ) $child_number = 0;
                $child_html = '<select name="child_number" class="form-control" style="width: 100%">';
                for ( $i = 0; $i <= $child_number; $i++ ) {
                    $child_html .= '<option value="' . $i . '">' . $i . '</option>';
                }
                $child_html .= '</select>';

                $room_number = intval( get_post_meta( $room_id, 'number_room', true ) );
                if ( $room_number <= 0 ) $room_number = 1;
                $room_html = '<select name="room_num_search" class="form-control" style="width: 100%">';
                for ( $i = 1; $i <= $room_number; $i++ ) {
                    $room_html .= '<option value="' . $i . '">' . $i . '</option>';
                }
                $room_html .= '</select>';

                $extras = get_post_meta( $room_id, 'trizen_room_other_facility_data_group', true );
                if ( is_array( $extras ) && count( $extras ) ):
                    $html = '<table class="table">';
                    foreach ( $extras as $key => $val ):
                        $html .= '
                    <tr>
                        <td width="80%">
                            <label for="' . $val[ 'trizen_hotel_room_extra_service_title' ] . '" class="ml20">' . $val[ 'trizen_hotel_room_extra_service_price_designation' ] . ' (' . TravelHelper::format_money( $val[ 'extra_price' ] ) . ')' . '</label>
                            <input type="hidden" name="extra_price[trizen_hotel_room_extra_service_price][' . $val[ 'trizen_hotel_room_extra_service_title' ] . ']" value="' . $val[ 'trizen_hotel_room_extra_service_price' ] . '">
                            <input type="hidden" name="extra_price[trizen_hotel_room_extra_service_price_designation][' . $val[ 'extra_name' ] . ']" value="' . $val[ 'trizen_hotel_room_extra_service_price_designation' ] . '">
                        </td>
                        <td width="20%">
                            <select style="width: 100%" class="form-control" name="extra_price[value][' . $val[ 'extra_name' ] . ']" id="">';

                        $max_item = intval( $val[ 'extra_max_number' ] );
                        if ( $max_item <= 0 ) $max_item = 1;
                        for ( $i = 0; $i <= $max_item; $i++ ):
                            $html .= '<option value="' . $i . '">' . $i . '</option>';
                        endfor;
                        $html .= '
                            </select>
                        </td>
                    </tr>';
                    endforeach;
                    $html .= '</table>';
                endif;
                $data[ 'price' ]      = TravelHelper::format_money_from_db( $price, false );
                $data[ 'extras' ]     = $html;
                $data[ 'adult_html' ] = $adult_html;
                $data[ 'child_html' ] = $child_html;
                $data[ 'room_html' ]  = $room_html;
                $data[ 'adult_price' ] = TravelHelper::format_money_from_db( $adult_price, false );
                $data[ 'child_price' ] = TravelHelper::format_money_from_db( $child_price, false );
                echo json_encode( $data );
            }
            die();
        }

        static function getRoomHotel() {
            $hotel_id = intval( request( 'hotel_id', '' ) );
            $room_id  = intval( request( 'room_id', '' ) );
            if ( $hotel_id <= 0 || get_post_type( $hotel_id ) != 'ts_hotel' ) {
                echo "";
                die();
            } else {
                $list_room = "<select name='room_id' id='room_id' class='form-control form-control-admin'>";
                $list_room .= "<option value=''>" . esc_html__( '----Select a room----', 'trizen-helper' ) . "</option>";
                $query = [
                    'post_status'    => 'publish',
                    'post_type'      => 'hotel_room',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'DESC',
                    'meta_query'     => [
                        [
                            'key'     => 'room_parent',
                            'value'   => $hotel_id,
                            'compare' => 'IN',
                        ],
                    ],
                ];

                query_posts( $query );
                while ( have_posts() ): the_post();
                    $selected = ( $room_id == intval( get_the_ID() ) ) ? 'selected' : '';
                    $list_room .= "<option " . $selected . " value='" . get_the_ID() . "'>" . get_the_title() . "</option>";
                endwhile;
                wp_reset_query();
                wp_reset_postdata();
                $list_room .= "</select>";

                echo balanceTags($list_room);
                die();
            }
        }

        public static function __cronjob_update_min_avg_price($offset, $limit = 2) {
            global $wpdb;
            $list_hotel = new WP_Query(array(
                'posts_per_page' => $limit,
                'post_type'      => 'ts_hotel',
                'offset'         => $offset
            ));

            $hotel_ids=[];
            if ($list_hotel->have_posts()) {
                while ($list_hotel->have_posts())
                {
                    $list_hotel->the_post();
                    $hotel_id = get_the_ID();
                    TSAdminHotel::_update_avg_price($hotel_id);
                    TSAdminHotel::_update_min_price($hotel_id);
                    TSAdminHotel::_update_duplicate_data($hotel_id, []);
                }
            }

            wp_reset_postdata();
        }
    }

    new TSAdminHotel();
}
