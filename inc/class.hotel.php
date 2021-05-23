<?php
/**
 * @package    WordPress
 * @subpackage Trizen
 * @since      1.0
 * Class TSHotel
 */

if ( !class_exists( 'TSHotel' ) ) {
    class TSHotel extends TravelObject {
        static $_inst;
        static $_instance;
        //Current Hotel ID
        private $hotel_id;
        protected $orderby;
        protected $post_type = 'ts_hotel';
        protected $template_folder = 'hotel';
        function __construct( $hotel_id = false ) {
            $this->hotel_id = $hotel_id;
            $this->orderby  = [
                'new'        => [
                    'key'  => 'new',
                    'name' => __( 'New', 'trizen-helper' )
                ],
                'price_asc'  => [
                    'key'  => 'price_asc',
                    'name' => __( 'Price ', 'trizen-helper' ) . ' (<i class="fa fa-long-arrow-up"></i>)'
                ],
                'price_desc' => [
                    'key'  => 'price_desc',
                    'name' => __( 'Price ', 'trizen-helper' ) . ' (<i class="fa fa-long-arrow-down"></i>)'
                ],
                'name_asc'   => [
                    'key'  => 'name_asc',
                    'name' => __( 'Name (A-Z)', 'trizen-helper' )
                ],
                'name_desc'  => [
                    'key'  => 'name_desc',
                    'name' => __( 'Name (Z-A)', 'trizen-helper' )
                ],

            ];

        }

        /**
         * @return array
         */
        public function getOrderby() {
            return $this->orderby;
        }

        function init() {
//            if ( !$this->is_available() ) return;

            parent::init();
            add_action( 'template_redirect', [ $this, 'ajax_search_room' ], 1 );
            //Filter the search hotel
            //custom search hotel template
            add_filter( 'template_include', [ $this, 'choose_search_template' ] );

            //Sidebar Pos for SEARCH
            add_filter( 'ts_hotel_sidebar', [ $this, 'change_sidebar' ] );

            //add Widget Area
//            add_action( 'widgets_init', [ $this, 'add_sidebar' ] );

            //Create Hotel Booking Link
//            add_action( 'wp', [ $this, 'hotel_add_to_cart' ], 20 );

            // Change hotel review arg
            add_filter( 'ts_hotel_wp_review_form_args', [ $this, 'comment_args' ], 10, 2 );

            //Save Hotel Review Stats
            add_action( 'comment_post', [ $this, 'save_review_stats' ] );

            //Reduce total stats of posts after comment_delete
            add_action( 'delete_comment', [ $this, 'save_post_review_stats' ] );

            //Filter change layout of hotel detail if choose in metabox
            add_filter( 'ts_hotel_detail_layout', [ $this, 'custom_hotel_layout' ] );

            add_action( 'wp_enqueue_scripts', [ $this, 'add_localize' ] );

            add_action( 'wp_ajax_ajax_search_room', [ $this, 'ajax_search_room' ] );
            add_action( 'wp_ajax_nopriv_ajax_search_room', [ $this, 'ajax_search_room' ] );

            add_filter( 'ts_real_comment_post_id', [ $this, '_change_comment_post_id' ] );
            add_filter( 'ts_search_preload_page', [ $this, '_change_preload_search_title' ] );
            add_filter( 'ts_checkout_form_validate', [ $this, '_check_booking_period' ] );
            add_filter( 'ts_ts_hotel_search_result_link', [ $this, '_change_search_result_link' ], 10, 2 );

            // Woocommerce cart item information
            add_action( 'ts_wc_cart_item_information_ts_hotel', [ $this, '_show_wc_cart_item_information' ] );
            add_action( 'ts_wc_cart_item_information_btn_ts_hotel', [ $this, '_show_wc_cart_item_information_btn' ] );

            add_action( 'ts_before_cart_item_ts_hotel', [ $this, '_show_wc_cart_post_type_icon' ] );

            add_action( 'wp_ajax_ts_fetch_inventory', [ $this, 'ts_fetch_inventory' ] );
            add_action( 'wp_ajax_add_price_inventory', [ $this, 'add_price_inventory_hotels' ] );

            //xsearch Load post hotel filter ajax
            add_action( 'wp_ajax_ts_filter_hotel_ajax', [ $this, 'ts_filter_hotel_ajax' ] );
            add_action( 'wp_ajax_nopriv_ts_filter_hotel_ajax', [ $this, 'ts_filter_hotel_ajax' ] );

            add_action( 'wp_ajax_ts_add_room_number_inventory', [ $this, 'ts_add_room_number_inventory' ] );
        }

        public function ts_add_room_number_inventory() {
            $room_id     = post( 'room_id', '' );
            $number_room = post( 'number_room', '' );

            $current_user = wp_get_current_user();
            $roles        = $current_user->roles;
            $role         = array_shift( $roles );

            if ( $role != 'administrator' && $role != 'partner' ) {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
                ];
                echo json_encode( $return );
                die;
            } else {
                if ( $role == 'partner' ) {
                    $current_user_id = $current_user->ID;
                    $post            = get_post( $room_id );
                    $authid          = $post->post_author;
                    if ( $current_user_id != $authid ) {
                        $return = [
                            'status'  => 0,
                            'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
                        ];
                        echo json_encode( $return );
                        die;
                    }
                }
            }

            if ( get_post_type( $room_id ) != 'hotel_room' ) {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
                ];
                echo json_encode( $return );
                die;
            }

            if ( $room_id < 0 || $room_id == '' || !is_numeric( $room_id ) ) {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__( 'Room is invalid!', 'trizen-helper' ),
                ];
                echo json_encode( $return );
                die;
            }

            if ( $number_room < 0 || $number_room == '' || !is_numeric( $number_room ) ) {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__( 'Number of room is invalid!', 'trizen-helper' ),
                ];
                echo json_encode( $return );
                die;
            }
            $res = update_post_meta( $room_id, 'number_room', $number_room );

            //Update number room in available table
            $update_number_room = TS_Hotel_Room_Availability::inst()
                ->where( 'post_id', $room_id )
                ->update( [ 'number' => $number_room ] );
            if ( $res && $update_number_room > 0 ) {
                $return = [
                    'status'  => 1,
                    'message' => esc_html__( 'Update success!', 'trizen-helper' ),
                ];
                echo json_encode( $return );
                die;
            } else {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
                ];
                echo json_encode( $return );
                die;
            }
        }

        public function ts_filter_hotel_ajax() {
            check_ajax_referer( 'ajax-search', '_wpnonce_search_ajax', true );
            $page_number = get( 'page' );
            $style       = get( 'layout' );
            $orderby     = get( 'orderby' );
            $jcategory   = get( 'jcategory' );
            global $wp_query, $ts_search_query;
            $hotel = $this;
            $hotel->alter_search_query();
            set_query_var( 'paged', $page_number );
            $paged = $page_number;
            $args = [
                'post_type'   => 'ts_hotel',
                's'           => '',
                'post_status' => [ 'publish' ],
                'paged'       => $paged
            ];

            query_posts( $args );

            $ts_search_query = $wp_query;
            global $wp_query;
            if ( $orderby == 'featured' ) {
                $ts_search_query->set( 'meta_key', 'is_featured' );
                $ts_search_query->set( 'orderby', 'meta_value' );
                $ts_search_query->set( 'order', 'DESC' );
            }
            $hotel->remove_alter_search_query();
            $current_page = get_query_var( 'paged' );
            $total_posts  = $wp_query->found_posts;
            if ( $total_posts == 0 && $current_page >= 2 ) {
                global $wp_rewrite;
                $link = add_query_arg();
                if ( $wp_rewrite->using_permalinks() ) {
                    $link = preg_replace( "/page\/(\d)\//", "page/1/", $link );
                } else {
                    $link = add_query_arg( 'paged', 1 );
                }
                wp_redirect( $link );
            }
            //echo $wp_query->request;
            //wp_reset_query();
            global $wp_query, $ts_search_query;

            if ( $ts_search_query ) {
                $query = $ts_search_query;
            } else $query = $wp_query;

            ob_start();
                ?>
                <div class="row row-wrap loop_hotel loop_grid_hotel style_box">
                    <?php
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        echo 'i am hotel grid';
                    }
                    ?>
                </div>
                <?php
            if ( !$query->found_posts ) {
                echo '<h3 class="ajax-filter-not-found">' . __( 'No hotel found', 'trizen-helper' ) . '</h3>';
            }
            //echo st()->load_template('hotel/loop',false,array('style'=>$st_style));
            $ajax_filter_content = ob_get_contents();
            ob_clean();
            ob_end_flush();

            //Pagination
            ob_start();
            ?>
            <p>
                <small>
                    <?php
                    set_query_var( 'paged', $page_number );
                    if ( is_rtl() || st()->get_option( 'right_to_left' ) == 'on' ):
                        ?>
                        <?php
                        if ( !empty( $ts_search_query ) ) {
                            $wp_query = $ts_search_query;
                        }
                        if ( $wp_query->found_posts ):
                            esc_html_e( 'Showing', 'traveler' );
                            $page           = get_query_var( 'paged' );
                            $posts_per_page = get_query_var( 'posts_per_page' );
                            if ( !$page ) $page = 1;
                            $last = $posts_per_page * ( $page );
                            if ( $last > $wp_query->found_posts ) $last = $wp_query->found_posts;
                            echo ' ' . ( $posts_per_page * ( $page - 1 ) + 1 ) . ' - ' . $last;
                        endif;
                        ?>
                        .&nbsp;&nbsp;<?php echo balanceTags( $hotel->get_result_string() ) ?>

                    <?php else: ?>
                        <?php echo balanceTags( $hotel->get_result_string() ) ?>. &nbsp;&nbsp;
                        <?php
                        if ( !empty( $ts_search_query ) ) {
                            $wp_query = $ts_search_query;
                        }
                        if ( $wp_query->found_posts ):
                            esc_html_e( 'Showing', 'trizen-helper' );
                            $page           = get_query_var( 'paged' );
                            $posts_per_page = get_query_var( 'posts_per_page' );
                            if ( !$page ) $page = 1;
                            $last = $posts_per_page * ( $page );
                            if ( $last > $wp_query->found_posts ) $last = $wp_query->found_posts;
                            echo ' ' . ( $posts_per_page * ( $page - 1 ) + 1 ) . ' - ' . esc_attr($last);
                        endif;
                        ?>
                    <?php endif; ?>
                </small>
            </p>
            <div class="row">
                <?php
                TravelHelper::paging(); ?>
            </div>
            <?php
            $ajax_filter_pag = ob_get_contents();
            ob_clean();
            ob_end_flush();
            $count  = balanceTags( $hotel->get_result_string() );
            $result = [
                'content' => $ajax_filter_content,
                'pag'     => $ajax_filter_pag,
                'count'   => $count,
                'page'    => $page_number
            ];
            echo json_encode( $result );
            die;
        }

        public function ts_fetch_inventory() {
            $post_id = post( 'post_id', '' );
            if ( get_post_type( $post_id ) == 'ts_hotel' ) {
                $start = strtotime( post( 'start', '' ) );
                $end   = strtotime( post( 'end', '' ) );
                if ( $start > 0 && $end > 0 ) {
                    $args = [
                        'post_type'      => 'hotel_room',
                        'posts_per_page' => -1,
                        'meta_query'     => [
                            [
                                'key'     => 'room_parent',
                                'value'   => $post_id,
                                'compare' => '='
                            ]
                        ]
                    ];
                    if ( ! current_user_can('administrator') ) {
                        $args['author'] = get_current_user_id();
                    }
                    $rooms = [];
                    $query = new WP_Query( $args );
                    while ( $query->have_posts() ): $query->the_post();
                        $rooms[] = [
                            'id'   => get_the_ID(),
                            'name' => get_the_title()
                        ];
                    endwhile;
                    wp_reset_postdata();
                    $datarooms = [];
                    if ( !empty( $rooms ) ) {
                        foreach ( $rooms as $key => $value ) {
                            $datarooms[] = $this->featch_dataroom( $post_id, $value[ 'id' ], $value[ 'name' ], $start, $end );
                        }
                    }
                    echo json_encode( [
                        'status' => 1,
                        'rooms'  => $datarooms
                    ] );
                    die;
                }
            }
            echo json_encode( [
                'status'  => 0,
                'message' => __( 'Can not fetch data', 'trizen-helper' ),
                'rooms'   => ''
            ] );
            die;
        }

        function st_origin_id( $post_id, $service_type = 'post' ) {
            if ( function_exists( 'wpml_object_id_filter' ) ) {
                global $sitepress;
                $a = wpml_object_id_filter( $post_id, $service_type, true, $sitepress->get_default_language() );
                return $a;
            } else {
                return $post_id;
            }
        }

        public function add_price_inventory_hotels(){
            $post_id = (int)post( 'post_id' );
            $price   = post( 'price' );
            $status  = post( 'status', 'available' );
            $start   = (float)post( 'start' );
            $end     = (float)post( 'end' );
            $start   /= 1000;
            $end     /= 1000;
            $adult_price = post( 'adult_price' );
            $child_price = post( 'child_price' );
            $price_by_per_person = get_post_meta( $post_id, 'price_by_per_person', true );


            $start = strtotime( date( 'Y-m-d', $start ) );
            $end   = strtotime( date( 'Y-m-d', $end ) );

            if ( get_post_type( $post_id ) != 'hotel_room' ) {
                echo json_encode( [
                    'status'  => 0,
                    'message' => esc_html__( 'Can not set price for this room', 'trizen-helper' )
                ] );
                die;
            }
            if ( $price_by_per_person == 'on' ) {
                if ( ( $status == 'available' )
                    && ( $adult_price == '' && $child_price == '' ) && ( ( $adult_price == '' || !is_numeric( $adult_price ) || (float)$adult_price < 0 )
                        || ( $child_price == '' || !is_numeric( $child_price ) || (float)$child_price < 0 ) ) ) {
                    echo json_encode( [
                        'status'  => 0,
                        'message' => esc_html__( 'Price is incorrect', 'trizen-helper' )
                    ] );
                    die;
                }
            } else {
                if ( ( $status == 'available' ) && ( $price == '' || !is_numeric( $price ) || (float)$price < 0 ) ) {
                    echo json_encode( [
                        'status'  => 0,
                        'message' => esc_html__( 'Price is incorrect', 'trizen-helper' )
                    ] );
                    die;
                }
            }
            $price = (float)$price;
            $adult_price = floatval( $adult_price );
            $child_price = floatval( $child_price );

            $base_id = (int)ts_origin_id( $post_id, 'hotel_room' );


            $new_item = $this->inventory_save_data( $post_id, $base_id, $start, $end, $price, $status, $adult_price, $child_price );

            if ( $new_item > 0 ) {
                echo json_encode( [
                    'status'  => 1,
                    'message' => esc_html__( 'Successffully added', 'trizen-helper' )
                ] );
                die;
            } else {
                echo json_encode( [
                    'status'  => 0,
                    'message' => esc_html__( 'Getting an error when adding new item.', 'trizen-helper' )
                ] );
                die;
            }
        }

        public function inventory_save_data( $post_id, $base_id, $check_in, $check_out, $price, $status, $adult_price = '', $child_price = '' ){
            global $wpdb;
            $result = get_availability( $base_id, $check_in, $check_out );

            $number         = get_post_meta( $base_id, 'number_room', true );
            $parent_id      = get_post_meta( $base_id, 'room_parent', true );
            $booking_period = get_post_meta( $parent_id, 'hotel_booking_period', true );
            $allow_full_day = get_post_meta( $base_id, 'allow_full_day', true );
            $adult_number   = get_post_meta( $base_id, 'adult_number', true );
            $child_number   = get_post_meta( $base_id, 'children_number', true );

            $string_insert      = '';
            $check_total_update = 0;
            if ( !empty( $result ) ) {
                if ( !empty( $check_in ) && !empty( $check_out ) ) {
                    $arr_to_insert = [];
                    for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
                        $check_available = TS_Hotel_Room_Availability::inst()
                            ->where( 'post_id', $base_id )
                            ->where( 'check_in', $i )
                            ->get()->result();
                        if ( !empty( $check_available ) ) {
                            $check_update       = TS_Hotel_Room_Availability::inst()
                                ->where( 'post_id', $base_id )
                                ->where( 'check_in', $i )
                                ->update( [
                                    'price'          => $price,
                                    'post_type'      => 'hotel_room',
                                    'number'         => $number,
                                    'parent_id'      => $parent_id,
                                    'allow_full_day' => $allow_full_day,
                                    'booking_period' => $booking_period,
                                    'adult_number'   => $adult_number,
                                    'child_number'   => $child_number,
                                    'status'         => $status,
                                    'adult_price'    => $adult_price,
                                    'child_price'    => $child_price,
                                ] );
                            $check_total_update += $check_update;
                        } else {
                            array_push( $arr_to_insert, $i );
                        }
                    }
                    if ( !empty( $arr_to_insert ) ) {
                        foreach ( $arr_to_insert as $kk => $vv ) {
                            $string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $booking_period, $allow_full_day, $adult_number, $child_number, $base_id, $vv, $vv, $price, 'available', $adult_price, $child_price );
                        }
                    }
                }
            } else {
                for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
                    $string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $booking_period, $allow_full_day, $adult_number, $child_number, $base_id, $i, $i, $price, 'available', $adult_price, $child_price );
                }
            }

            if ( !empty( $string_insert ) || $check_total_update > 0 ) {
                if ( !empty( $string_insert ) ) {
                    $string_insert = substr( $string_insert, 0, -1 );
                    $sql           = "INSERT INTO {$wpdb->prefix}ts_room_availability (id, post_type, is_base, `number`, parent_id, booking_period, allow_full_day, adult_number, child_number, post_id,check_in,check_out,price, status, adult_price, child_price ) VALUES {$string_insert}";
                    $result        = $wpdb->query( $sql );

                    return $result;
                } else {
                    return $check_total_update;
                }
            } else {
                return 0;
            }
        }

        /*public function get_availability( $base_id = '', $check_in = '', $check_out = '' ){
            global $wpdb;
            $table = $wpdb->prefix . 'ts_room_availability';
            $sql = "SELECT * FROM {$table} WHERE post_id = {$base_id} AND ( ( CAST( `check_in` AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED) AND CAST( `check_in` AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) OR ( CAST( `check_out` AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED ) AND ( CAST( `check_out` AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) ) ) ";
            $result = $wpdb->get_results( $sql, ARRAY_A );
            $return = [];
            if ( !empty( $result ) ) {
                foreach ( $result as $item ) {
                    $item_array = [
                        'id'         => $item[ 'id' ],
                        'post_id'    => $item[ 'post_id' ],
                        'start'      => date( 'Y-m-d', $item[ 'check_in' ] ),
                        'end'        => date( 'Y-m-d', strtotime( '+1 day', $item[ 'check_out' ] ) ),
                        'price'      => (float)$item[ 'price' ],
                        'price_text' => format_money( $item[ 'price' ] ),
                        'status'     => $item[ 'status' ],
                        'adult_price' => floatval( $item['adult_price'] ),
                        'child_price' => floatval( $item['child_price'] ),
                    ];
                    $return[] = $item_array;
                }
            }
            return $return;
        }*/

        public function featch_dataroom( $hotel_id, $post_id, $post_name, $start, $end ) {
            $number_room   = (int)get_post_meta( $post_id, 'number_room', true );
            $allow_fullday = get_post_meta( $hotel_id, 'allow_full_day', true );
            $base_price    = (float)get_post_meta( $post_id, 'price', true );
            $adult_price   = floatval( get_post_meta( $post_id, 'adult_price', true ) );
            $child_price   = floatval( get_post_meta( $post_id, 'child_price', true ) );
            $price_by_per_person = (get_post_meta( $post_id, 'price_by_per_person', true) == 'on') ? true : false;
            $post_id_calendar = post_origin($post_id);
            global $wpdb;
            $sql = "SELECT
                    *
                FROM
                    {$wpdb->prefix}ts_room_availability AS avai
                WHERE
                    (
                        (
                            avai.check_in <= {$start}
                            AND avai.check_out >= {$start}
                        )
                        OR (
                            avai.check_in <= {$end}
                            AND avai.check_out >= {$end}
                        )
                        OR (
                            avai.check_in <= {$start}
                            AND avai.check_out >= {$end}
                        )
                        OR (
                            avai.check_in >= {$start}
                            AND avai.check_out <= {$end}
                        )
                    )
                and avai.post_id = {$post_id_calendar}";

            $avai_rs = $wpdb->get_results( $sql );

            $column = 'ts_booking_id';
            if ( get_post_type( $post_id ) == 'hotel_room' ) {
                $column = 'room_id';
            }
            $sql      = "SELECT
                    *
                FROM
                    {$wpdb->prefix}ts_order_item_meta AS _order
                WHERE
                    (
                        (
                            _order.check_in_timestamp <= {$start}
                            AND _order.check_out_timestamp >= {$start}
                        )
                        OR (
                            _order.check_in_timestamp <= {$end}
                            AND _order.check_out_timestamp >= {$end}
                        )
                        OR (
                            _order.check_in_timestamp <= {$start}
                            AND _order.check_out_timestamp >= {$end}
                        )
                        OR (
                            _order.check_in_timestamp >= {$start}
                            AND _order.check_out_timestamp <= {$end}
                        )
                    )
                AND _order.{$column} = {$post_id} AND _order.`status` NOT IN ('cancelled', 'wc-cancelled')";
            $order_rs = $wpdb->get_results( $sql );
            $return   = [
                'name'   => esc_html( $post_name ),
                'values' => [],
                'id'     => $post_id,
                'price_by_per_person' => $price_by_per_person
            ];
            for ( $i = $start; $i <= $end; $i = strtotime( '+1 day', $i ) ) {
                $date      = $i * 1000;
                $available = true;
                $price     = $base_price;
                if ( !empty( $avai_rs ) ) {
                    foreach ( $avai_rs as $key => $value ) {
                        if ( $i >= $value->check_in && $i <= $value->check_out ) {
                            if ( $value->status == 'available' ) {
                                if ( $price_by_per_person ) {
                                    $adult_price = floatval( $value->adult_price );
                                    $child_price = floatval( $value->child_price );
                                } else {
                                    $price = (float)$value->price;
                                }
                            } else {
                                $available = false;
                            }
                            break;
                        }
                    }
                }
                if ( $available ) {
                    $ordered = 0;
                    if ( !empty( $order_rs ) ) {
                        foreach ( $order_rs as $key => $value ) {
                            if ( $allow_fullday == 'on' ) {
                                if ( $i >= $value->check_in_timestamp && $i <= $value->check_out_timestamp ) {
                                    $ordered += (int)$value->room_num_search;
                                }
                            } else {
                                if ( $i >= $value->check_in_timestamp && $i == strtotime( '-1 day', $value->check_out_timestamp ) ) {
                                    $ordered += (int)$value->room_num_search;
                                }
                            }

                        }
                    }
                    if ( $number_room - $ordered > 0 ) {
                        $return[ 'values' ][] = [
                            'from'        => "/Date({$date})/",
                            'to'          => "/Date({$date})/",
                            'label'       => $number_room - $ordered,
                            'desc'        => sprintf( __( '%s left', 'trizen-helper' ), $number_room - $ordered ),
                            'customClass' => 'ganttBlue',
                            'price'       => format_money( $price, [ 'simple_html' => true ] ),
                            'adult_price' => format_money( $adult_price, [ 'simple_html' => true ] ),
                            'child_price' => format_money( $child_price, [ 'simple_html' => true ] ),
                            'price_by_per_person' => $price_by_per_person
                        ];
                    } else {
                        $return[ 'values' ][] = [
                            'from'        => "/Date({$date})/",
                            'to'          => "/Date({$date})/",
                            'label'       => __( 'O', 'trizen-helper' ),
                            'desc'        => __( 'Out of stock', 'trizen-helper' ),
                            'customClass' => 'ganttOrange',
                            'price'       => format_money( $price, [ 'simple_html' => true ] ),
                            'adult_price' => format_money( $adult_price, [ 'simple_html' => true ] ),
                            'child_price' => format_money( $child_price, [ 'simple_html' => true ] ),
                            'price_by_per_person' => $price_by_per_person
                        ];
                    }
                } else {
                    $return[ 'values' ][] = [
                        'from'        => "/Date({$date})/",
                        'to'          => "/Date({$date})/",
                        'label'       => __( 'N', 'trizen-helper' ),
                        'desc'        => __( 'Not Available', 'trizen-helper' ),
                        'customClass' => 'ganttRed',
                        'price'       => format_money( $price, [ 'simple_html' => true ] ),
                        'adult_price' => format_money( $adult_price, [ 'simple_html' => true ] ),
                        'child_price' => format_money( $child_price, [ 'simple_html' => true ] ),
                        'price_by_per_person' => $price_by_per_person
                    ];
                }
            }

            return $return;

        }

        /**
         * @since 1.0
         * */
        function _deposit_calculator( $cart_data, $item_id ) {
            $room_id = isset( $cart_data[ 'data' ][ 'room_id' ] ) ? $cart_data[ 'data' ][ 'room_id' ] : false;
            if ( $room_id ) {
                $cart_data = parent::_deposit_calculator( $cart_data, $room_id );
            }
            return $cart_data;
        }

        /**
         * @since 1.0
         * */
        function _show_wc_cart_post_type_icon() {
            echo '<span class="booking-item-wishlist-title"><i class="fa fa-building-o"></i> ' . __( 'hotel', 'trizen-helper' ) . ' <span></span></span>';
        }

        /**
         * Show cart item information for hotel booking
         * @since 1.0
         * */

        function _show_wc_cart_item_information( $st_booking_data = [] ) {
//            echo st()->load_template( 'hotel/wc_cart_item_information', false, [ 'ts_booking_data' => $st_booking_data ] );
        }

        function _add_room_number_field( $post_type = false ){
            /*if ( $post_type == 'hotel_room' ) {
                echo st()->load_template( 'hotel/checkout_fields', null, [ 'key' => get_the_ID() ] );

                return;
            }*/
        }

        function _is_hotel_booking() {
            $items = get_items();
            if ( !empty( $items ) ) {
                foreach ( $items as $key => $value ) {
                    if ( get_post_type( $key ) == 'ts_hotel' ) return true;
                }
            }
        }


        /**
         * @since 1.0
         * */
        function _check_booking_period( $validate ) {
            $cart     = get_items();
            $hotel_id = '';
            $today    = strtotime( date( 'm/d/Y' ) );
            $check_in = $today;
            foreach ( $cart as $key => $val ) {
                $hotel_id = $key;
                $check_in = strtotime( $val[ 'data' ][ 'check_in' ] );
            }
            $booking_period = intval( get_post_meta( $hotel_id, 'hotel_booking_period', true ) );
            $period = TravelHelper::date_diff( $today, $check_in );
            if ( $booking_period && $period < $booking_period ) {
                set_message( sprintf( __( 'This hotel allow minimum booking is %d day(s)', 'trizen-helper' ), $booking_period ), 'danger' );
                $validate = false;
            }
            return $validate;
        }

        function _add_validate_fields( $validate ) {
            $items = get_items();

            if ( !empty( $items ) ) {
                foreach ( $items as $key => $value ) {
                    if ( get_post_type( $key ) == 'ts_hotel' ) {

                        // validate

                        $default = [
                            'number' => 1
                        ];
                        $value = wp_parse_args( $value, $default );
                        $room_num = $value[ 'number' ];
                        $room_data = request( 'room_data', [] );
                        if ( $room_num > 1 ) {
                            if ( !is_array( $room_data ) or empty( $room_data ) ) {
                                set_message( __( 'Room infomation is required', 'trizen-helper' ), 'danger' );
                                $validate = false;
                            } else {
                                for ( $k = 1; $k <= $room_num; $k++ ) {
                                    $valid = true;
                                    if ( !isset( $room_data[ $k ][ 'adult_number' ] ) or !$room_data[ $k ][ 'adult_number' ] ) {
                                        set_message( __( 'Adult number in room is required!', 'trizen-helper' ), 'danger' );
                                        $valid = false;
                                    }
                                    if ( !isset( $room_data[ $k ][ 'host_name' ] ) or !$room_data[ $k ][ 'host_name' ] ) {
                                        set_message( __( 'Room Host Name is required!', 'trizen-helper' ), 'danger' );
                                        $valid = false;
                                    }
                                    if ( isset( $room_data[ $k ][ 'child_number' ] ) ) {
                                        $child_number = (int)$room_data[ $k ][ 'child_number' ];
                                        if ( $child_number > 0 ) {
                                            if ( !isset( $room_data[ $k ][ 'age_of_children' ] ) or !is_array( $room_data[ $k ][ 'age_of_children' ] ) or empty( $room_data[ $k ][ 'age_of_children' ] ) ) {
                                                set_message( __( 'Ages of Children is required!', 'trizen-helper' ), 'danger' );
                                                $valid = false;
                                            } else {
                                                foreach ( $room_data[ $k ][ 'age_of_children' ] as $k2 => $v2 ) {
                                                    if ( !$v2 ) {
                                                        set_message( __( 'Ages of Children is required!', 'trizen-helper' ), 'danger' );
                                                        $valid = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if ( !$valid ) {
                                        $validate = false;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $validate;
        }

        function _change_preload_search_title( $return ) {
            if ( get_query_var( 'post_type' ) == 'ts_hotel' || is_page_template( 'template-hotel-search.php' ) ) {
                $return = __( " Hotels in %s", 'trizen-helper' );
                if ( get( 'location_id' ) ) {
                    $return = sprintf( $return, get_the_title( get( 'location_id' ) ) );
                } elseif ( get( 'location_name' ) ) {
                    $return = sprintf( $return, get( 'location_name' ) );
                } elseif ( get( 'address' ) ) {
                    $return = sprintf( $return, get( 'address' ) );
                } else {
                    $return = __( " Hotels", 'trizen-helper' );
                }
                $return .= '...';
            }
            return $return;
        }

        function _change_comment_post_id( $id_item ) {
            return $id_item;
        }

        function add_localize() {
            wp_localize_script( 'jquery', 'ts_hotel_localize', [
                'booking_required_adult'          => __( 'Please select adult number', 'trizen-helper' ),
                'booking_required_children'       => __( 'Please select children number', 'trizen-helper' ),
                'booking_required_adult_children' => __( 'Please select Adult and  Children number', 'trizen-helper' ),
                'room'                            => __( 'Room', 'trizen-helper' ),
                'is_aoc_fail'                     => __( 'Please select the ages of children', 'trizen-helper' ),
                'is_not_select_date'              => __( 'Please select Check-in and Check-out date', 'trizen-helper' ),
                'is_not_select_check_in_date'     => __( 'Please select Check-in date', 'trizen-helper' ),
                'is_not_select_check_out_date'    => __( 'Please select Check-out date', 'trizen-helper' ),
                'is_host_name_fail'               => __( 'Please provide Host Name(s)', 'trizen-helper' )
            ] );
        }

        static function get_search_fields_name() {
            return [
                'location'      => [
                    'value' => 'location',
                    'label' => __( 'Location', 'trizen-helper' )
                ],
                'list_location' => [
                    'value' => 'list_location',
                    'label' => __( 'Location List', 'trizen-helper' )
                ],
                'checkin'       => [
                    'value' => 'checkin',
                    'label' => __( 'Check in', 'trizen-helper' )
                ],
                'checkout'      => [
                    'value' => 'checkout',
                    'label' => __( 'Check out', 'trizen-helper' )
                ],
                'adult'         => [
                    'value' => 'adult',
                    'label' => __( 'Adult', 'trizen-helper' )
                ],
                'children'      => [
                    'value' => 'children',
                    'label' => __( 'Children', 'trizen-helper' )
                ],
                'taxonomy'      => [
                    'value' => 'taxonomy',
                    'label' => __( 'Taxonomy', 'trizen-helper' )
                ],
                'price_slider'  => [
                    'value' => 'price_slider',
                    'label' => __( 'Price slider', 'trizen-helper' )
                ],
                'room_num'      => [
                    'value' => 'room_num',
                    'label' => __( 'Room(s)', 'trizen-helper' )
                ],
                'taxonomy_room' => [
                    'value' => 'taxonomy_room',
                    'label' => __( 'Taxonomy Room', 'trizen-helper' )
                ],
            ];
        }

        function count_offers( $post_id = false ) {
            if ( !$post_id ) $post_id = $this->hotel_id;
            //Count Rooms
            global $wpdb;
            $query_count = $wpdb->get_results( "
                select DISTINCT ID from {$wpdb->posts}
                join {$wpdb->postmeta}
                on {$wpdb->postmeta} .post_id = {$wpdb->posts}.ID
                and {$wpdb->postmeta} .meta_key = 'room_parent' and {$wpdb->postmeta} .meta_value =  {$post_id}
                and {$wpdb->posts}.post_status = 'publish'
            " );
            return ( count( $query_count ) );
        }

        function get_search_fields() {
            /*$fields = st()->get_option( 'hotel_search_fields' );

            return $fields;*/
        }

        function get_search_adv_fields() {
            /*$fields = st()->get_option( 'hotel_search_advance' );
            return $fields;*/
        }

        function custom_hotel_layout( $old_layout_id ) {
            if ( is_singular( $this->post_type ) ) {
                $meta = get_post_meta( get_the_ID(), 'ts_custom_layout', true );
                if ( $meta and get_post_type( $meta ) == 'ts_layouts' ) {
                    return $meta;
                }
            }
            return $old_layout_id;
        }

        function save_review_stats( $comment_id ) {
            $comemntObj = get_comment( $comment_id );
            $post_id    = $comemntObj->comment_post_ID;

            if ( get_post_type( $post_id ) == 'ts_hotel' ) {
                $all_stats       = $this->get_review_stats();
                $st_review_stats = post( 'ts_review_stats' );

                if ( !empty( $all_stats ) && is_array( $all_stats ) ) {
                    $total_point = 0;
                    foreach ( $all_stats as $key => $value ) {
                        if ( isset( $st_review_stats[ $value[ 'title' ] ] ) ) {
                            $total_point += $st_review_stats[ $value[ 'title' ] ];
                            //Now Update the Each Stat Value
                            update_comment_meta( $comment_id, 'ts_stat_' . sanitize_title( $value[ 'title' ] ), $st_review_stats[ $value[ 'title' ] ] );
                        }
                    }

                    $avg = round( $total_point / count( $all_stats ), 1 );

                    //Update comment rate with avg point
                    $rate = wp_filter_nohtml_kses( $avg );
                    if ( $rate > 5 ) {
                        //Max rate is 5
                        $rate = 5;
                    }
                    update_comment_meta( $comment_id, 'comment_rate', $rate );
                    //Now Update the Stats Value
                    update_comment_meta( $comment_id, 'ts_review_stats', $st_review_stats );
                }
            }

            if ( post( 'comment_rate' ) ) {
                update_comment_meta( $comment_id, 'comment_rate', post( 'comment_rate' ) );

            }
            //review_stats
            $avg = TSReview::get_avg_rate( $post_id );
            update_post_meta( $post_id, 'rate_review', $avg );
        }

        function save_post_review_stats( $comment_id ) {
            $comemntObj = get_comment( $comment_id );
            $post_id    = $comemntObj->comment_post_ID;
            $avg = TSReview::get_avg_rate( $post_id );
            update_post_meta( $post_id, 'rate_review', $avg );
        }


        function get_review_stats() {
            /*$review_stat = st()->get_option( 'hotel_review_stats' );

            return $review_stat;*/
        }

        function get_review_stats_metabox() {
            /*$review_stat = st()->get_option( 'hotel_review_stats' );

            $result = [];

            if ( !empty( $review_stat ) ) {
                foreach ( $review_stat as $key => $value ) {
                    $result[] = [
                        'label' => $value[ 'title' ],
                        'value' => sanitize_title( $value[ 'title' ] )
                    ];
                }

            }

            return $result;*/
        }

        function comment_args( $comment_form, $post_id = false ) {
            if ( !$post_id ) $post_id = get_the_ID();
            if ( get_post_type( $post_id ) == 'ts_hotel' ) {
                $stats = $this->get_review_stats();

                if ( $stats and is_array( $stats ) ) {
                    $stat_html = '<ul class="list booking-item-raiting-summary-list stats-list-select">';

                    foreach ( $stats as $key => $value ) {
                        $stat_html .= '<li class=""><div class="booking-item-raiting-list-title">' . esc_html($value[ 'title' ]) . '</div>
                                <ul class="icon-group booking-item-rating-stars">
                                <li class=""><i class="fa fa-smile-o"></i>
                                </li>
                                <li class=""><i class="fa fa-smile-o"></i>
                                </li>
                                <li class=""><i class="fa fa-smile-o"></i>
                                </li>
                                <li class=""><i class="fa fa-smile-o"></i>
                                </li>
                                <li><i class="fa fa-smile-o"></i>
                                </li>
                            </ul>
                            <input type="hidden" class="st_review_stats" value="0" name="ts_review_stats[' . esc_attr($value[ 'title' ]) . ']">
                                </li>';
                    }
                    $stat_html .= '</ul>';
                    $comment_form[ 'comment_field' ] = "
                        <div class='row'>
                            <div class=\"col-sm-8\">
                    ";
                    $comment_form[ 'comment_field' ] .= '<div class="form-group">
                                            <label>' . __( 'Review Title', 'trizen-helper' ) . '</label>
                                            <input class="form-control" type="text" name="comment_title">
                                        </div>';

                    $comment_form[ 'comment_field' ] .= '<div class="form-group">
                                            <label>' . __( 'Review Text', 'trizen-helper' ) . '</label>
                                            <textarea name="comment" id="comment" class="form-control" rows="6"></textarea>
                                        </div>
                                        </div><!--End col-sm-8-->
                                        ';

                    $comment_form[ 'comment_field' ] .= '<div class="col-sm-4">' . $stat_html . '</div></div><!--End Row-->';
                }
            }
            return $comment_form;
        }

        /*function hotel_add_to_cart()
        {
            if ( request( 'action' ) == 'hotel_add_to_cart' ) {

                if ( $this->do_add_to_cart() ) {
                    $link = STCart::get_cart_link();
                    wp_safe_redirect( $link );
                    die;
                }
            }

        }*/

//        function do_add_to_cart()
//        {
//            $pass_validate = true;
//
//            $item_id = intval( request( 'item_id', '' ) );
//            if ( $item_id <= 0 ) {
//                STTemplate::set_message( __( 'This hotel is not available.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//
//            $room_id = intval( STInput::request( 'room_id', '' ) );
//            if ( $room_id <= 0 || get_post_type( $room_id ) != 'hotel_room' ) {
//                STTemplate::set_message( __( 'This room is not available.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            $room_origin = TravelHelper::post_origin( $room_id, 'hotel_room' );
//
//            $check_in = STInput::request( 'check_in', '' );
//
//            if ( empty( $check_in ) ) {
//                STTemplate::set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            $check_in = TravelHelper::convertDateFormat( $check_in );
//
//            $check_out = STInput::request( 'check_out', '' );
//            if ( empty( $check_out ) ) {
//                STTemplate::set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            $check_out       = TravelHelper::convertDateFormat( $check_out );
//            $room_num_search = intval( STInput::request( 'room_num_search', '' ) );
//            if ( empty( $room_num_search ) )
//                $room_num_search = intval( STInput::request( 'number_room', '' ) );
//            if ( $room_num_search <= 0 ) $room_num_search = 1;
//
//            $adult_number = intval( STInput::request( 'adult_number', '' ) );
//            if ( $adult_number <= 0 ) $adult_number = 1;
//
//            $child_number = intval( STInput::request( 'child_number', '' ) );
//            if ( $child_number <= 0 ) $child_number = 0;
//
//            $checkin_ymd  = date( 'Y-m-d', strtotime( $check_in ) );
//            $checkout_ymd = date( 'Y-m-d', strtotime( $check_out ) );
//
//            if ( !HotelHelper::check_day_cant_order( $room_origin, $checkin_ymd, $checkout_ymd, $room_num_search, $adult_number, $child_number ) ) {
//                STTemplate::set_message( sprintf( __( 'This room is not available from %s to %s.', 'trizen-helper' ), $checkin_ymd, $checkout_ymd ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            if ( !HotelHelper::_check_room_only_available( $room_origin, $checkin_ymd, $checkout_ymd, $room_num_search ) ) {
//                STTemplate::set_message( __( 'This room is not available.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            /*if (get_post_type($item_id) == 'st_hotel') {
//            if (!HotelHelper::_check_room_available($room_origin, $checkin_ymd, $checkout_ymd, $room_num_search)) {
//                STTemplate::set_message(__('This room is not available.', 'trizen-helper'), 'danger');
//                $pass_validate = FALSE;
//
//                return FALSE;
//            }
//        } else {
//            if (!HotelHelper::_check_room_only_available($room_origin, $checkin_ymd, $checkout_ymd, $room_num_search)) {
//                STTemplate::set_message(__('This room is not available.', 'trizen-helper'), 'danger');
//                $pass_validate = FALSE;
//
//                return FALSE;
//            }
//        }*/
//
//            if ( strtotime( $check_out ) - strtotime( $check_in ) <= 0 ) {
//                STTemplate::set_message( __( 'The check-out is later than the check-in.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            $num_room = intval( get_post_meta( $room_origin, 'number_room', true ) );
//            $adult    = intval( get_post_meta( $room_origin, 'adult_number', true ) );
//            if ( $adult == 0 ) {
//                $adult = 1;
//            }
//            $children = intval( get_post_meta( $room_origin, 'children_number', true ) );
//
//            if ( $room_num_search > $num_room ) {
//                STTemplate::set_message( __( 'Max of rooms are incorrect.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            if ( $adult_number > $adult ) {
//                STTemplate::set_message( sprintf( __( 'Max of adults is %d people.', 'trizen-helper' ), $adult ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            if ( $child_number > $children ) {
//                STTemplate::set_message( __( 'Number of children in the room are incorrect.', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            $today = date( 'm/d/Y' );
//
//            $period = STDate::dateDiff( $today, $check_in );
//
//            $booking_min_day = intval( get_post_meta( $item_id, 'min_book_room', true ) );
//            $compare         = TravelHelper::dateCompare( $today, $check_in );
//
//            $booking_period = get_post_meta( $item_id, 'hotel_booking_period', true );
//            if ( empty( $booking_period ) || $booking_period <= 0 ) $booking_period = 0;
//
//            if ( $compare < 0 ) {
//                STTemplate::set_message( __( 'You can not set check-in date in the past', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//            if ( $period < $booking_period ) {
//                STTemplate::set_message( sprintf( _n( 'This hotel allow minimum booking is %d day', 'This hotel allow minimum booking is %d day(s)', $booking_period, 'trizen-helper' ), $booking_period ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            if ( $booking_min_day and $booking_min_day > STDate::dateDiff( $check_in, $check_out ) ) {
//                STTemplate::set_message( sprintf( _n( 'Please book at least %d day in total', 'Please book at least %d days in total', $booking_min_day,'trizen-helper' ), $booking_min_day ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            /**
//             * Validate Guest Name
//             *
//             * @since  2.1.2
//             * @author dannie
//             */
//            $partner_create_booking = STInput::request('add_booking_partner_field');
//            if ( !st_validate_guest_name( $room_id, $adult_number, $child_number, 0 ) && empty($partner_create_booking)) {
//                STTemplate::set_message( esc_html__( 'Please enter the Guest Name', 'trizen-helper' ), 'danger' );
//                $pass_validate = false;
//
//                return false;
//            }
//
//            $numberday     = STDate::dateDiff( $check_in, $check_out );
//            if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on' ) {
//                $item_price = floatval( get_post_meta( $room_origin, 'adult_price', true ) ) * floatval( $adult_number ) * $numberday + floatval( get_post_meta( $room_origin, 'child_price', true ) ) * floatval( $child_number ) * $numberday;
//            } else {
//                $item_price = floatval( get_post_meta( $room_origin, 'price', true ) );
//            }
//            // Extra price added in the new version 1.1.9
//            $extras = STInput::request( 'extra_price', [] );
//
//            $extra_price   = STPrice::getExtraPrice( $room_origin, $extras, $room_num_search, $numberday );
//            $sale_price    = STPrice::getRoomPrice( $room_origin, strtotime( $check_in ), strtotime( $check_out ), $room_num_search, $adult_number, $child_number );
//            $discount_rate = STPrice::get_discount_rate( $room_origin, strtotime( $check_in ) );
//            $data          = [
//                'item_price'      => $item_price,
//                'ori_price'       => $sale_price + $extra_price,
//                'check_in'        => $check_in,
//                'check_out'       => $check_out,
//                'room_num_search' => $room_num_search,
//                'room_id'         => $room_id,
//                'adult_number'    => $adult_number,
//                'child_number'    => $child_number,
//                'extras'          => $extras,
//                'extra_price'     => $extra_price,
//                'commission'      => TravelHelper::get_commission( $item_id ),
//                'discount_rate'   => $discount_rate,
//                'guest_title'     => STInput::post( 'guest_title' ),
//                'guest_name'      => STInput::post( 'guest_name' ),
//            ];
//            if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on') {
//                $data['adult_price'] = floatval( get_post_meta( $room_origin, 'adult_price', true ) );
//                $data['child_price'] = floatval( get_post_meta( $room_origin, 'child_price', true ) );
//            }
//            if ( $pass_validate ) {
//                $pass_validate = apply_filters( 'ts_hotel_add_cart_validate', $pass_validate, $data );
//            }
//            if ( $pass_validate ) {
//                add_cart( $item_id, $room_num_search, $sale_price + $extra_price, $data );
//            }
//            return $pass_validate;
//        }

        function is_booking_period( $item_id = '', $t = '', $c = '' ) {
            $today          = strtotime( $t );
            $check_in       = strtotime( $c );
            $booking_period = intval( get_post_meta( $item_id, 'hotel_booking_period', true ) );
            $period         = TravelHelper::date_diff( $today, $check_in );
            if ( $period < $booking_period ) {
                return $booking_period;
            }
            return false;
        }


        function get_cart_item_html( $item_id = false )
        {
//            return st()->load_template( 'hotel/cart_item_html', null, [ 'item_id' => $item_id ] );
        }

        /**
         * @since  1.0
         * */
        function change_sidebar( $sidebar = false ) {
//            return st()->get_option( 'hotel_sidebar_pos', 'left' );
            return 'left';
        }

        function get_result_string()
        {
            global $wp_query, $st_search_query;
            if ( $st_search_query ) {
                $query = $st_search_query;
            } else $query = $wp_query;
            $result_string = $p1 = $p2 = $p3 = $p4 = '';

            if ( $query->found_posts ) {
                if ( $query->found_posts > 1 ) {
                    $p1 = sprintf( __( '%s hotels', 'trizen-helper' ), $query->found_posts );
                } else {
                    $p1 = sprintf( __( '%s hotel', 'trizen-helper' ), $query->found_posts );
                }
            } else {
                $p1 = __( 'No hotel found', 'trizen-helper' );
            }

            $location_id = get( 'location_id' );
            if ( $location_id and $location = get_post( $location_id ) ) {
                $p2 = sprintf( __( 'in %s', 'trizen-helper' ), get_the_title( $location_id ) );
            } elseif ( request( 'location_name' ) ) {
                $p2 = sprintf( __( 'in %s', 'trizen-helper' ), request( 'location_name' ) );
            } elseif ( request( 'address' ) ) {
                $p2 = sprintf( __( 'in %s', 'trizen-helper' ), request( 'address' ) );
            }

            if ( request( 'ts_google_location', '' ) != '' ) {
                $p2 .= sprintf( __( ' in %s', 'trizen-helper' ), esc_html( request( 'ts_google_location', '' ) ) );
            }
            $start = convertDateFormat( get( 'start' ) );
            $end   = convertDateFormat( get( 'end' ) );
            $start = strtotime( $start );
            $end = strtotime( $end );
            if ( $start and $end ) {
                $p3 = sprintf( __( 'on %s', 'trizen-helper' ), date_i18n( 'M d', $start ) . ' - ' . date_i18n( 'M d', $end ) );
            }

            if ( $adult_num = get( 'adult_number' ) ) {
                if ( $adult_num > 1 ) {
                    $p4 = sprintf( __( 'for %s adults', 'trizen-helper' ), $adult_num );
                } else {
                    $p4 = sprintf( __( 'for %s adult', 'trizen-helper' ), $adult_num );
                }

            }

            // check Right to left
            /*if ( st()->get_option( 'right_to_left' ) == 'on' || is_rtl() ) {

                return $p1 . ' ' . $p4 . ' ' . $p3 . ' ' . $p2;
            }*/
            return esc_html( $p1 . ' ' . $p2 . ' ' . $p3 . ' ' . $p4 );

        }


        function ajax_search_room() {
//            if ( ts_is_ajax() and post( 'room_search' ) ) {
            if ( post( 'room_search' ) ) {
                $result = [
                    'status' => 1,
                    'data'   => "",
                ];
                $hotel_id              = get_the_ID();
                $post                  = request();
                $post[ 'room_parent' ] = $hotel_id;

                //Check Date
                $today = date( 'm/d/Y' );
                $check_in  = convertDateFormat( $post[ 'start' ] );
                $check_out = convertDateFormat( $post[ 'end' ] );
                $date_diff = dateDiff( $check_in, $check_out );
                $booking_period = intval( get_post_meta( $hotel_id, 'hotel_booking_period', true ) );
                $period = dateDiff( $today, $check_in );

                if ( $booking_period && $period < $booking_period ) {
                    $result = [
                        'status'  => 0,
                        'data'    => TRIZEN_HELPER_URI . 'inc/hotel/loop-room-none.php',
                        'message' => sprintf( __( 'This hotel allow minimum booking is %d day(s)', 'trizen-helper' ), $booking_period )
                    ];
                    echo json_encode( $result );
                    die;
                }
                if ( $date_diff < 1 ) {
                    $result = [
                        'status'    => 0,
                        'data'      => "",
                        'message'   => __( 'Make sure your check-out date is at least 1 day after check-in.', 'trizen-helper' ),
                        'more-data' => $date_diff
                    ];
                    echo json_encode( $result );
                    die;
                }

                global $wp_query;
                $this->search_room();

                if ( have_posts() ) {
                    while ( have_posts() ) {
                        the_post();
                        $result[ 'data' ] .= preg_replace( '/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_URI . 'inc/hotel/loop-room-item.php' );
                    }
                } else {
                    $result[ 'data' ] .= TRIZEN_HELPER_URI . 'inc/hotel/loop-room-none.php';
                }
                //echo $wp_query->request;
                $result[ 'paging' ] = TravelHelper::paging_room();
                wp_reset_query();
                echo json_encode( $result );
                die();
            }
        }

        function search_room( $param = [] ) {
            $this->alter_search_room_query();
            $page = request( 'paged_room' );
            if ( !$page ) {
                $page = get_query_var( 'paged_room' );
            }
            $arg = apply_filters( 'ts_ajax_search_room_arg', [
                'post_type'      => 'hotel_room',
                'posts_per_page' => '10',
                'paged'          => $page,
            ] );
            query_posts( $arg );
            $this->remove_search_room_query();
        }

        function alter_search_room_query() {
            add_filter( 'posts_where', [ $this, '_alter_search_query_ajax' ] );
            add_action( 'posts_fields', [ $this, '_room_change_post_fields' ] );
            add_filter( 'posts_join', [ $this, '_room_get_join_query' ] );
            add_filter( 'posts_groupby', [ $this, '_room_change_posts_groupby' ] );
        }

        function remove_search_room_query() {
            remove_filter( 'posts_where', [ $this, '_alter_search_query_ajax' ] );
            remove_action( 'posts_fields', [ $this, '_room_change_post_fields' ] );
            remove_filter( 'posts_join', [ $this, '_room_get_join_query' ] );
            remove_filter( 'posts_groupby', [ $this, '_room_change_posts_groupby' ] );
        }

        function _room_get_join_query( $join ) {
            //if (!TravelHelper::checkTableDuplicate('ts_hotel')) return $join;
            global $wpdb;
            $table = $wpdb->prefix . 'ts_room_availability';
            $table_st_hotel = $wpdb->prefix . 'st_hotel';
            $join .= " INNER JOIN {$table} as tb ON {$wpdb->prefix}posts.ID = tb.post_id";
            return $join;
        }

        public function _room_change_post_fields( $fields ) {
            $fields .= ', SUM(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL)) as ts_price, COUNT(tb.id) as total_available ';
            return $fields;
        }

        public function _room_change_posts_groupby( $groupby ) {
            global $wpdb;
            if ( !$groupby or strpos( $wpdb->posts . '.ID', $groupby ) === false ) {
                //$post_id        = get_the_ID();
                $post_id = post( 'room_parent', get_the_ID() );
                $check_in       = strtotime( convertDateFormat( request( 'start' ) ) );
                $check_out      = strtotime( convertDateFormat( request( 'end' ) ) );
                $allow_full_day = get_post_meta( $post_id, 'allow_full_day', true );
                $diff           = timestamp_diff_day( $check_in, $check_out );
                $max_day        = $allow_full_day != 'off' ? $diff + 1 : $diff;
                $groupby .= $wpdb->prepare( $wpdb->posts . '.ID HAVING total_available >=%d ', $max_day );
            }
            return $groupby;
        }

        public function _alter_search_query_ajax( $where ) {
            global $wpdb;
            $hotel_id = get_the_ID();
            $sql      = $wpdb->prepare( ' AND parent_id = %d ', $hotel_id );

            if ( request( 'start' ) and request( 'end' ) ) {
                $check_in    = strtotime( convertDateFormat( request( 'start' ) ) );
                $check_out   = strtotime( convertDateFormat( request( 'end' ) ) );
                $adult_num   = request( 'adult_number', 0 );
                $child_num   = request( 'child_number', 0 );
                $number_room = request( 'room_num_search', 0 );

//                $list = HotelHelper::_hotelValidateByID($hotel_id, strtotime($check_in), strtotime($check_out), $adult_num, $child_num, $number_room);
//                if (!is_array($list) || count($list) <= 0) {
//                    $list = "''";
//                } else {
//                    $list = implode(',', $list);
//                }
                //$where .= " AND {$wpdb->prefix}posts.ID NOT IN ({$list})";

                $allow_full_day = get_post_meta( $hotel_id, 'allow_full_day', true );

                $whereNumber = " AND check_in <= %d AND (number  - IFNULL(number_booked, 0)) >= %d";
                if ( $allow_full_day == 'off' ) {
                    $whereNumber = "AND check_in < %d AND (number  - IFNULL(number_booked, 0) + IFNULL(number_end, 0)) >= %d";
                }
//                $subWhereByDate=[];
//                $whereByDate='';
//                while ($check_in<$check_out)
//                {
//                    $subWhereByDate[]=$wpdb->prepare(" ( check_in=%d ".$whereNumber." )",$check_in,$number_room);
//                    $check_in+=86400;
//                }
//                if($allow_full_day!='off')
//                {
//                    $subWhereByDate[]=$wpdb->prepare(" ( check_in=%d ".$whereNumber." )",$check_out,$number_room);
//                }
//
//                if(!empty($subWhereByDate)){
//                    $whereByDate=' AND ('.implode(' OR ',$subWhereByDate).') ';
//                }
                $sql2 = "
                        AND check_in >= %d
                        {$whereNumber}
                        AND status = 'available'
                        AND adult_number>=%d
                        AND child_number>=%d
                    ";
                $sql  .= $wpdb->prepare( $sql2, $check_in, $check_out, $number_room, $adult_num, $child_num );
            }
            $where .= $sql;
            return $where;
        }

        function get_search_arg( $param ) {
            $default = [
                's' => false
            ];
            extract( wp_parse_args( $param, $default ) );
            $arg = [];
            return $arg;
        }

        function choose_search_template( $template ) {
            global $wp_query;
            $post_type = get_query_var( 'post_type' );
            if ( $wp_query->is_search && $post_type == 'ts_hotel' ) {
                return locate_template( 'search-hotel.php' );  //  redirect to archive-search.php
            }
            return $template;
        }

        function _alter_search_query( $where ) {
            if ( is_admin() ) return $where;
            global $wp_query;
            if ( is_search() ) {
                $post_type = $wp_query->query_vars[ 'post_type' ];
                if ( $post_type == 'ts_hotel' and is_search() ) {
                    //Alter From NOW
                    global $wpdb;
                    $check_in  = get( 'start' );
                    $check_out = get( 'end' );

                    //Alter WHERE for check in and check out
                    if ( $check_in and $check_out ) {
                        $check_in  = @date( 'Y-m-d', strtotime( convertDateFormat( $check_in ) ) );
                        $check_out = @date( 'Y-m-d', strtotime( convertDateFormat( $check_out ) ) );

                        $check_in  = esc_sql( $check_in );
                        $check_out = esc_sql( $check_out );

                        $where .= " AND $wpdb->posts.ID in ((SELECT {$wpdb->postmeta}.meta_value
                        FROM {$wpdb->postmeta}
                        WHERE {$wpdb->postmeta}.meta_key='room_parent'
                        AND  {$wpdb->postmeta}.post_id NOT IN(
                            SELECT room_id FROM (
                                SELECT count(ts_meta6.meta_value) as total,
                                    ts_meta5.meta_value as total_room,ts_meta6.meta_value as room_id ,ts_meta2.meta_value as check_in,ts_meta3.meta_value as check_out
                                     FROM {$wpdb->posts}
                                            JOIN {$wpdb->postmeta}  as ts_meta2 on ts_meta2.post_id={$wpdb->posts}.ID and ts_meta2.meta_key='check_in'
                                            JOIN {$wpdb->postmeta}  as ts_meta3 on ts_meta3.post_id={$wpdb->posts}.ID and ts_meta3.meta_key='check_out'
                                            JOIN {$wpdb->postmeta}  as ts_meta6 on ts_meta6.post_id={$wpdb->posts}.ID and ts_meta6.meta_key='room_id'
                                            JOIN {$wpdb->postmeta}  as ts_meta5 on ts_meta5.post_id=ts_meta6.meta_value and ts_meta5.meta_key='number_room'
                                            WHERE {$wpdb->posts}.post_type='st_order'
                                    GROUP BY ts_meta6.meta_value HAVING total>=total_room AND (

                                                ( CAST(ts_meta2.meta_value AS DATE)<'{$check_in}' AND  CAST(ts_meta3.meta_value AS DATE)>'{$check_in}' )
                                                OR ( CAST(ts_meta2.meta_value AS DATE)>='{$check_in}' AND  CAST(ts_meta2.meta_value AS DATE)<='{$check_out}' )

                                    )
                            ) as room_booked
                        )
                    ))";
                    }

                    if ( $price_range = request( 'price_range_' ) ) {
                        // price_range_ ???
                        $price_obj = explode( ';', $price_range );
                        // convert to default money
                        $price_obj[ 0 ] = TSAdminRoom::convert_money_to_default( $price_obj[ 0 ] );
                        $price_obj[ 1 ] = TSAdminRoom::convert_money_to_default( $price_obj[ 1 ] );

                        if ( !isset( $price_obj[ 1 ] ) ) {
                            $price_from = 0;
                            $price_to   = $price_obj[ 0 ];
                        } else {
                            $price_from = $price_obj[ 0 ];
                            $price_to   = $price_obj[ 1 ];
                        }
                        global $wpdb;
                        $query = " AND {$wpdb->posts}.ID IN (
                                SELECT ID FROM
                                (
                                    SELECT ID, MIN(min_price) as min_price_new FROM
                                    (
                                    select {$wpdb->posts}.ID,
                                    IF(
                                        ts_meta3.meta_value is not NULL,
                                        IF((ts_meta2.meta_value = 'on' and CAST(ts_meta5.meta_value as DATE)<=NOW() and CAST(ts_meta4.meta_value as DATE)>=NOW()) or
                                        ts_meta2.meta_value='off'
                                        ,
                                        ts_meta1.meta_value-(ts_meta1.meta_value/100)*ts_meta3.meta_value,
                                        CAST(ts_meta1.meta_value as DECIMAL)
                                        ),
                                        CAST(ts_meta1.meta_value as DECIMAL)
                                    ) as min_price

                                    from {$wpdb->posts}
                                    JOIN {$wpdb->postmeta} on {$wpdb->postmeta}.meta_value={$wpdb->posts}.ID and {$wpdb->postmeta}.meta_key='room_parent'
                                    JOIN {$wpdb->postmeta} as ts_meta1 on ts_meta1.post_id={$wpdb->postmeta}.post_id AND ts_meta1.meta_key='price'
                                    LEFT JOIN {$wpdb->postmeta} as ts_meta2 on ts_meta2.post_id={$wpdb->postmeta}.post_id AND ts_meta2.meta_key='is_sale_schedule'
                                    LEFT JOIN {$wpdb->postmeta} as ts_meta3 on ts_meta3.post_id={$wpdb->postmeta}.post_id AND ts_meta3.meta_key='discount_rate'
                                    LEFT JOIN {$wpdb->postmeta} as ts_meta4 on ts_meta4.post_id={$wpdb->postmeta}.post_id AND ts_meta4.meta_key='sale_price_to'
                                    LEFT JOIN {$wpdb->postmeta} as ts_meta5 on ts_meta5.post_id={$wpdb->postmeta}.post_id AND ts_meta5.meta_key='sale_price_from'

                                     )as min_price_table
                                    group by ID Having  min_price_new>=%d and min_price_new<=%d ) as min_price_table_new
                                ) ";
                        $query = $wpdb->prepare( $query, $price_from, $price_to );
                        $where .= $query;
                    }
                }
            }
            return $where;
        }

        function _get_join_query( $join ) {
            //if (!TravelHelper::checkTableDuplicate('ts_hotel')) return $join;
            global $wpdb;
            $table  = $wpdb->prefix . 'ts_room_availability';
            $table2 = $wpdb->prefix . 'ts_hotel';
            $table3 = $wpdb->prefix . 'hotel_room';
            $join .= " INNER JOIN {$table} as tb ON {$wpdb->prefix}posts.ID = tb.parent_id AND status = 'available'";
            $join .= " INNER JOIN {$table2} as tb2 ON {$wpdb->prefix}posts.ID = tb2.post_id";
            //zzz Join with table hotel room to get discount rate
            $join .= " INNER JOIN {$table3} as tb3 ON tb.post_id = tb3.post_id";
            return $join;
        }

        function _get_where_query( $where ) {
            //if (!TravelHelper::checkTableDuplicate('ts_hotel')) return $where;
            global $wpdb, $ts_search_args;
            if ( !$ts_search_args ) $ts_search_args = $_REQUEST;
            /**
             * Merge data with element args with search args
             * @since  1.0
             */
            if ( !empty( $ts_search_args[ 'ts_location' ] ) ) {
                if ( empty( $ts_search_args[ 'only_featured_location' ] ) or $ts_search_args[ 'only_featured_location' ] == 'no' )
                    $ts_search_args[ 'location_id' ] = $ts_search_args[ 'st_location' ];
            }

            if ( isset( $ts_search_args[ 'location_id' ] ) && !empty( $ts_search_args[ 'location_id' ] ) ) {
                $location_id = $ts_search_args[ 'location_id' ];

                $where = TravelHelper::_ts_get_where_location( $location_id, [ 'ts_hotel' ], $where );
            } elseif ( isset( $_REQUEST[ 'location_name' ] ) && !empty( $_REQUEST[ 'location_name' ] ) ) {
                $location_name = request( 'location_name', '' );

                $ids_location = TravelObject::_get_location_by_name( $location_name );

                if ( !empty( $ids_location ) && is_array( $ids_location ) ) {
                    $where .= TravelHelper::_ts_get_where_location( $ids_location, [ 'ts_hotel' ], $where );
                } else {
                    $where .= " AND (tb2.address LIKE '%{$location_name}%'";
                    $where .= " OR {$wpdb->prefix}posts.post_title LIKE '%{$location_name}%')";
                }
            }

            if ( isset( $_REQUEST[ 'item_name' ] ) && !empty( $_REQUEST[ 'item_name' ] ) ) {
                $item_name = request( 'item_name', '' );
                $where     .= " AND {$wpdb->prefix}posts.post_title LIKE '%{$item_name}%'";
            }

            if ( isset( $_REQUEST[ 'item_id' ] ) and !empty( $_REQUEST[ 'item_id' ] ) ) {
                $item_id = request( 'item_id', '' );
                $where   .= " AND ({$wpdb->prefix}posts.ID = '{$item_id}')";
            }

            if ( isset( $_GET[ 'start' ] ) && !empty( $_GET[ 'start' ] ) && isset( $_GET[ 'end' ] ) && !empty( $_GET[ 'end' ] ) ) {
                $check_in  = date( 'Y-m-d', strtotime( convertDateFormat( $_GET[ 'start' ] ) ) );
                $check_out = date( 'Y-m-d', strtotime( convertDateFormat( $_GET[ 'end' ] ) ) );

                $check_in_stamp  = strtotime( convertDateFormat( $_GET[ 'start' ] ) );
                $check_out_stamp = strtotime( convertDateFormat( $_GET[ 'end' ] ) );

                $today = date( 'm/d/Y' );

                $period = dateDiff( $today, $check_in );

                $adult_number = get( 'adult_number', 0 );
                if ( intval( $adult_number ) < 0 ) $adult_number = 0;

                $children_number = get( 'children_num', 0 );
                if ( intval( $children_number ) < 0 ) $children_number = 0;

                $number_room = get( 'room_num_search', 0 );
                if ( intval( $number_room ) < 0 ) $number_room = 0;
                $list_hotel = $this->get_unavailability_hotel( $check_in, $check_out, $adult_number, $children_number, $number_room );
                if ( !is_array( $list_hotel ) || count( $list_hotel ) <= 0 ) {
                    $list_hotel = "''";
                } else {
                    $list_hotel = array_filter( $list_hotel, function ( $value ) {
                        return $value !== '';
                    } );
                    $list_hotel = implode( ',', $list_hotel );
                    if ( !empty( $list_hotel ) ) {
                        $check_in_rewhere  = get( 'start', '' );
                        $check_out_rewhere = get( 'end', '' );
                        if ( !empty( $check_in_rewhere ) || !empty( $check_out_rewhere ) ) {
                            $where .= " AND {$wpdb->prefix}posts.ID NOT IN ({$list_hotel}) ";
                        }
                    }
                }

                $where .= " AND CAST(tb2.hotel_booking_period AS UNSIGNED) <= {$period}";

                $where .= " AND tb.check_in >= {$check_in_stamp} AND tb.check_out <= {$check_out_stamp} ";

            } else {
                $where .= " AND check_in >= UNIX_TIMESTAMP(CURRENT_DATE) ";
            }
            if ( isset( $_REQUEST[ 'star_rate' ] ) && !empty( $_REQUEST[ 'star_rate' ] ) ) {
                $stars    = get( 'star_rate', 1 );
                $stars    = explode( ',', $stars );
                $all_star = [];
                if ( !empty( $stars ) && is_array( $stars ) ) {
                    foreach ( $stars as $val ) {
                        for ( $i = $val; $i < $val + 0.9; $i += 0.1 ) {
                            if ( $i ) {
                                $all_star[] = $i;
                            }
                        }
                    }
                }
                $list_star = implode( ',', $all_star );
                if ( $list_star ) {
                    $where .= " AND (tb2.rate_review IN ({$list_star}))";
                }
            }

            if ( isset( $_REQUEST[ 'hotel_rate' ] ) && !empty( $_REQUEST[ 'hotel_rate' ] ) ) {
                $hotel_rate = get( 'hotel_rate', '' );
                $where      .= " AND (tb2.hotel_star IN ({$hotel_rate}))";
            }

//            if (isset($_REQUEST['price_range']) && !empty($_REQUEST['price_range'])) {
//                $meta_key = st()->get_option('hotel_show_min_price', 'avg_price');
//                if ($meta_key == 'avg_price') $meta_key = "price_avg";
//
//                $price = STInput::get('price_range', '0;0');
//                $priceobj = explode(';', $price);
//
//                // convert to default money
//                $priceobj[0] = TravelHelper::convert_money_to_default($priceobj[0]);
//                $priceobj[1] = TravelHelper::convert_money_to_default($priceobj[1]);
//
//                $where .=$wpdb->prepare(" AND st_price >= %f ",$priceobj[0]);
//
//                if (isset($priceobj[1])) {
//
//                    $priceobj[1] = TravelHelper::convert_money_to_default($priceobj[1]);
//                    $where .=$wpdb->prepare(" AND st_price <= %f ",$priceobj[1]);
//                }
//            }
            if ( isset( $_REQUEST[ 'range' ] ) and isset( $_REQUEST[ 'location_id' ] ) ) {
                $range     = get( 'range', '0;5' );
                $rangeobj  = explode( ';', $range );
                $range_min = $rangeobj[ 0 ];
                $range_max = $rangeobj[ 1 ];

                $location_id = request( 'location_id' );
                $post_type   = get_query_var( 'post_type' );
                $map_lat     = (float)get_post_meta( $location_id, 'map_lat', true );
                $map_lng     = (float)get_post_meta( $location_id, 'map_lng', true );
                global $wpdb;
                $where .= "
                AND $wpdb->posts.ID IN (
                        SELECT ID FROM (
                            SELECT $wpdb->posts.*,( 6371 * acos( cos( radians({$map_lat}) ) * cos( radians( mt1.meta_value ) ) *
                                            cos( radians( mt2.meta_value ) - radians({$map_lng}) ) + sin( radians({$map_lat}) ) *
                                            sin( radians( mt1.meta_value ) ) ) ) AS distance
                                                FROM $wpdb->posts, $wpdb->postmeta as mt1,$wpdb->postmeta as mt2
                                                WHERE $wpdb->posts.ID = mt1.post_id
                                                and $wpdb->posts.ID=mt2.post_id
                                                AND mt1.meta_key = 'map_lat'
                                                and mt2.meta_key = 'map_lng'
                                                AND $wpdb->posts.post_status = 'publish'
                                                AND $wpdb->posts.post_type = '{$post_type}'
                                                AND $wpdb->posts.post_date < NOW()
                                                GROUP BY $wpdb->posts.ID HAVING distance >= {$range_min} and distance <= {$range_max}
                                                ORDER BY distance ASC
                        ) as st_data
	            )";
            }
            $where_room = '';
            if ( !empty( $_REQUEST[ 'taxonomy_hotel_room' ] ) ) {
                $tax = request( 'taxonomy_hotel_room' );
                if ( !empty( $tax ) and is_array( $tax ) ) {
                    $tax_query = [];
                    foreach ( $tax as $key => $value ) {
                        if ( $value ) {
                            $ids     = [];
                            $ids_tmp = explode( ',', $value );
                            if ( !empty( $ids_tmp ) ) {
                                foreach ( $ids_tmp as $k => $v ) {
                                    if ( !empty( $v ) ) {
                                        $ids[] = $v;
                                    }
                                }
                            }
                            if ( !empty( $ids ) ) {
                                $tax_query[] = [
                                    'taxonomy' => $key,
                                    'terms'    => $ids
                                ];
                            }
                        }
                    }

                    if ( !empty( $tax_query ) ) {
                        $where_room = ' AND (';
                        foreach ( $tax_query as $k => $v ) {
                            $ids = implode( ',', $v[ 'terms' ] );
                            if ( $k > 0 ) {
                                $where_room .= " AND ";
                            }
                            $where_room .= "  (
                                                    SELECT COUNT(1)
                                                    FROM {$wpdb->prefix}term_relationships
                                                    WHERE term_taxonomy_id IN ({$ids})
                                                    AND object_id = {$wpdb->prefix}posts.ID
                                                  ) = " . count( $v[ 'terms' ] ) . "  ";
                        }
                        $where_room .= " ) ";
                    }
                }
            }
//            $where .= apply_filters('ts_hotel_is_query_room_parent', " AND $wpdb->posts.ID IN
//				(
//				   SELECT ID FROM
//				   (
//					  SELECT meta1.meta_value as ID
//							FROM {$wpdb->prefix}posts
//
//							INNER JOIN {$wpdb->prefix}postmeta as meta1 ON {$wpdb->prefix}posts.ID = meta1.post_id and meta1.meta_key='room_parent'
//							WHERE 1=1
//							{$where_room}
//							AND {$wpdb->prefix}posts.post_type = 'hotel_room'
//							GROUP BY meta1.meta_value
//				   ) as ids
//				) ");


            /**
             * Change Where for Element List
             * @since  1.0
             * @author quandq
             */
            if ( !empty( $st_search_args[ 'only_featured_location' ] ) and !empty( $st_search_args[ 'featured_location' ] ) ) {
                $featured = $st_search_args[ 'featured_location' ];
                if ( $st_search_args[ 'only_featured_location' ] == 'yes' and is_array( $featured ) ) {

                    if ( is_array( $featured ) && count( $featured ) ) {
                        $where     .= " AND (";
                        $where_tmp = "";
                        foreach ( $featured as $item ) {
                            if ( empty( $where_tmp ) ) {
                                $where_tmp .= " tb2.multi_location LIKE '%_{$item}_%'";
                            } else {
                                $where_tmp .= " OR tb2.multi_location LIKE '%_{$item}_%'";
                            }
                        }
                        $featured  = implode( ',', $featured );
                        $where_tmp .= " OR tb2.id_location IN ({$featured})";
                        $where     .= $where_tmp . ")";
                    }
                }
            }

            return $where;
        }


        /**
         * @update 1.0
         */
        function _get_where_query_tab_location( $where ) {
            $location_id = get_the_ID();
            if ( !TSAdminRoom::checkTableDuplicate( 'ts_hotel' ) ) return $where;
            if ( !empty( $location_id ) ) {
                $where = TravelHelper::_ts_get_where_location( $location_id, [ 'ts_hotel' ], $where );
            }
            return $where;
        }


        function alter_search_query() {
            add_action( 'pre_get_posts', [ $this, 'change_search_hotel_arg' ] );
            add_action( 'posts_fields', [ $this, '_change_posts_fields' ] );
            add_filter( 'posts_where', [ $this, '_get_where_query' ] );
            add_filter( 'posts_join', [ $this, '_get_join_query' ] );
            add_filter( 'posts_orderby', [ $this, '_get_order_by_query' ] );
            add_filter( 'posts_groupby', [ $this, '_change_posts_groupby' ] );
        }

        function remove_alter_search_query() {
            remove_action( 'pre_get_posts', [ $this, 'change_search_hotel_arg' ] );
            remove_action( 'posts_fields', [ $this, '_change_posts_fields' ] );
            remove_filter( 'posts_where', [ $this, '_get_where_query' ] );
            remove_filter( 'posts_join', [ $this, '_get_join_query' ] );
            remove_filter( 'posts_orderby', [ $this, '_get_order_by_query' ] );
            remove_filter( 'posts_groupby', [ $this, '_change_posts_groupby' ] );
        }

        public function _change_posts_fields( $fields ) {
            global $wpdb;
            //zzz Calc discount price inner join with table hotel room
            $fields .= ', min(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL) - (CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL) * ((IFNULL(tb3.discount_rate, 1))/100))) as ts_price, tb2.price_avg as ts_price_avg';
            //$fields .=', min(CAST(tb.price AS DECIMAL)) as ts_price';
            return $fields;
        }

        public function _change_posts_groupby( $groupby ) {
            global $wpdb;
            //if ( !$groupby or strpos( $wpdb->posts . '.ID', $groupby ) === false ) {
            $groupby = $wpdb->posts . '.ID ';
            if ( isset( $_REQUEST[ 'price_range' ] ) && !empty( $_REQUEST[ 'price_range' ] ) ) {
                $groupby  .= " HAVING ";
                /*$meta_key = st()->get_option( 'hotel_show_min_price', 'avg_price' );
                if ( $meta_key == 'avg_price' ) */
                $meta_key = "trizen_hotel_regular_price";

                $price    = get( 'price_range', '0;0' );
                $priceobj = explode( ';', $price );

                // convert to default money
                $priceobj[ 0 ] = TSAdminRoom::convert_money_to_default( $priceobj[ 0 ] );
                $priceobj[ 1 ] = TSAdminRoom::convert_money_to_default( $priceobj[ 1 ] );
                $groupby .= $wpdb->prepare( " ts_price >= %f ", $priceobj[ 0 ] );
                if ( isset( $priceobj[ 1 ] ) ) {
                    $priceobj[ 1 ] = TSAdminRoom::convert_money_to_default( $priceobj[ 1 ] );
                    $groupby       .= $wpdb->prepare( " AND ts_price <= %f ", $priceobj[ 1 ] );
                }
            }
            // }

            return $groupby;
        }

        function change_search_hotel_arg( $query ) {
            if ( empty( $_REQUEST[ 'isajax' ] ) ) {
                if ( is_admin() and empty( $_REQUEST[ 'is_search_map' ] ) ) return $query;
            }
            /**
             * Global Search Args used in Element list and map display
             * @since 1.0
             */
            global $ts_search_args;
            if ( !$ts_search_args ) $ts_search_args = $_REQUEST;

            $post_type      = get_query_var( 'post_type' );
            $posts_per_page = '12';
            if ( $post_type == 'ts_hotel' ) {
                $query->set( 'author', '' );

                if ( get( 'item_name' ) ) {
                    $query->set( 's', get( 'item_name' ) );
                }

                if ( empty( $_REQUEST[ 'is_search_map' ] ) && empty( $query->query[ 'is_st_location_list_hotel' ] ) ) {
                    $query->set( 'posts_per_page', $posts_per_page );
                }

                $has_tax_in_element = [];
                if ( is_array( $ts_search_args ) ) {
                    foreach ( $ts_search_args as $key => $val ) {
                        if ( strpos( $key, 'taxonomies--' ) === 0 && !empty( $val ) ) {
                            $has_tax_in_element[ $key ] = $val;
                        }
                    }
                }

                if ( !empty( $has_tax_in_element ) ) {
                    $tax_query = [];
                    foreach ( $has_tax_in_element as $tax => $value ) {
                        $tax_name = str_replace( 'taxonomies--', '', $tax );
                        if ( !empty( $value ) ) {
                            $value       = explode( ',', $value );
                            $tax_query[] = [
                                'taxonomy' => $tax_name,
                                'terms'    => $value,
                                'operator' => 'IN',
                            ];
                        }

                    }
                    if ( !empty( $tax_query ) ) {
                        $query->set( 'tax_query', $tax_query );
                    }
                }

                $tax = request( 'taxonomy' );

                if ( !empty( $tax ) and is_array( $tax ) ) {
                    $tax_query = [];
                    foreach ( $tax as $key => $value ) {
                        if ( $value ) {
                            $value = explode( ',', $value );
                            if ( !empty( $value ) and is_array( $value ) ) {
                                foreach ( $value as $k => $v ) {
                                    if ( !empty( $v ) ) {
                                        $ids[] = $v;
                                    }
                                }
                            }
                            if ( !empty( $ids ) ) {
                                $tax_query[] = [
                                    'taxonomy' => $key,
                                    'terms'    => $ids,
                                    //'COMPARE'=>"IN",
                                    'operator' => 'IN',
                                ];
                            }
                            $ids = [];
                        }
                    }
                    $query->set( 'tax_query', $tax_query );
                }
//                $is_featured = st()->get_option( 'is_featured_search_hotel', 'off' );
                if ( empty( $ts_search_args[ 'ts_orderby' ] ) ) {
                    $query->set( 'meta_key', 'is_featured' );
                    $query->set( 'orderby', 'meta_value' );
                    $query->set( 'order', 'DESC' );
                }

                /**
                 * Post In and Post Order By from Element
                 * @since  1.0
                 * @author quandq
                 */
                if ( !empty( $ts_search_args[ 'ts_number_ht' ] ) ) {
                    $query->set( 'posts_per_page', $ts_search_args[ 'ts_number_ht' ] );
                }
                if ( !empty( $ts_search_args[ 'ts_ids' ] ) ) {
                    $query->set( 'post__in', explode( ',', $ts_search_args[ 'ts_ids' ] ) );
                    $query->set( 'orderby', 'post__in' );
                }
                if ( !empty( $ts_search_args[ 'ts_orderby' ] ) and $ts_orderby = $ts_search_args[ 'ts_orderby' ] ) {
                    if ( $ts_orderby == 'sale' ) {
                        $query->set( 'meta_key', 'total_sale_number' );
                        $query->set( 'orderby', 'meta_value_num' );
                    }
                    if ( $ts_orderby == 'rate' ) {
                        $query->set( 'meta_key', 'rate_review' );
                        $query->set( 'orderby', 'meta_value' );
                    }
                    if ( $ts_orderby == 'discount' ) {
                        $query->set( 'meta_key', 'discount_rate' );
                        $query->set( 'orderby', 'meta_value_num' );
                    }
                    if ( $ts_orderby == 'featured' ) {
                        $query->set( 'meta_key', 'is_featured' );
                        $query->set( 'orderby', 'meta_value' );
                        $query->set( 'order', 'DESC' );
                    }
                }
                if ( !empty( $ts_search_args[ 'sort_taxonomy' ] ) and $sort_taxonomy = $ts_search_args[ 'sort_taxonomy' ] ) {
                    if ( isset( $ts_search_args[ "id_term_" . $sort_taxonomy ] ) ) {
                        $id_term     = $ts_search_args[ "id_term_" . $sort_taxonomy ];
                        $tax_query[] = [
                            [
                                'taxonomy'         => $sort_taxonomy,
                                'field'            => 'id',
                                'terms'            => explode( ',', $id_term ),
                                'include_children' => false
                            ],
                        ];
                    }
                }


                if ( !empty( $meta_query ) ) {
                    $query->set( 'meta_query', $meta_query );
                }

                if ( !empty( $tax_query ) ) {
                    $query->set( 'tax_query', $tax_query );
                }
            }
        }

        /**
         * since 1.0
         */
        function _get_order_by_query( $orderby ) {
            if ( $check = get( 'orderby' ) ) {
                global $wpdb;
                /*$meta_key = st()->get_option( 'hotel_show_min_price', 'avg_price' );
                if ( $meta_key == 'avg_price' ) */
                    $meta_key = "trizen_hotel_regular_price";
                switch ( $check ) {
                    case "price_asc":
                        $orderby = ' ts_price asc';
                        break;
                    case "price_desc":
                        $orderby = ' ts_price desc';
                        break;
                    case "name_asc":
                        $orderby = $wpdb->posts . '.post_title';
                        break;
                    case "name_desc":
                        $orderby = $wpdb->posts . '.post_title desc';
                        break;
                    case "rand":
                        $orderby = ' rand()';
                        break;
                    case "new":
                        $orderby = $wpdb->posts . '.post_modified desc';
                        break;
                    default:
                        /*$is_featured = st()->get_option( 'is_featured_search_hotel', 'off' );
                        if ( !empty( $is_featured ) and $is_featured == 'on') {
                            //$orderby = 'tb_st_hotel.is_featured desc';
                        }else{*/
                            $orderby = $wpdb->posts . '.post_modified desc';
//                        }
                        break;
                }
            }
            else {
                global $wpdb;
                /*$is_featured = st()->get_option( 'is_featured_search_hotel', 'off' );
                if ( !empty( $is_featured ) and $is_featured == 'on') {
                    //$orderby = 'tb_st_hotel.is_featured desc';
                }else{*/
                    $orderby = $wpdb->posts . '.post_modified desc';
//                }
            }

            return $orderby;
        }


        //Helper class
        function get_last_booking() {
            if ( $this->hotel_id == false ) {
                $this->hotel_id = get_the_ID();
            }
            global $wpdb;

            $query = "SELECT * from " . $wpdb->postmeta . "
                where meta_key='item_id'
                and meta_value in (
                    SELECT ID from {$wpdb->posts}
                    join " . $wpdb->postmeta . " on " . $wpdb->posts . ".ID=" . $wpdb->postmeta . ".post_id and " . $wpdb->postmeta . ".meta_key='room_parent'
                    where post_type='hotel_room'
                    and " . $wpdb->postmeta . ".meta_value='" . $this->hotel_id . "'
                )
                order by meta_id
                limit 0,1";
            $data = $wpdb->get_results( $query, OBJECT );

            if ( !empty( $data ) ) {
                foreach ( $data as $key => $value ) {
                    return human_time_diff( get_the_time( 'U', $value->post_id ), current_time( 'timestamp' ) ) . __( ' ago', 'trizen-helper' );
                }
            }
        }

        static function count_meta_key( $key, $value, $post_type = 'ts_hotel', $location_key = 'multi_location' ) {
            $arg = [
                'post_type'      => $post_type,
                'posts_per_page' => 1,
            ];
            if ( request( 'location_id' ) ) {
                $arg[ 'meta_query' ][] = [
                    'key'   => $location_key,
                    'value' => request( 'location_id' )
                ];
            }
            if ( $key == 'rate_review' ) {
                $arg[ 'meta_query' ][] = [
                    'key'     => $key,
                    'value'   => $value,
                    'type'    => 'DECIMAL',
                    'compare' => '>='
                ];
            } else {
                $arg[ 'meta_key' ]   = $key;
                $arg[ 'meta_value' ] = $value;
            }
            $query = new WP_Query(
                $arg
            );
            $count = $query->found_posts;
            wp_reset_query();
            return $count;
        }

        static function get_avg_price( $post_id = false ) {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            $price = get_post_meta( $post_id, 'trizen_hotel_regular_price', true );
            $price = apply_filters( 'ts_apply_tax_amount', $price );
            return $price;
        }

        /**
         * Get Hotel price for listing and single page
         * @since 1.0
         * */
        static function get_price( $hotel_id = false ) {
            if ( !$hotel_id ) $hotel_id = get_the_ID();
            if ( self::is_show_min_price() ) {
                $min_price = HotelHelper::get_minimum_price_hotel( $hotel_id );
                $min_price = apply_filters( 'ts_apply_tax_amount', $min_price );
                return $min_price;
            } else {
                return get_avg_price_hotel( $hotel_id );
            }
        }

        /**
         * Check if Traveler Setting show min price instead avg price
         * @since 1.0
         * */
        static function is_show_min_price() {
            /*$show_min_or_avg = st()->get_option( 'hotel_show_min_price', 'avg_price' );
            if ( $show_min_or_avg == 'min_price' ) return true;
            return false;*/
        }

        /**
         * Base on all room price
         * @deprecate this function is no longer work
         * */
        static function get_min_price( $post_id = false ) {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            $query = [
                'post_type'      => 'hotel_room',
                'posts_per_page' => 100,
                'meta_key'       => 'room_parent',
                'meta_value'     => $post_id
            ];
            $q = new WP_Query( $query );
            $min_price = 0;
            $i         = 1;
            while ( $q->have_posts() ) {
                $q->the_post();
                $price = get_post_meta( get_the_ID(), 'price', true );
                if ( $i == 1 ) {
                    $min_price = $price;
                } else {
                    if ( $price < $min_price ) {
                        $min_price = $price;
                    }
                }
                $i++;
            }
            wp_reset_postdata();
            return apply_filters( 'ts_apply_tax_amount', $min_price );
        }

        function _change_search_result_link( $url ) {
            /*$page_id = st()->get_option( 'hotel_search_result_page' );
            if ( $page_id ) {
                $url = get_permalink( $page_id );
            }*/

            return $url;
        }

        static function get_min_max_price( $post_type = 'ts_hotel' ) {
            /*$meta_key = st()->get_option( 'hotel_show_min_price', 'avg_price' );
            if ( $meta_key == 'avg_price' )*/
            $meta_key = "trizen_hotel_regular_price";

            if ( empty( $post_type ) || !TSAdminRoom::checkTableDuplicate( $post_type ) ) {
                return [ 'price_min' => 0, 'price_max' => 500 ];
            }

            global $wpdb;

            $sql = "
                select
                    min(CAST({$meta_key} as DECIMAL)) as min,
                    max(CAST({$meta_key} as DECIMAL)) as max
                from {$wpdb->prefix}st_hotel";

            $results = $wpdb->get_results( $sql, OBJECT );

            $price_min = $results[ 0 ]->min;
            $price_max = $results[ 0 ]->max;

            if ( empty( $price_min ) ) $price_min = 0;
            if ( empty( $price_max ) ) $price_max = 500;

            return [ 'min' => ceil( $price_min ), 'max' => ceil( $price_max ) ];
        }

        static function get_price_slider() {
            global $wpdb;
            $query = "SELECT min(orgin_price) as min_price,MAX(orgin_price) as max_price from
                (SELECT
                 IF( ts_meta3.meta_value is not NULL,
                    IF((ts_meta2.meta_value = 'on' and CAST(ts_meta5.meta_value as DATE)<=NOW() and CAST(ts_meta4.meta_value as DATE)>=NOW())
                      or ts_meta2.meta_value='off' ,
                      {$wpdb->postmeta}.meta_value-({$wpdb->postmeta}.meta_value/100)*ts_meta3.meta_value,
                      CAST({$wpdb->postmeta}.meta_value as DECIMAL) ),
                  CAST({$wpdb->postmeta}.meta_value as DECIMAL) ) as orgin_price
                  FROM {$wpdb->postmeta}
                  JOIN {$wpdb->postmeta} as ts_meta1 on ts_meta1.post_id={$wpdb->postmeta}.post_id
                  LEFT JOIN {$wpdb->postmeta} as ts_meta2 on ts_meta2.post_id={$wpdb->postmeta}.post_id AND ts_meta2.meta_key='is_sale_schedule'
                  LEFT JOIN {$wpdb->postmeta} as ts_meta3 on ts_meta3.post_id={$wpdb->postmeta}.post_id AND ts_meta3.meta_key='discount_rate'
                  LEFT JOIN {$wpdb->postmeta} as ts_meta4 on ts_meta4.post_id={$wpdb->postmeta}.post_id AND ts_meta4.meta_key='sale_price_to'
                  LEFT JOIN {$wpdb->postmeta} as ts_meta5 on ts_meta5.post_id={$wpdb->postmeta}.post_id AND ts_meta5.meta_key='sale_price_from'
                  WHERE ts_meta1.meta_key='room_parent' AND {$wpdb->postmeta}.meta_key='price')
        as orgin_price_table";

            $data = $wpdb->get_row( $query );
            $min  = apply_filters( 'ts_apply_tax_amount', $data->min_price );
            $max  = apply_filters( 'ts_apply_tax_amount', $data->max_price );

            return [ 'min' => floor( $min ), 'max' => ceil( $max ) ];
        }

        static function get_owner_email( $hotel_id = false ) {
            /*$theme_option = st()->get_option( 'partner_show_contact_info' );
            $metabox      = get_post_meta( $hotel_id, 'show_agent_contact_info', true );

            $use_agent_info = false;

            if ( $theme_option == 'on' ) $use_agent_info = true;
            if ( $metabox == 'user_agent_info' ) $use_agent_info = true;
            if ( $metabox == 'user_item_info' ) $use_agent_info = false;

            if ( $use_agent_info ) {
                $post = get_post( $hotel_id );
                if ( $post ) {
                    return get_the_author_meta( 'user_email', $post->post_author );
                }
            }
            return get_post_meta( $hotel_id, 'email', true );*/
        }

        /**
         * @since 1.0
         **/
        static function getStar( $post_id = false ) {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            return intval( get_post_meta( $post_id, 'hotel_star', true ) );
        }

        static function listTaxonomy() {
            $terms        = get_object_taxonomies( 'hotel_room', 'objects' );
            $listTaxonomy = [];
            if ( !is_wp_error( $terms ) and !empty( $terms ) )
                foreach ( $terms as $key => $val ) {
                    $listTaxonomy[ $val->labels->name ] = $key;
                }
            return $listTaxonomy;
        }

        /** from 1.0 */
        static function get_taxonomy_and_id_term_tour() {
            $list_taxonomy = ts_list_taxonomy( 'ts_hotel' );
            $list_id_vc    = [];
            $param         = [];
            foreach ( $list_taxonomy as $k => $v ) {
                $param[]                       = [
                    "type"       => "ts_checkbox",
                    "holder"     => "div",
                    "heading"    => $k,
                    "param_name" => "id_term_" . $v,
                    'stype'      => 'list_terms',
                    'sparam'     => $v,
                    'dependency' => [
                        'element' => 'sort_taxonomy',
                        'value'   => [ $v ]
                    ],
                ];
                $list_value                    = "";
                $list_id_vc[ "id_term_" . $v ] = "";
            }
            return [
                "list_vc"    => $param,
                'list_id_vc' => $list_id_vc
            ];
        }

        static function get_list_hotel_by_location_or_address( $locations, $address ) {
            $location_ids = implode( ',', $locations );
            global $wpdb;
            $select   = "";
            $where    = "";
            $group_by = " GROUP BY {$wpdb->prefix}posts.ID ";
            $order_by = " ORDER BY {$wpdb->prefix}postmeta.meta_value DESC ";
            $limit    = "";

            $select .= "SELECT SQL_CALC_FOUND_ROWS {$wpdb->prefix}posts.ID
                                FROM {$wpdb->prefix}posts
                                INNER JOIN {$wpdb->prefix}postmeta
                                ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id )
                                INNER JOIN {$wpdb->prefix}ts_hotel as tb ON {$wpdb->prefix}posts.ID = tb.post_id ";

            $where   .= " WHERE 1=1 ";
            $user_id = get_current_user_id();
            if ( !is_super_admin( $user_id ) ) {
                $where .= " AND {$wpdb->prefix}posts.post_author IN ({$user_id}) ";
            }
            $where .= " AND {$wpdb->prefix}posts.post_type = 'ts_hotel' AND {$wpdb->prefix}posts.post_status = 'publish' ";
            if ( !empty( $locations ) ) {
                $where .= " AND {$wpdb->prefix}posts.ID IN (SELECT post_id FROM {$wpdb->prefix}ts_location_relationships WHERE 1=1 AND location_from IN ({$location_ids}) AND post_type IN ('st_hotel')) ";
            } else {
                if ( $address != '' ) {
                    $where .= " AND (tb.address LIKE '%{$address}%' ";
                    $where .= " OR {$wpdb->prefix}posts.post_title LIKE '%{$address}%') ";
                }
            }
            $where .= " AND {$wpdb->prefix}posts.ID IN ( SELECT ID FROM ( SELECT meta1.meta_value as ID FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta as meta1 ON {$wpdb->prefix}posts.ID = meta1.post_id and meta1.meta_key='room_parent' WHERE 1=1 AND {$wpdb->prefix}posts.post_type = 'hotel_room' GROUP BY meta1.meta_value ) as ids ) ";

            $sql = "
                         {$select}
                         {$where}
                         {$group_by}
                         {$order_by}
                         {$limit}
                        ";
            $res = $wpdb->get_results( $sql, ARRAY_A );
            return $res;
        }

        function get_unavailability_hotel( $check_in, $check_out, $adult_number, $children_number, $number_room = 1 ) {
            $check_in  = strtotime( $check_in );
            $check_out = strtotime( $check_out );

            $r = [];

            $list_hotel = TS_Hotel_Room_Availability::inst()
                ->select( 'post_id, parent_id' )
                ->where( "check_in >=", $check_in )
                ->where( "check_out <=", $check_out )
                ->where( "(status = 'unavailable' OR IFNULL(adult_number, 0) < {$adult_number} OR IFNULL(child_number, 0) < {$children_number} OR (CASE WHEN number > 0 THEN IFNULL(number, 0) - IFNULL(number_booked, 0) < {$number_room} END ) )", null, true )
                ->groupby( 'post_id' )
                ->get()->result();

            if ( !empty( $list_hotel ) ) {
                foreach ( $list_hotel as $k => $v ) {
                    $hotel_id = $v[ 'parent_id' ];
                    //if ( !empty( $hotel_id ) )
                    $r[] = $hotel_id;
                }
            }

            $freqs = array_count_values($r);

            global $wpdb;
            $sql_count_room = "SELECT room_parent, count(room_parent) as number_room FROM {$wpdb->prefix}hotel_room as ht INNER JOIN {$wpdb->prefix}posts as p ON ht.post_id = p.ID WHERE p.post_status = 'publish' GROUP By room_parent";
            $count_room_by_hotel = $wpdb->get_results($sql_count_room, ARRAY_A);
            $rs = [];
            if(!empty($count_room_by_hotel)){
                foreach ($count_room_by_hotel as $kc => $vc){
                    if(isset($freqs[$vc['room_parent']])) {
                        if ($freqs[$vc['room_parent']] >= $vc['number_room'])
                            $rs[] = $vc['room_parent'];
                    }
                }
            }

            return $rs;

            /*global $wpdb;
        $check_in = strtotime($check_in);
        $check_out = strtotime($check_out);

        $having = FALSE;
        $having_number_room = false;

        $where_number = "";

        if ($adult_number) {
            $where_number .= " OR {$wpdb->prefix}postmeta.meta_value < {$adult_number} ";
        }

        if ($children_number) {
            $where_number .= " OR st_meta1.meta_value  {$children_number} ";
        }

        if ($number_room) {
            $having_number_room .= "room.number_room - total_booked < {$number_room}";
            $where_number .= " OR st_meta3.meta_value < {$number_room} ";
        }

        if ($having) {
            $having = 'Having ' . $having;
        }
        if ($having_number_room) {
            $having_number_room = 'Having ' . $having_number_room;
        }
        $query = "
            SELECT ID, hotel_id, count(hotel_id) as total_room
            FROM
            (
            SELECT
                ID,
                {$wpdb->prefix}postmeta.meta_value as adult_number,
                st_meta1.meta_value as children_number,
                st_meta3.meta_value as number_room,
                st_meta2.meta_value as hotel_id
            FROM
                {$wpdb->posts}
            JOIN {$wpdb->prefix}postmeta on {$wpdb->prefix}postmeta.post_id={$wpdb->prefix}posts.ID and {$wpdb->prefix}postmeta.meta_key='adult_number'
            JOIN {$wpdb->prefix}postmeta as st_meta1 on st_meta1.post_id={$wpdb->prefix}posts.ID and st_meta1.meta_key='children_number'
            JOIN {$wpdb->prefix}postmeta as st_meta3 on st_meta3.post_id={$wpdb->prefix}posts.ID and st_meta3.meta_key='number_room'
            JOIN {$wpdb->prefix}postmeta as st_meta2 on st_meta2.post_id={$wpdb->prefix}posts.ID and st_meta2.meta_key='room_parent'
            where 1=1
            AND post_type='hotel_room'

            AND {$wpdb->prefix}posts.ID IN
            (
                SELECT room_id from(
                    SELECT
                room_id,
                sum(room_num_search) AS total_booked,
                room.number_room
            FROM
                {$wpdb->prefix}st_order_item_meta
            INNER JOIN {$wpdb->prefix}hotel_room AS room ON room.post_id = {$wpdb->prefix}st_order_item_meta.room_id
            WHERE
                1 = 1
            AND (
                (
                    check_in_timestamp <= {$check_in}
                    AND check_out_timestamp >= {$check_in}
                )
                OR (
                    check_in_timestamp >= {$check_in}
                    AND check_in_timestamp <= {$check_out}
                )
            )
            AND st_booking_post_type = 'st_hotel'
            AND STATUS NOT IN (
                'trash',
                'canceled',
                'wc-cancelled'
            )
            GROUP BY
                room_id
                {$having_number_room}

                ) as booked_table
            )

            OR {$wpdb->prefix}posts.ID IN
            (
                SELECT
                        post_id
                    FROM
                        {$wpdb->prefix}st_room_availability
                    WHERE
                        1 = 1
                    AND (
                        check_in >= {$check_in}
                        AND check_out <= {$check_out}
                        AND `status` = 'unavailable'
                    )
            )

            {$where_number}

            GROUP BY {$wpdb->prefix}posts.ID
            )
             as room_data
                GROUP BY hotel_id
                HAVING
                total_room >= (
                SELECT count(post_id)
                FROM wp_postmeta
                WHERE
                    meta_key = 'room_parent'
                    AND
                    meta_value = hotel_id
                )
            ";

        $res = $wpdb->get_results($query, ARRAY_A);
        if (!is_wp_error($res)) {
            $r = [];
            foreach ($res as $key => $value) {
                $r[] = $value['hotel_id'];
            }

            return $r;
        }*/

        }

        static function get_cart_item_total( $item_id, $item ) {
            /* @since 1.0 */
            $count_sale   = 0;
            $price_sale   = $item[ 'price' ];
            $adult_price2 = 0;
            $child_price2 = 0;
            if ( !empty( $item[ 'data' ][ 'discount' ] ) ) {
                $count_sale = $item[ 'data' ][ 'discount' ];
                $price_sale = $item[ 'data' ][ 'price_sale' ] * $item[ 'number' ];
            }
            $adult_number = $item[ 'data' ][ 'adult_number' ];
            $child_number = $item[ 'data' ][ 'child_number' ];
            $adult_price  = $item[ 'data' ][ 'adult_price' ];
            $child_price  = $item[ 'data' ][ 'child_price' ];
            $adult_price = round( $adult_price );
            $child_price = round( $child_price );
            $total_price = $adult_number * ts_get_discount_value( $adult_price, $count_sale, false );
            $total_price += $child_number * ts_get_discount_value( $child_price, $count_sale, false );

            return $total_price;
        }

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }

    TSHotel::inst()->init();
}
