<?php
/**
 * @package    WordPress
 * @subpackage Trizen
 * @since      1.0
 * Class TSHotel
 */

if ( !class_exists( 'TSHotel' ) ) {
    class TSHotel {
        static $_inst;
        static $_instance;
        //Current Hotel ID
        private $hotel_id;
        protected $orderby;
        protected $post_type = 'ts_hotel';
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
            //Filter the search hotel
            //custom search hotel template
            add_filter( 'template_include', [ $this, 'choose_search_template' ] );

            //Sidebar Pos for SEARCH
            add_filter( 'ts_hotel_sidebar', [ $this, 'change_sidebar' ] );

            //add Widget Area
//            add_action( 'widgets_init', [ $this, 'add_sidebar' ] );


            // Change hotel review arg
            add_filter( 'ts_hotel_wp_review_form_args', [ $this, 'comment_args' ], 10, 2 );

            //Save Hotel Review Stars
            add_action( 'comment_post', [ $this, 'save_review_stars' ] );

            //Reduce total stars of posts after comment_delete
            add_action( 'delete_comment', [ $this, 'save_post_review_stars' ] );

            //Filter change layout of hotel detail if choose in metabox
            add_filter( 'ts_hotel_detail_layout', [ $this, 'custom_hotel_layout' ] );

            add_action( 'wp_enqueue_scripts', [ $this, 'add_localize' ] );

            add_filter( 'ts_real_comment_post_id', [ $this, '_change_comment_post_id' ] );
            add_filter( 'ts_search_preload_page', [ $this, '_change_preload_search_title' ] );
            add_filter( 'ts_checkout_form_validate', [ $this, '_check_booking_period' ] );
            add_filter( 'ts_ts_hotel_search_result_link', [ $this, '_change_search_result_link' ], 10, 2 );

            // Woocommerce cart item information
            add_action( 'ts_wc_cart_item_information_ts_hotel', [ $this, '_show_wc_cart_item_information' ] );
            add_action( 'ts_wc_cart_item_information_btn_ts_hotel', [ $this, '_show_wc_cart_item_information_btn' ] );

            add_action( 'ts_before_cart_item_ts_hotel', [ $this, '_show_wc_cart_post_type_icon' ] );


            //xsearch Load post hotel filter ajax
            add_action( 'wp_ajax_ts_filter_hotel_ajax', [ $this, 'ts_filter_hotel_ajax' ] );
            add_action( 'wp_ajax_nopriv_ts_filter_hotel_ajax', [ $this, 'ts_filter_hotel_ajax' ] );

            add_action('wp_ajax_ts_top_ajax_search', [$this, '_top_ajax_search']);
            add_action('wp_ajax_nopriv_ts_top_ajax_search', [$this, '_top_ajax_search']);

            //xsearch Load post hotel filter ajax location
            add_action('wp_ajax_ts_filter_hotel_ajax_location', [$this, 'ts_filter_hotel_ajax_location']);
            add_action('wp_ajax_nopriv_ts_filter_hotel_ajax_location', [$this, 'ts_filter_hotel_ajax_location']);

            add_action('ts_review_stars_' . $this->post_type . '_content', [
                $this,
                'display_posted_review_stars'
            ]);
        }

        public function setQueryHotelSearch() {
            $page_number = get('page');
            global $wp_query, $ts_search_query;
            $hotel = $this;
            $hotel->alter_search_query();
            set_query_var('paged', $page_number);
            $paged = $page_number;
            $args = [
                'post_type'   => 'ts_hotel',
                's'           => '',
                'post_status' => ['publish'],
                'paged'       => $paged
            ];
            query_posts($args);
            $ts_search_query = $wp_query;
            $hotel->remove_alter_search_query();
        }

        public function removeSearchServiceLocationByAuthor($query) {
            $query->set('author', '');
            return $query;
        }

        public function ts_filter_hotel_ajax_location() {
            $page_number             = get('page');
            $posts_per_page          = get('posts_per_page');
            $id_location             = get('id_location');
            $_REQUEST['location_id'] = get('id_location');
            global $wp_query, $ts_search_query;
            add_filter('pre_get_posts', [$this, 'removeSearchServiceLocationByAuthor']);
            $this->setQueryHotelSearch();
            add_filter('pre_get_posts', [$this, 'removeSearchServiceLocationByAuthor']);
            $query_service = $ts_search_query;
            ob_start();
            ?>
            <div class="row row-wrapper">
                <?php if ($query_service->have_posts()) {
                    while ($query_service->have_posts()) {
                        $query_service->the_post();
                        require_once TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-item.php';
                    }
                } else {
                    echo '<div class="col-xs-12">';
                    require_once TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-none.php';
                    echo '</div>';
                }
                wp_reset_postdata(); ?>
            </div>
            <?php
            $ajax_filter_content = ob_get_contents();
            ob_clean();
            ob_end_flush();
            ob_start();
            TravelHelper::paging(false, false); ?>
            <span class="count-string">
                <?php
                if ($query_service->found_posts):
                    $posts_per_page = $posts_per_page;
                    if (!$page_number) {
                        $page = 1;
                    } else {
                        $page = $page_number;
                    }
                    $last = (int)$posts_per_page * (int)($page);
                    if ($last > $query_service->found_posts) $last = $query_service->found_posts;
                    echo sprintf(__('%d - %d of %d ', 'trizen-helper'), (int)$posts_per_page * ($page - 1) + 1, $last, $query_service->found_posts);
                    echo ($query_service->found_posts == 1) ? __('Hotel', 'trizen-helper') : __('Hotels', 'trizen-helper');
                endif;
                ?>
            </span>
            <?php
            $ajax_filter_pag = ob_get_contents();
            ob_clean();
            ob_end_flush();
            $result = [
                'content' => $ajax_filter_content,
                'pag'     => $ajax_filter_pag,
                'page'    => $page_number,
            ];
            wp_reset_query();
            wp_reset_postdata();
            echo json_encode($result);
            die;
        }

        public function ts_filter_hotel_ajax() {
            $page_number   = get('page');
            $style         = get('layout');
            $format        = get('format');
            $is_popup_map  = get('is_popup_map');
            $half_map_show = get('half_map_show');
            $fullwidth     = get('fullwidth');
            if (empty($half_map_show))
                $half_map_show = 'yes';
            $popup_map = '';
            if ($is_popup_map) {
                $popup_map = '<div class="row list-style">';
            }
            if (!in_array($format, ['normal', 'halfmap', 'popupmap']))
                $format = 'normal';
            global $wp_query, $ts_search_query;
            $this->setQueryHotelSearch();
            $query = $ts_search_query;
            //Map
            $map_lat_center = 0;
            $map_lng_center = 0;
            if (request('location_id')) {
                $location_id = post_origin(request('location_id'), 'location');
                $map_lat_center = get_post_meta($location_id, 'lat', true);
                $map_lng_center = get_post_meta($location_id, 'lng', true);
            }
            $data_map = [];
            $stt = 0;
            //End map
            ob_start();
//            echo st()->load_template('layouts/modern/common/loader', 'content');
            if (!isset($style) || empty($style)) $style = 'grid';
            switch ($format) {
                case 'halfmap':
                    echo ($style == 'grid') ? '<div class="row">' : '<div class="row list-style">';
                    break;
                default:
                    echo ($style == 'grid') ? '<div class="row row-wrapper">' : '<div class="style-list">';
                    break;
            }
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
//                    if ($fullwidth) {
//                        echo 'I am FullWidth Loop';
//                        echo st()->load_template('layouts/modern/hotel/elements/loop/' . esc_attr($format), $style, ['show_map' => $half_map_show, 'fullwidth' => true]);
//                    } else {
                    require_once TRIZEN_HELPER_PATH .'inc/hotel/search/loop-room-item.php';
//                        echo st()->load_template('layouts/modern/hotel/elements/loop/' . esc_attr($format), $style, ['show_map' => $half_map_show]);
//                    }
//                    if ($is_popup_map)
//                        $popup_map .= st()->load_template('layouts/modern/hotel/elements/loop/popupmap');
                    //Map
                    $map_lat = get_post_meta(get_the_ID(), 'lat', true);
                    $map_lng = get_post_meta(get_the_ID(), 'lng', true);
                    if (!empty($map_lat) and !empty($map_lng)) {
                        if (empty($map_lat_center)) $map_lat_center = $map_lat;
                        if (empty($map_lng_center)) $map_lng_center = $map_lng;
                        $post_type = get_post_type();
                        $data_map[$stt]['id'] = get_the_ID();
                        $data_map[$stt]['name'] = get_the_title();
                        $data_map[$stt]['post_type'] = $post_type;
                        $data_map[$stt]['lat'] = $map_lat;
                        $data_map[$stt]['lng'] = $map_lng;
                        $post_type_name = get_post_type_object($post_type);
                        $post_type_name->label;
                        $data_map[$stt]['content_html'] = preg_replace('/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_PATH .'inc/hotel/search/loop-room-item.php');
                        $data_map[$stt]['content_adv_html'] = preg_replace('/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_PATH .'inc/hotel/search/loop-room-item.php');
                        $stt++;
                    }
                    //End map
                }
            } else {
                if ($is_popup_map)
                    $popup_map .= '<div class="col-xs-12">' . esc_html__('Hotel None', 'trizen-helper') . '</div>';
                echo ($style == 'grid') ? '<div class="col-xs-12">' : '';
                require_once TRIZEN_HELPER_PATH .'inc/hotel/search/loop-room-none.php';
                echo '</div>';
            }
            echo '</div>';
            $ajax_filter_content = ob_get_contents();
            ob_clean();
            ob_end_flush();
            if ($is_popup_map) {
                $popup_map .= '</div>';
            }
            ob_start();
            TravelHelper::paging(false, false, true); ?>
            <span class="count-string">
                    <?php
                    if (!empty($ts_search_query)) {
                        $wp_query = $ts_search_query;
                    }
                    if ($wp_query->found_posts):
                        $page           = get_query_var('paged');
                        $posts_per_page = get_query_var('posts_per_page');
                        if (!$page) $page = 1;
                        $last = $posts_per_page * ($page);
                        if ($last > $wp_query->found_posts) $last = $wp_query->found_posts;
                        echo sprintf(__('%d - %d of %d ', 'trizen-helper'), $posts_per_page * ($page - 1) + 1, $last, $wp_query->found_posts);
                        echo ($wp_query->found_posts == 1) ? __('Hotel', 'trizen-helper') : __('Hotels', 'trizen-helper');
                    endif;
                    ?>
                </span>
            <?php
            $ajax_filter_pag = ob_get_contents();
            ob_clean();
            ob_end_flush();
            $count = balanceTags($this->get_result_string()) . '<div id="btn-clear-filter" class="btn-clear-filter" style="display: none;">' . __('Clear filter', 'trizen-helper') . '</div>';
            //Map
            $map_icon = 'M Icon';
            $data_tmp = [
                'data_map'       => $data_map,
                'map_lat_center' => $map_lat_center,
                'map_lng_center' => $map_lng_center,
                'map_icon'       => $map_icon
            ];
            //End map
            $result = [
                'content'       => $ajax_filter_content,
                'pag'           => $ajax_filter_pag,
                'count'         => $count,
                'page'          => $page_number,
                'content_popup' => $popup_map,
                'data_map'      => $data_tmp
            ];
            wp_reset_query();
            wp_reset_postdata();
            echo json_encode($result);
            die;
        }

        function ts_origin_id( $post_id, $service_type = 'post' ) {
            if ( function_exists( 'wpml_object_id_filter' ) ) {
                global $sitepress;
                $a = wpml_object_id_filter( $post_id, $service_type, true, $sitepress->get_default_language() );
                return $a;
            } else {
                return $post_id;
            }
        }

        function _top_ajax_search() {
            //Small security
            check_ajax_referer('ts_search_security', 'security');
            //$search_header_onof = st()->get_option('search_header_onoff', 'on');
            $search_header_orderby = 'none';
            $search_header_list    = 'post';
            $search_header_order   = 'ASC';
            $s                     = get('s');
            $arg = [
                'post_type'        => $search_header_list,
                'posts_per_page'   => 10,
                's'                => $s,
                'suppress_filters' => false,
                'orderby'          => $search_header_orderby,
                'order'            => $search_header_order,
                'author'           => false,
                'post_status'      => 'publish'
            ];
            global $sitepress;
            if (class_exists('SitePress') and get('lang')) {
                $sitepress->switch_lang(get('lang'));
            }
            add_filter('pre_get_posts', [$this, '_change_top_search']);
            $query = new WP_Query();
            $query->is_admin = false;
            $query->query($arg);
            $r = [];
            $r['x'] = $arg;
            remove_filter('pre_get_posts', [$this, '_change_top_search']);
            while ($query->have_posts()) {
                $query->the_post();
                $post_type = get_post_type(get_the_ID());
                $obj = get_post_type_object($post_type);
                $item = [
                    'title' => html_entity_decode(get_the_title()),
                    'id' => get_the_ID(),
                    'type' => $obj->labels->singular_name,
                    'url' => get_permalink(),
                    'obj' => $obj
                ];
                if ($post_type == 'location') {
                    $item['url'] = home_url(esc_url_raw('?s=&post_type=ts_hotel&location_id=' . get_the_ID()));
                }
                $r['data'][] = $item;
            }
            wp_reset_query();
            echo json_encode($r);
            die();
        }

        /**
         * @since 1.0
         * */
        function _deposit_calculator( $cart_data, $item_id ) {
//            $room_id = isset( $cart_data[ 'data' ][ 'room_id' ] ) ? $cart_data[ 'data' ][ 'room_id' ] : false;
//            if ( $room_id ) {
//                $cart_data = parent::_deposit_calculator( $cart_data, $room_id );
//            }
//            return $cart_data;
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
        function _show_wc_cart_item_information( $ts_booking_data = [] ) {
//            echo st()->load_template( 'hotel/wc_cart_item_information', false, [ 'ts_booking_data' => $ts_booking_data ] );
        }

        function _add_room_number_field( $post_type = false ){
            /*if ( $post_type == 'hotel_room' ) {
                echo st()->load_template( 'hotel/checkout_fields', null, [ 'key' => get_the_ID() ] );

                return;
            }*/
        }

        function _is_hotel_booking() {
            $items = TSCart::get_items();
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
            $cart     = TSCart::get_items();
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
            $items = TSCart::get_items();

            if ( !empty( $items ) ) {
                foreach ( $items as $key => $value ) {
                    if ( get_post_type( $key ) == 'ts_hotel' ) {
                        // validate
                        $default = [
                            'number' => 1
                        ];
                        $value     = wp_parse_args( $value, $default );
                        $room_num  = $value[ 'number' ];
                        $room_data = request( 'room_data', [] );
                        if ( $room_num > 1 ) {
                            if ( !is_array( $room_data ) or empty( $room_data ) ) {
                                set_message( __( 'Room information is required', 'trizen-helper' ), 'danger' );
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
        function display_posted_review_stars($comment_id) {
            if (get_post_type() == $this->post_type) {
                $data     = $this->get_review_stars();
                $output[] = '<ul class="list booking-item-raiting-summary-list mt20">';
                if (!empty($data) and is_array($data)) {
                    foreach ($data as $value) {
                        $key        = $value;
                        $star_value = get_comment_meta($comment_id, 'ts_star_' . sanitize_title($value), true);
                        $output[]   = '
                    <li>
                        <div class="booking-item-raiting-list-title">' . $key . '</div>
                        <ul class="icon-group booking-item-rating-stars">';
                        for ($i = 1; $i <= 5; $i++) {
                            $class = '';
                            if ($i > $star_value)
                                $class = 'text-gray';
                            $output[] = '<li><i class="la la-star ' . $class . '"></i>';
                        }
                        $output[] = '
                        </ul>
                    </li>';
                    }
                }
                $output[] = '</ul>';
                echo implode("\n", $output);
            }
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
        function save_review_stars($comment_id) {
            $comemntObj = get_comment($comment_id);
            $post_id    = $comemntObj->comment_post_ID;

            if (get_post_type($post_id) == 'ts_hotel') {
                $all_stars = $this->get_review_stars();
                $ts_review_stars = post('ts_review_stars');

                if (!empty($all_stars) and is_array($all_stars)) {
                    $total_point = 0;
                    foreach ( $all_stars as $key => $value ) {
                        if ( isset( $ts_review_stars[$value] ) ) {
                            //Now Update the Each Star Value
                            if( is_numeric( $ts_review_stars[$value] ) ) {
                                $ts_review_stars[$value] = intval( $ts_review_stars[$value] );
                            } else {
                                $ts_review_stars[$value] = 5;
                            }
                            $total_point += $ts_review_stars[$value];
                            update_comment_meta($comment_id, 'ts_star_' . sanitize_title($value), $ts_review_stars[$value]);
                        }
                    }
                    $avg = round($total_point / count($all_stars), 1);
                    //Update comment rate with avg point
                    $rate = wp_filter_nohtml_kses($avg);
                    if ($rate > 5) {
                        //Max rate is 5
                        $rate = 5;
                    }

                    update_comment_meta($comment_id, 'comment_rate', $rate);
                    //Now Update the Stars Value
                    update_comment_meta($comment_id, 'ts_review_stars', $ts_review_stars);
                }
            }
            if (post('comment_rate')) {
                update_comment_meta($comment_id, 'comment_rate', post('comment_rate'));
            }
            //review_stars
            $avg = TSReview::get_avg_rate($post_id);
            update_post_meta($post_id, 'rate_review', $avg);
        }
        function save_post_review_stars( $comment_id ) {
            $comemntObj = get_comment( $comment_id );
            $post_id    = $comemntObj->comment_post_ID;
            $avg        = TSReview::get_avg_rate( $post_id );
            update_post_meta( $post_id, 'rate_review', $avg );
        }
        function get_review_stars() {
            $review_star = get_option( 'hotel_review_stars' );
            return $review_star;
        }
        function get_review_stars_metabox() {
            $review_star = get_option( 'hotel_review_stars' );
            $result      = [];
            if ( !empty( $review_star ) ) {
                foreach ( $review_star as $key => $value ) {
                    $result[] = [
                        'label' => $value,
                        'value' => sanitize_title( $value )
                    ];
                }
            }
            return $result;
        }
        function comment_args( $comment_form, $post_id = false ) {
            if ( !$post_id ) $post_id = get_the_ID();
            if ( get_post_type( $post_id ) == 'ts_hotel' ) {
                $stars = $this->get_review_stars();

                if ( $stars and is_array( $stars ) ) {
                    $star_html = '<ul class="list booking-item-raiting-summary-list stars-list-select">';

                    foreach ( $stars as $key => $value ) {
                        $star_html .= '<li class=""><div class="booking-item-raiting-list-title">' . esc_html($value) . '</div>
                                <ul class="icon-group booking-item-rating-stars">
                                <li class=""><i class="la la-star"></i>
                                </li>
                                <li class=""><i class="la la-star"></i>
                                </li>
                                <li class=""><i class="la la-star"></i>
                                </li>
                                <li class=""><i class="la la-star"></i>
                                </li>
                                <li><i class="la la-star"></i>
                                </li>
                            </ul>
                            <input type="hidden" class="ts_review_stars" value="0" name="ts_review_stars[' . esc_attr($value) . ']">
                                </li>';
                    }
                    $star_html .= '</ul>';
                    $comment_form[ 'comment_field' ] = "
                        <div class='row'>
                            <div class=\"col-sm-8\"> ";
                    $comment_form[ 'comment_field' ] .= '<div class="form-group">
                                            <label>' . esc_html__( 'Review Title', 'trizen-helper' ) . '</label>
                                            <input class="form-control" type="text" name="comment_title">
                                        </div>';

                    $comment_form[ 'comment_field' ] .= '<div class="form-group">
                                            <label>' . esc_html__( 'Review Text', 'trizen-helper' ) . '</label>
                                            <textarea name="comment" id="comment" class="form-control" rows="6"></textarea>
                                        </div>
                                        </div><!--End col-sm-8-->
                                        ';

                    $comment_form[ 'comment_field' ] .= '<div class="col-sm-4">' . $star_html . '</div></div><!--End Row-->';
                }
            }
            return $comment_form;
        }
        function get_data_room_availability($room_id = '', $check_in = '', $check_out = '', $number_room = 1, $adult_number = '', $child_number = ''){
            $number_room   = !empty($number_room) ? $number_room : 1;
            $room_id       = intval($room_id);
            $default_state = get_post_meta($room_id, 'default_state', true);
            if(!$default_state) $default_state = 'available';
            $total_price = 0;
            /**
             * @since 1.0
             * sale by number day
             **/
            $sale_by_day     = array();
            $sale_count_date = 0;
            if(get_post_type($room_id) == 'hotel_room'){
                $price_by_per_person = get_post_meta( $room_id, 'price_by_per_person', true );
                if ( $price_by_per_person == 'on' ) {
                    $adult_price = floatval( get_post_meta( $room_id, 'adult_price', true ) );
                    $child_price = floatval( get_post_meta( $room_id, 'child_price', true ) );
                    $price_ori   = floatval( $adult_number ) * $adult_price + floatval( $child_number ) * $child_price ;
                } else {
                    $price_ori = floatval(get_post_meta($room_id, 'price', true));
                }
                if($price_ori < 0) $price_ori = 0;
                $discount_rate = floatval(get_post_meta($room_id,'discount_rate',true));
                if($discount_rate < 0) $discount_rate = 0;
                if($discount_rate > 100) $discount_rate = 100;
                $is_sale_schedule = get_post_meta($room_id, 'is_sale_schedule', true);
                if($is_sale_schedule == false || empty($is_sale_schedule)) $is_sale_schedule = 'off';
                // Price with custom price
                $room_origin_id = post_origin($room_id, 'hotel_room');
                $custom_price   = AvailabilityHelper::_getdataHotel($room_origin_id, $check_in, $check_out);
                $groupday       = TSPrice::getGroupDay($check_in, $check_out);
                $_price_child   = $_price_adule = 0;
                if(is_array($groupday) && count($groupday)){
                    foreach($groupday as $key => $date){
                        $price_tmp_adult = 0;
                        $price_tmp_child = 0;
                        $status = 'available';
                        $priority = 0;
                        $in_date = false;
                        foreach($custom_price as $key2 => $val){
                            if($date[0] >= $val->check_in && $date[0] <= $val->check_out){
                                $status = $val->status;
                                if ( $price_by_per_person == 'on' ) {
                                    $_price_child_item =  floatval( $child_number ) * $val->child_price;
                                    $_price_adule_item = floatval( $adult_number ) * $val->adult_price;
                                } else {
                                    $price = floatval($val->price);
                                }
                                if(!$in_date) $in_date = true;
                            }
                        }
                        if($in_date){
                            if($status = 'available'){
                                $price_tmp_child = $_price_child_item;
                                $price_tmp_adult = $_price_adule_item;
                            }
                        }else{
                            if($default_state == 'available'){
                                $price_tmp_child = $child_price;
                                $price_tmp_adult = $adult_price;
                            }
                        }
                        $_price_child += $price_tmp_child;
                        $_price_adule += $price_tmp_adult;
                    }
                    return array(
                        'child_price' => $_price_child,
                        'adult_price' => $_price_adule,

                    );
                } else {
                    if(is_array($custom_price) && count($custom_price)){
                        $count_item = count($custom_price);
                        foreach($custom_price as $key=>$item_val){
                            if($key < $count_item){
                                $_price_adule += $item_val->adult_price;
                                $_price_child += $item_val->child_price;
                            }
                        }
                        return array(
                            'child_price' => $_price_child,
                            'adult_price' => $_price_adule,
                        );
                    }
                }
            }
            return 0;
        }
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
        function change_sidebar( $sidebar = false ) {
            return 'left';
        }
        function get_result_string() {
            global $wp_query, $ts_search_query;
            if ($ts_search_query) {
                $query = $ts_search_query;
            } else $query = $wp_query;
            $result_string = $p1 = $p2 = $p3 = $p4 = '';
            $location_id   = get('location_id', '');
            $get_post      = get_post($location_id);
            if (!empty($location_id) and isset($get_post)) {
                $p1 = sprintf(__('%s: ', 'trizen-helper'), get_the_title($location_id));
            } elseif (request('address')) {
                $p1 = sprintf(__('%s: ', 'trizen-helper'), request('address', ''));
            }
            if ($query->found_posts) {
                if ($query->found_posts > 1) {
                    $p2 = sprintf(__('%s hotels found', 'trizen-helper'), $query->found_posts);
                } else {
                    $p2 = sprintf(__('%s hotel found', 'trizen-helper'), $query->found_posts);
                }
            } else {
                $p2 = __('No hotel found', 'trizen-helper');
            }
            return esc_html($p1 . $p2);
        }
        function search_room( ) {
            $this->alter_search_room_query();
            $arg = apply_filters('ts_ajax_search_room_arg', [
                'post_type'      => 'hotel_room',
                'posts_per_page' => -1,
            ]);
            $query = new WP_Query($arg);
            $this->remove_search_room_query();
            return $query;
        }
        function alter_search_room_query() {
            add_filter('pre_get_posts', [$this, '_change_room_pre_get_posts']);
            add_filter('posts_where', [$this, '_alter_search_query_ajax']);
            add_action('posts_fields', [$this, '_room_change_post_fields']);
            add_filter('posts_join', [$this, '_room_get_join_query']);
            add_filter('posts_groupby', [$this, '_room_change_posts_groupby']);
        }
        function remove_search_room_query() {
            remove_filter('pre_get_posts', [$this, '_change_room_pre_get_posts']);
            remove_filter('posts_where', [$this, '_alter_search_query_ajax']);
            remove_action('posts_fields', [$this, '_room_change_post_fields']);
            remove_filter('posts_join', [$this, '_room_get_join_query']);
            remove_filter('posts_groupby', [$this, '_room_change_posts_groupby']);
        }
        public function _change_room_pre_get_posts($query) {
            $query->set('author', '');
            return $query;
        }
        function _room_get_join_query( $join ) {
            //if (!checkTableDuplicate('ts_hotel')) return $join;
            global $wpdb;
            $table = $wpdb->prefix . 'ts_room_availability';
            $join .= " INNER JOIN {$table} as tb ON {$wpdb->prefix}posts.ID = tb.post_id";
            return $join;
        }
        public function _room_change_post_fields( $fields ) {
            $fields .= ', SUM(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL)) as ts_price, COUNT(tb.id) as total_available ';
            return $fields;
        }
        public function _room_change_posts_groupby($groupby) {
            global $wpdb;
            if (!$groupby or strpos($wpdb->posts . '.ID', $groupby) === false) {
                //$post_id        = get_the_ID();
                $post_id        = post('room_parent', get_the_ID());
                $post_id        = post_origin($post_id);
                $check_in       = strtotime(convertDateFormat(request('start')));
                $check_out      = strtotime(convertDateFormat(request('end')));
                $allow_full_day = get_post_meta($post_id, 'allow_full_day', true);
                $diff           = timestamp_diff_day($check_in, $check_out);
                $max_day        = $allow_full_day == 1 ? $diff + 1 : $diff;
                $groupby       .= $wpdb->prepare($wpdb->posts . '.ID HAVING total_available >=%d ', $max_day);
            }
            return $groupby;
        }
        public function _alter_search_query_ajax($where) {
            global $wpdb;
            $hotel_id     = post('room_parent', get_the_ID());
            $hotel_origin = post_origin($hotel_id);
            $sql          = $wpdb->prepare(' AND parent_id = %d ', $hotel_origin);
            if (request('start') and request('end')) {
                $check_in    = strtotime(convertDateFormat(request('start')));
                $check_out   = strtotime(convertDateFormat(request('end')));
                $adult_num   = request('adult_number', 0);
                $child_num   = request('child_number', 0);
                $infant_num  = request('infant_number', 0);
//                $list = HotelHelper::_hotelValidateByID($hotel_id, strtotime($check_in), strtotime($check_out), $adult_num, $child_num, $infant_num);
//                if (!is_array($list) || count($list) <= 0) {
//                    $list = "''";
//                } else {
//                    $list = implode(',', $list);
//                }
                //$where .= " AND {$wpdb->prefix}posts.ID NOT IN ({$list})";
                $allow_full_day = get_post_meta($hotel_origin, 'allow_full_day', true);
                $whereNumber = " AND check_in <= %d AND (number  - IFNULL(number_booked, 0)) >= %d";
                if (!$allow_full_day == 1) {
                    $whereNumber = "AND check_in < %d AND (number  - IFNULL(number_booked, 0) + IFNULL(number_end, 0)) >= %d";
                }
                $sql2 = "
                        AND check_in >= %d
                        {$whereNumber}
                        AND status = 'available'
                        AND adult_number>=%d
                        AND child_number>=%d
                    ";
                $sql .= $wpdb->prepare($sql2, $check_in, $check_out, $infant_num, $adult_num, $child_num);
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
                return 'I am search-hotel.php';//locate_template( 'search-hotel.php' );  //  redirect to archive-search.php
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
                                            WHERE {$wpdb->posts}.post_type='ts_order'
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
                        $price_obj[ 0 ] = TravelHelper::convert_money_to_default( $price_obj[ 0 ] );
                        $price_obj[ 1 ] = TravelHelper::convert_money_to_default( $price_obj[ 1 ] );

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
        function _get_join_query($join) {
            //if (!checkTableDuplicate('ts_hotel')) return $join;
            global $wpdb;
            $table  = $wpdb->prefix . 'ts_room_availability';
            $table2 = $wpdb->prefix . 'ts_hotel';
            $table3 = $wpdb->prefix . 'hotel_room';
            $disable_avai_check = get_option('disable_availability_check');
            if (!$disable_avai_check == 1) {
                $join .= " INNER JOIN {$table} as tb ON {$wpdb->prefix}posts.ID = tb.parent_id AND status = 'available'";
                $join .= " INNER JOIN {$table3} as tb3 ON (tb.post_id = tb3.post_id and tb3.`status` IN ('publish', 'private'))";
            }
            $join .= " INNER JOIN {$table2} as tb2 ON {$wpdb->prefix}posts.ID = tb2.post_id";
            return $join;
        }
        function _get_where_query($where) {
            global $wpdb, $ts_search_args;
            if (!$ts_search_args) $ts_search_args = $_REQUEST;
            if (!empty($ts_search_args['ts_location'])) {
                if (empty($ts_search_args['only_featured_location']) or $ts_search_args['only_featured_location'] == 'no')
                    $ts_search_args['location_id'] = $ts_search_args['ts_location'];
            }
            if (isset($ts_search_args['location_id']) && !empty($ts_search_args['location_id'])) {
                $location_id = $ts_search_args['location_id'];
                $location_id = post_origin($location_id, 'location');
                $where = TravelHelper::_ts_get_where_location($location_id, ['ts_hotel'], $where);
            } elseif (isset($_REQUEST['location_name']) && !empty($_REQUEST['location_name'])) {
                $location_name = request('location_name', '');
                $ids_location  = TSHotel::_get_location_by_name($location_name);
                if (!empty($ids_location) && is_array($ids_location)) {
                    foreach ($ids_location as $key => $id) {
                        $ids_location[$key] = post_origin($id, 'location');
                    }
                    $where .= TravelHelper::_ts_get_where_location($ids_location, ['ts_hotel'], $where);
                } else {
                    $where .= " AND (tb2.address LIKE '%{$location_name}%'";
                    $where .= " OR {$wpdb->prefix}posts.post_title LIKE '%{$location_name}%')";
                }
            }
            if (isset($_REQUEST['item_name']) && !empty($_REQUEST['item_name'])) {
                $item_name = request('item_name', '');
                $where    .= " AND {$wpdb->prefix}posts.post_title LIKE '%{$item_name}%'";
            }
            if (isset($_REQUEST['item_id']) and !empty($_REQUEST['item_id'])) {
                $item_id = request('item_id', '');
                $where  .= " AND ({$wpdb->prefix}posts.ID = '{$item_id}')";
            }
            $check_in  = get('start', '');
            $check_out = get('end', '');
            if (!empty($check_in) && !empty($check_out)) {
                $check_in  = date('Y-m-d', strtotime(convertDateFormat($check_in)));
                $check_out = date('Y-m-d', strtotime(convertDateFormat($check_out)));
                $check_in_stamp  = strtotime($check_in);
                $check_out_stamp = strtotime($check_out);
            } else {
                $check_in        = date('Y-m-d');
                $check_out       = date('Y-m-d', strtotime('+1 day'));
                $check_in_stamp  = strtotime($check_in);
                $check_out_stamp = strtotime($check_out);
            }
            if ($check_in && $check_out) {
                $today        = date('m/d/Y');
                $period       = dateDiff($today, $check_in);
                $adult_number = get('adult_number', 0);
                if (intval($adult_number) < 0) $adult_number = 0;
                $children_number = get('children_num', 0);
                if (intval($children_number) < 0) $children_number = 0;
                $number_room = get('room_num_search', 0);
                if (intval($number_room) < 0) $number_room = 0;
                $disable_avai_check = get_option('disable_availability_check');
                if (!$disable_avai_check == 1) {
                    $list_hotel = $this->get_unavailability_hotel($check_in, $check_out, $adult_number, $children_number, $number_room);
                    if (!is_array($list_hotel) || count($list_hotel) <= 0) {
                        $list_hotel = "''";
                    } else {
                        $list_hotel = array_filter($list_hotel, function ($value) {
                            return $value !== '';
                        });
                        $list_hotel = implode(',', $list_hotel);
                        if (!empty($list_hotel)) {
                            $check_in_rewhere  = get('start', '');
                            $check_out_rewhere = get('end', '');
                            if (!empty($check_in_rewhere) || !empty($check_out_rewhere)) {
                                $where .= " AND {$wpdb->prefix}posts.ID NOT IN ({$list_hotel}) ";
                            }
                        }
                    }
                    $where .= " AND tb.check_in >= {$check_in_stamp} AND tb.check_out <= {$check_out_stamp} ";
                }
                $where .= " AND CAST(tb2.hotel_booking_period AS UNSIGNED) <= {$period}";
            } else {
                $disable_avai_check = get_option('disable_availability_check');
                if (!$disable_avai_check == 1) {
                    $where .= " AND check_in >= UNIX_TIMESTAMP(CURRENT_DATE) ";
                }
            }
            if (isset($_REQUEST['star_rate']) && !empty($_REQUEST['star_rate'])) {
                $stars    = get('star_rate', 1);
                $stars    = explode(',', $stars);
                $all_star = [];
                if (!empty($stars) && is_array($stars)) {
                    foreach ($stars as $val) {
                        if ($val == 'zero') {
                            $val = 0;
                            for ($i = $val; $i < $val + 1; $i = $i + 0.1) {
                                $all_star[] = $i;
                            }
                        } else {
                            for ($i = $val + 0.1; $i <= $val + 1.1; $i = $i + 0.1) {
                                $all_star[] = $i;
                            }
                        }
                    }
                }
                $list_star = implode(',', $all_star);
                if ($list_star) {
                    $where .= " AND (tb2.rate_review IN ({$list_star}))";
                }
            }
            if (isset($_REQUEST['hotel_rate']) && !empty($_REQUEST['hotel_rate'])) {
                $hotel_rate = get('hotel_rate', '');
                $where     .= " AND (tb2.hotel_star IN ({$hotel_rate}))";
            }
            if (isset($_REQUEST['range']) and isset($_REQUEST['location_id'])) {
                $range       = get('range', '0;5');
                $rangeobj    = explode(';', $range);
                $range_min   = $rangeobj[0];
                $range_max   = $rangeobj[1];
                $location_id = request('location_id');
                $post_type   = get_query_var('post_type');
                $map_lat     = (float)get_post_meta($location_id, 'lat', true);
                $map_lng     = (float)get_post_meta($location_id, 'lng', true);
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
                                                AND mt1.meta_key = 'lat'
                                                and mt2.meta_key = 'lng'
                                                AND $wpdb->posts.post_status = 'publish'
                                                AND $wpdb->posts.post_type = '{$post_type}'
                                                AND $wpdb->posts.post_date < NOW()
                                                GROUP BY $wpdb->posts.ID HAVING distance >= {$range_min} and distance <= {$range_max}
                                                ORDER BY distance ASC
                        ) as ts_data
	            )";
            }
            $where_room = '';
            if (!empty($_REQUEST['taxonomy_hotel_room'])) {
                $tax = request('taxonomy_hotel_room');
                if (!empty($tax) and is_array($tax)) {
                    $tax_query = [];
                    foreach ($tax as $key => $value) {
                        if ($value) {
                            $ids = "";
                            $ids_tmp = explode(',', $value);
                            if (!empty($ids_tmp)) {
                                foreach ($ids_tmp as $k => $v) {
                                    if (!empty($v)) {
                                        $ids[] = $v;
                                    }
                                }
                            }
                            if (!empty($ids)) {
                                $tax_query[] = [
                                    'taxonomy' => $key,
                                    'terms' => $ids
                                ];
                            }
                        }
                    }
                    if (!empty($tax_query)) {
                        $where_room = ' AND (';
                        foreach ($tax_query as $k => $v) {
                            $ids = implode(',', $v['terms']);
                            if ($k > 0) {
                                $where_room .= " AND ";
                            }
                            $where_room .= "  (
                                                    SELECT COUNT(1)
                                                    FROM {$wpdb->prefix}term_relationships
                                                    WHERE term_taxonomy_id IN ({$ids})
                                                    AND object_id = {$wpdb->prefix}posts.ID
                                                  ) = " . count($v['terms']) . "  ";
                        }
                        $where_room .= " ) ";
                    }
                }
            }
            if (!empty($ts_search_args['only_featured_location']) and !empty($ts_search_args['featured_location'])) {
                $featured = $ts_search_args['featured_location'];
                if ($ts_search_args['only_featured_location'] == 'yes' and is_array($featured)) {
                    if (is_array($featured) && count($featured)) {
                        $where .= " AND (";
                        $where_tmp = "";
                        foreach ($featured as $item) {
                            if (empty($where_tmp)) {
                                $where_tmp .= " tb2.multi_location LIKE '%_{$item}_%'";
                            } else {
                                $where_tmp .= " OR tb2.multi_location LIKE '%_{$item}_%'";
                            }
                        }
                        $featured = implode(',', $featured);
                        $where_tmp .= " OR tb2.id_location IN ({$featured})";
                        $where .= $where_tmp . ")";
                    }
                }
            }
            return $where;
        }
        function _get_where_query_tab_location( $where ) {
            $location_id = get_the_ID();
            if ( !TravelHelper::checkTableDuplicate( 'ts_hotel' ) ) return $where;
            if ( !empty( $location_id ) ) {
                $where = TravelHelper::_ts_get_where_location( $location_id, [ 'ts_hotel' ], $where );
            }
            return $where;
        }
        function alter_search_query() {
            add_action('pre_get_posts', [$this, 'change_search_hotel_arg']);
            add_action('posts_fields', [$this, '_change_posts_fields']);
            add_filter('posts_where', [$this, '_get_where_query']);
            add_filter('posts_join', [$this, '_get_join_query']);
            add_filter('posts_orderby', [$this, '_get_order_by_query']);
            add_filter('posts_groupby', [$this, '_change_posts_groupby']);
        }
        function remove_alter_search_query() {
            remove_action('pre_get_posts', [$this, 'change_search_hotel_arg']);
            remove_action('posts_fields', [$this, '_change_posts_fields']);
            remove_filter('posts_where', [$this, '_get_where_query']);
            remove_filter('posts_join', [$this, '_get_join_query']);
            remove_filter('posts_orderby', [$this, '_get_order_by_query']);
            remove_filter('posts_groupby', [$this, '_change_posts_groupby']);
        }
        public function _change_posts_fields($fields) {
            global $wpdb;
            $disable_avai_check = get_option('disable_availability_check');
            if (!$disable_avai_check == 1) {
                $fields .= ', min(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL) ) as ts_price';
            } else {
                if (self::is_show_min_price()) {
                    $fields .= ', min(CAST(tb2.min_price as DECIMAL)) as ts_price';
                } else {
                    $fields .= ', min(CAST(tb2.price_avg as DECIMAL)) as ts_price';
                }
            }
            return $fields;
        }
        public function _change_posts_groupby($groupby) {
            global $wpdb;
            //if ( !$groupby or strpos( $wpdb->posts . '.ID', $groupby ) === false ) {
            $groupby = $wpdb->posts . '.ID ';
            if (isset($_REQUEST['price_range']) && !empty($_REQUEST['price_range'])) {
                $groupby .= " HAVING ";
                $meta_key = 'avg_price';
                if ($meta_key == 'avg_price') $meta_key = "price_avg";
                $price    = get('price_range', '0;0');
                $priceobj = explode(';', $price);
                // convert to default money
                $priceobj[0] = TravelHelper::convert_money_to_default($priceobj[0]);
                $priceobj[1] = TravelHelper::convert_money_to_default($priceobj[1]);
                $groupby    .= $wpdb->prepare(" ts_price >= %f ", $priceobj[0]);
                if (isset($priceobj[1])) {
                    $groupby .= $wpdb->prepare(" AND ts_price <= %f ", $priceobj[1]);
                }
            }
            // }
            return $groupby;
        }
        function change_search_hotel_arg($query) {
            if (is_admin() and empty($_REQUEST['is_search_map']) and empty($_REQUEST['is_search_page'])) return $query;
            /**
             * Global Search Args used in Element list and map display
             * @since 1.0
             */
            global $ts_search_args;
            if (!$ts_search_args) $ts_search_args = $_REQUEST;
            $post_type      = get_query_var('post_type');
            $posts_per_page = 12;
            if ($post_type == 'ts_hotel') {
                $query->set('author', '');
                if (get('item_name')) {
                    $query->set('s', get('item_name'));
                }
                if ((empty($_REQUEST['is_search_map']) && empty($query->query['is_ts_location_list_hotel'])) or !empty($_REQUEST['is_search_page'])) {
                    $query->set('posts_per_page', $posts_per_page);
                }
                $has_tax_in_element = [];
                if (is_array($ts_search_args)) {
                    foreach ($ts_search_args as $key => $val) {
                        if (strpos($key, 'taxonomies--') === 0 && !empty($val)) {
                            $has_tax_in_element[$key] = $val;
                        }
                    }
                }
                if (!empty($has_tax_in_element)) {
                    $tax_query = [];
                    foreach ($has_tax_in_element as $tax => $value) {
                        $tax_name = str_replace('taxonomies--', '', $tax);
                        if (!empty($value)) {
                            $value = explode(',', $value);
                            $tax_query[] = [
                                'taxonomy' => $tax_name,
                                'terms'    => $value,
                                'operator' => 'IN',
                            ];
                        }
                    }
                    if (!empty($tax_query)) {
                        $type_filter_option_attribute = 'and';
                        array_push($tax_query,array('relation' => $type_filter_option_attribute));
                        $query->set('tax_query', $tax_query);
                    }
                }
                $tax = request('taxonomy');
                if (!empty($tax) and is_array($tax)) {
                    $tax_query = [];
                    foreach ($tax as $key => $value) {
                        if ($value) {
                            $value = explode(',', $value);
                            if (!empty($value) and is_array($value)) {
                                foreach ($value as $k => $v) {
                                    if (!empty($v)) {
                                        $v     = post_origin($v, $key);
                                        $ids[] = $v;
                                    }
                                }
                            }
                            if (!empty($ids)) {
                                $tax_query[] = [
                                    'taxonomy'  => $key,
                                    'terms'     => $ids,
                                    //'COMPARE' => "IN",
                                    'operator'  => 'IN',
                                ];
                            }
                            $ids = [];
                        }
                    }
                    $query->set('tax_query', $tax_query);
                }

                if (!empty($ts_search_args['ts_number_ht'])) {
                    $query->set('posts_per_page', $ts_search_args['ts_number_ht']);
                }
                if (!empty($ts_search_args['ts_ids'])) {
                    $query->set('post__in', explode(',', $ts_search_args['ts_ids']));
                    $query->set('orderby', 'post__in');
                }
                if (!empty($ts_search_args['posts_per_page'])) {
                    $query->set('posts_per_page', $ts_search_args['posts_per_page']);
                }
                if (!empty($ts_search_args['ts_orderby']) and $ts_orderby = $ts_search_args['ts_orderby']) {
                    if ($ts_orderby == 'sale') {
                        $query->set('meta_key', 'total_sale_number');
                        $query->set('orderby', 'meta_value_num');
                    }
                    if ($ts_orderby == 'rate') {
                        $query->set('meta_key', 'rate_review');
                        $query->set('orderby', 'meta_value');
                    }
                    if ($ts_orderby == 'discount') {
                        $query->set('meta_key', 'discount_rate');
                        $query->set('orderby', 'meta_value_num');
                    }
                    if ($ts_orderby == 'featured') {
                        $query->set('meta_key', 'is_featured');
                        $query->set('orderby', 'meta_value');
                        $query->set('order', 'DESC');
                    }
                }
                if (!empty($ts_search_args['sort_taxonomy']) and $sort_taxonomy = $ts_search_args['sort_taxonomy']) {
                    if (isset($ts_search_args["id_term_" . $sort_taxonomy])) {
                        $id_term = $ts_search_args["id_term_" . $sort_taxonomy];
                        $tax_query[] = [
                            [
                                'taxonomy'         => $sort_taxonomy,
                                'field'            => 'id',
                                'terms'            => explode(',', $id_term),
                                'include_children' => false
                            ],
                        ];
                    }
                }
                if (!empty($meta_query)) {
                    $query->set('meta_query', $meta_query);
                }
                if (!empty($tax_query)) {
                    $query->set('tax_query', $tax_query);
                }
            }
        }
        function _get_order_by_query($orderby) {
            if (strpos($orderby, "FIELD(") !== false && (strpos($orderby, "posts.ID") !== false)) {
                return $orderby;
            }
            if ($check = get('orderby')) {
                global $wpdb;
                $meta_key = 'avg_price';
                if ($meta_key == 'avg_price') $meta_key = "price_avg";
                switch ($check) {
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
                        $is_featured = 'off'; //is_featured_search_hotel
                        if (!empty($is_featured) and $is_featured == 'on') {
                            $orderby = 'tb2.is_featured desc';
                        } else {
                            $orderby = $wpdb->posts . '.post_modified desc';
                        }
                        break;
                }
            } else {
                global $wpdb;
                $is_featured = 'off'; // is_featured_search_hotel
                if (!empty($is_featured) and $is_featured == 'on') {
                    $orderby = 'tb2.is_featured desc';
                } else {
                    $orderby = $wpdb->posts . '.post_modified desc';
                }
            }
            return $orderby;
        }
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
            $price = get_post_meta( $post_id, 'price_avg', true );
            $price = apply_filters( 'ts_apply_tax_amount', $price );
            return $price;
        }
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
        static function is_show_min_price() {
            $show_min_or_avg = 'avg_price';
            if ( $show_min_or_avg == 'min_price' ) return true;
            return true;
        }
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
            $meta_key = 'avg_price';
            if ($meta_key == 'avg_price') $meta_key = "price_avg";

            if ( empty( $post_type ) || !TravelHelper::checkTableDuplicate( $post_type ) ) {
                return [ 'price_min' => 0, 'price_max' => 500 ];
            }

            global $wpdb;
            $sql = "
                select
                    min(CAST({$meta_key} as DECIMAL)) as min,
                    max(CAST({$meta_key} as DECIMAL)) as max
                from {$wpdb->prefix}ts_hotel";

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
                $where .= " AND {$wpdb->prefix}posts.ID IN (SELECT post_id FROM {$wpdb->prefix}ts_location_relationships WHERE 1=1 AND location_from IN ({$location_ids}) AND post_type IN ('ts_hotel')) ";
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
            $r         = [];
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
        }
        static function get_cart_item_total( $item_id, $item ) {
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
        static function _get_location_by_name($location_name) {
            if (empty($location_name))
                return $location_name;
            $ids = [];
            global $wpdb; //OR (" . $wpdb->posts . ".post_content LIKE '%" . $location_name . "%')

            if (defined('ICL_LANGUAGE_CODE')) {
                $query = "SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->posts . ".ID
                FROM " . $wpdb->posts . "
                JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->prefix}posts.ID = t.element_id
                AND t.element_type = 'post_location'
                JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l. CODE
                AND l.active = 1
                WHERE 1=1
                AND t.language_code = '" . ICL_LANGUAGE_CODE . "'
                AND (((" . $wpdb->posts . ".post_title LIKE '%" . $location_name . "%') ))
                AND " . $wpdb->posts . ".post_type = 'location'
                AND ((" . $wpdb->posts . ".post_status = 'publish' OR " . $wpdb->posts . ".post_status = 'pending'))
                ORDER BY " . $wpdb->posts . ".post_title LIKE '%" . $location_name . "%' DESC, " . $wpdb->posts . ".post_date DESC LIMIT 0, 10";
            } else {
                $query = "SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->posts . ".ID
                FROM " . $wpdb->posts . "
                WHERE 1=1
                AND (((" . $wpdb->posts . ".post_title LIKE '%" . $location_name . "%') ))
                AND " . $wpdb->posts . ".post_type = 'location'
                AND ((" . $wpdb->posts . ".post_status = 'publish' OR " . $wpdb->posts . ".post_status = 'pending'))
                ORDER BY " . $wpdb->posts . ".post_title LIKE '%" . $location_name . "%' DESC, " . $wpdb->posts . ".post_date DESC LIMIT 0, 10";
            }
            $data = $wpdb->get_results($query, OBJECT);
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $ids[] = $v->ID;
                }
            }

            return $ids;
        }

        static function inst() {
            if (empty(self::$_inst)) {
                self::$_inst = new self();
            }
            return self::$_inst;
        }
    }

    TSHotel::inst();
}
