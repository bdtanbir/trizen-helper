<?php
/**
 * @package    WordPress
 * @subpackage Traveler
 * @since      1.0
 *
 * Class STHotel
 *
 * Created by ShineTheme
 *
 */
if ( !class_exists( 'TSHotelHelper' ) ) {
    class TSHotelHelper {
        static $_inst;
        static $_instance;
        //Current Hotel ID
        private $hotel_id;

        protected $orderby;

        protected $post_type = 'ts_hotel';

        protected $template_folder = 'hotel';

        function __construct( $hotel_id = false ) {

        }

        function init() {
//            parent::init();

//            add_action('template_redirect', [$this, 'ajax_search_room'], 1);
//            add_action( 'wp_ajax_ajax_search_room', [ $this, 'ajax_search_room' ] );
//            add_action( 'wp_ajax_nopriv_ajax_search_room', [ $this, 'ajax_search_room' ] );
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
            //if (!TravelHelper::checkTableDuplicate('st_hotel')) return $join;
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

        /*function ajax_search_room() {
            if ( post( 'room_search' ) ) {
                $result = [
                    'status' => 1,
                    'data'   => "",
                ];
                $hotel_id              = get_the_ID();
                $post                  = request();
                $post[ 'room_parent' ] = $hotel_id;

                //Check Date
                $today          = date( 'm/d/Y' );
                $check_in       = convertDateFormat( $post[ 'start' ] );
                $check_out      = convertDateFormat( $post[ 'end' ] );
                $date_diff      = dateDiff( $check_in, $check_out );
                $booking_period = intval( get_post_meta( $hotel_id, 'hotel_booking_period', true ) );
                $period         = dateDiff( $today, $check_in );
                if ( $booking_period && $period < $booking_period ) {
                    $result = [
                        'status'  => 0,
                        'data'    => TRIZEN_HELPER_URI . 'inc/hotel/search/loop-room-none.php',
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
                        $result[ 'data' ] .= preg_replace( '/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_URI . 'inc/hotel/search/loop-room-item.php' );
                    }
                } else {
                    $result[ 'data' ] .= TRIZEN_HELPER_URI . 'inc/hotel/search/loop-room-none.php';
                }
                //echo $wp_query->request;
                $result[ 'paging' ] = TravelHelper::paging_room();
                wp_reset_query();
                echo json_encode( $result );
                die();
            }
        }*/

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }
            return self::$_inst;
        }

    }
    TSHotelHelper::inst();
}


