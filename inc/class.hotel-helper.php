<?php

add_action('wp_ajax_ajax_search_room', 'ajax_search_room');
add_action('wp_ajax_nopriv_ajax_search_room', 'ajax_search_room');


function search_room( ) {
    alter_search_room_query();
    $arg = apply_filters('ts_ajax_search_room_arg', [
        'post_type'      => 'hotel_room',
        'posts_per_page' => -1,
    ]);
    $query = new WP_Query($arg);
    remove_search_room_query();
    return $query;
}


function alter_search_room_query() {
    add_filter( 'pre_get_posts', '_change_room_pre_get_posts' );
    add_filter( 'posts_where', '_alter_search_query_ajax' );
    add_action( 'posts_fields', '_room_change_post_fields' );
    add_filter( 'posts_join', '_room_get_join_query' );
    add_filter( 'posts_groupby', '_room_change_posts_groupby' );
}
function remove_search_room_query() {
    remove_filter( 'pre_get_posts', '_change_room_pre_get_posts' );
    remove_filter( 'posts_where', '_alter_search_query_ajax' );
    remove_action( 'posts_fields', '_room_change_post_fields' );
    remove_filter( 'posts_join', '_room_get_join_query' );
    remove_filter( 'posts_groupby', '_room_change_posts_groupby' );
}

function _change_room_pre_get_posts($query) {
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
function _room_change_post_fields( $fields ) {
    $fields .= ', SUM(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL)) as ts_price, COUNT(tb.id) as total_available ';
    return $fields;
}
function _room_change_posts_groupby($groupby) {
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
function _alter_search_query_ajax($where) {
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

function ajax_search_room() {
    check_ajax_referer('ts_frontend_security', 'security');
    $result = [
        'html'   => '',
        'status' => 1,
        'data'   => '',
    ];
    $post           = request();
    $hotel_id       = $post['room_parent'];
    $today          = date('m/d/Y');
    $check_in       = convertDateFormat($post['start']);
    $check_out      = convertDateFormat($post['end']);
    $date_diff      = dateDiff($check_in, $check_out);
    $booking_period = intval(get_post_meta($hotel_id, 'hotel_booking_period', true));
    $period         = dateDiff($today, $check_in);
    if ($booking_period && $period < $booking_period) {
        $result = [
            'status'  => 0,
            'html'    => "None Item from Booking_Period",
            'message' => sprintf(__('This hotel allow minimum booking is %d day(s)', 'trizen-helper'), $booking_period),
        ];
        echo json_encode($result);
        die;
    }
    if ($date_diff < 1) {
        $result = [
            'status'    => 0,
            'html'      => "None Item From Date dif",
            'message'   => __('Make sure your check-out date is at least 1 day after check-in. HOTEL Helper', 'trizen-helper'),
            'more-data' => $date_diff
        ];
        echo json_encode($result);
        die;
    }
    global $post;
    $old_post = $post;

    $hotel = new TSHotel();
    $query = $hotel->search_room();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
//                    $result['html'] .= preg_replace('/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-item.php');
            $result['html'] .= 'I am Working';
        }
    } else {
        $result['html'] .= "None Item";
    }
    wp_reset_postdata();
    $post = $old_post;
    echo json_encode($result);
    die();
}