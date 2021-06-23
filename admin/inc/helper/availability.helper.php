<?php

if ( !class_exists( 'AvailabilityHelper' ) ) {
    class AvailabilityHelper {
        public function __construct()
        {
            if (is_admin()) {
                add_action( 'wp_ajax_ts_get_availability_hotel', [&$this, '_get_availability_hotel'] );
            }

            // Update Status
            add_action('ts_booking_change_status', array(__CLASS__,'_ts_booking_change_status'),10,3);

            add_action( 'wp_ajax_ts_add_custom_price', [ $this, '_add_custom_price' ] );

            add_action('woocommerce_order_status_changed',array(__CLASS__,'_woocommerce_order_status_changed'),10,3);
        }

        static function _getDisableCustomDate($room_id, $month, $month2, $year, $year2, $date_format = false)
        {
            $date1 = $year . '-' . $month . '-01';
            $date2 = strtotime($year2 . '-' . $month2 . '-01');
            $date2 = date('Y-m-t', $date2);
            $date_time_format = getDateFormat();
            if (!empty($date_format)) {
                $date_time_format = $date_format;
            }
            global $wpdb;
            $sql = "
                    SELECT
                        `check_in`,
                        `check_out`,
                        `number`,
                        `status`,
                        `priority`
                    FROM
                        {$wpdb->prefix}ts_room_availability
                    WHERE
                        post_id = {$room_id}
                    AND (
                        (
                            '{$date1}' < DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND '{$date2}' > DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                        OR (
                            '{$date1}' BETWEEN DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                        OR (
                            '$date2' BETWEEN DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                    )";
            $results       = $wpdb->get_results($sql);
            $default_state = get_post_meta($room_id, 'default_state', true);
            if (!$default_state) $default_state = 'available';
            $list_date = [];
            $start     = strtotime($date1);
            $end       = strtotime($date2);
            if (is_array($results) && count($results)) {
                for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                    $in_date = false;
                    foreach ($results as $key => $val) {
                        $status = $val->status;
                        if ($i == $val->check_in && $i == $val->check_out) {
                            if ($status == 'unavailable') {
                                $date = $i;
                            } else {
                                unset($date);
                            }
                            if (!$in_date) {
                                $in_date = true;
                            }
                        }
                    }
                    if ($in_date && isset($date)) {
                        $list_date[] = date($date_time_format, $date);
                        unset($date);
                    } else {
                        if (!$in_date && $default_state == 'not_available') {
                            $list_date[] = date($date_time_format, $i);
                            unset($in_date);
                        }
                    }
                }
            } else {
                if ($default_state == 'not_available') {
                    for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                        $list_date[] = date($date_time_format, $i);
                    }
                }
            }
            return $list_date;
        }

        public static function syncAvailabilityOrder($data){
            if($data['ts_booking_id']) {
                global $wpdb;
                $post_type=$data['ts_booking_post_type'];
                $table  = $wpdb->prefix . 'ts_availability';

                switch ($post_type) {
                    case 'ts_tours':
                    case 'ts_activity':
                        $table = $wpdb->prefix . 'ts_tour_availability';
                        if($post_type == 'ts_activity'){
                            $table = $wpdb->prefix . 'ts_activity_availability';
                        }
                        $booked = ($data['adult_number'] + $data['child_number'] + $data['infant_number']);
                        $sql = $wpdb->prepare("UPDATE {$table} SET number_booked = IFNULL(number_booked, 0) + %d WHERE post_id = %d AND check_in = %s", $booked, $data['ts_booking_id'], $data['check_in_timestamp']);
                        $wpdb->query( $sql );
                        break;
                    case "ts_hotel":
                    case "hotel_room":
                        $table  = $wpdb->prefix . 'ts_room_availability';
                        if (!empty($data['room_origin']))
                        {
                            $booked =  !empty($data['room_num_search'])?intval($data['room_num_search']):1;

                            $booked_temp = $booked;

                            for ( $i = $data['check_in_timestamp']; $i <= $data['check_out_timestamp']; $i = strtotime( '+1 day', $i ) ) {
                                /*if($i > $data['check_in_timestamp'] and $i < $data['check_out_timestamp'] and get_post_meta($data['ts_booking_id'],'allow_full_day',true) != 'off'){
                                    $booked = $booked * 2;
                                }else{
                                    $booked = $booked_temp;
                                }*/
                                $sql = $wpdb->prepare("UPDATE {$table} SET number_booked = IFNULL(number_booked, 0) + %d WHERE post_id = %d AND check_in = %s",$booked,$data['room_origin'],$i);
                                $wpdb->query( $sql );
                            }

                            // Check allowed to set Number End
                            if(get_post_meta($data['ts_booking_id'],'allow_full_day',true)!='off'){
                                for ( $i = $data['check_in_timestamp']; $i <= $data['check_out_timestamp']; $i = strtotime( '+1 day', $i ) ) {
                                    $sql = $wpdb->prepare("UPDATE {$table} SET number_end = IFNULL(number_end, 0) + %d WHERE post_id = %d AND check_in = %s",$booked,$data['room_origin'],$i);
                                    $wpdb->query( $sql );
                                }
                            }

                        }

                        break;
                }

            }
            //  update set number_booked=number_booked+2
        }

        function _get_availability_hotel() {
            $results       = [];
            $post_id       = request('post_id', '');
            $post_id       = post_origin($post_id);
            $check_in      = request('start', '');
            $check_out     = request('end', '');
            $price_ori     = floatval(get_post_meta($post_id, 'price', true));
            $default_state = get_post_meta($post_id, 'default_state', true);
            $number_room   = intval(get_post_meta($post_id, 'number_room', true));
            if (get_post_type($post_id) == 'hotel_room') {
                $data = self::_getdataHotel($post_id, $check_in, $check_out);
                for ($i = intval($check_in); $i <= intval($check_out); $i = strtotime('+1 day', $i)) {
                    $in_date = false;
                    if (is_array($data) && count($data)) {
                        foreach ($data as $key => $val) {
                            if ($i >= intval($val->check_in) && $i <= intval($val->check_out)) {
                                $status = $val->status;
                                if ($status != 'unavailable') {
                                    $item = [
                                        'price'   => floatval($val->price),
                                        'start'   => date('Y-m-d', $i),
                                        'title'   => get_the_title($post_id),
                                        'item_id' => $val->id,
                                        'status'  => $val->status,
                                    ];
                                } else {
                                    unset($item);
                                }
                                if (!$in_date)
                                    $in_date = true;
                            }
                        }
                    }
                    if (isset($item)) {
                        $results[] = $item;
                        unset($item);
                    }
                    if (!$in_date && ($default_state == 'available' || !$default_state)) {
                        $item_ori = [
                            'price'  => $price_ori,
                            'start'  => date('Y-m-d', $i),
                            'title'  => get_the_title($post_id),
                            'number' => $number_room,
                            'status' => 'available',
                        ];
                        $results[] = $item_ori;
                        unset($item_ori);
                    }
                    if (!$in_date) {
                        $parent_id = get_post_meta($post_id, 'room_parent', true);
                        TS_Hotel_Room_Availability::inst()->insertOrUpdate([
                            'post_id'   => $post_id,
                            'check_in'  => $i,
                            'check_out' => $i,
                            'status'    => (!$default_state or $default_state == 'available') ? 'available' : 'unavailable',
                            'is_base'   => 1,
                            'price'     => $price_ori,
                            'post_type' => 'hotel_room',
                            'parent_id' => $parent_id,
                        ]);
                    }
                }
            }
            echo json_encode($results);
            die();
        }

        static function _getdataHotel( $post_id, $check_in, $check_out ) {
            global $wpdb;
            $sql     = "
			SELECT
				`id`,
				`post_id`,
				`post_type`,
				`check_in`,
				`check_out`,
				`number`,
				`price`,
                `status`,
                `adult_price`,
                `child_price`
			FROM
				{$wpdb->prefix}ts_room_availability
			WHERE
			post_id = %d
			AND post_type='hotel_room'
			AND check_in >=%d and check_in <=%d";
            $results = $wpdb->get_results($wpdb->prepare($sql, [$post_id, $check_in, $check_out]));
            return $results;
        }

        public function _add_custom_price() {
            $check_in  = request('calendar_check_in', '');
            $check_out = request('calendar_check_out', '');
            if (empty($check_in) || empty($check_out)) {
                echo json_encode([
                    'type' => 'error',
                    'status' => 0,
                    'message' => __('The check in or check out field is not empty.', 'trizen-helper')
                ]);
                die();
            }
            $check_in  = strtotime($check_in);
            $check_out = strtotime($check_out);
            if ($check_in > $check_out) {
                echo json_encode([
                    'type'    => 'error',
                    'status'  => 0,
                    'message' => __('The check out is later than the check in field.', 'trizen-helper')
                ]);
                die();
            }
            $status = request('calendar_status', 'available');
            if ($status == 'available') {
                if ( request( 'price_by_per_person', false ) == 'true' ) {
                    if ( filter_var( $_POST[ 'calendar_adult_price' ], FILTER_VALIDATE_FLOAT ) === false ) {
                        echo json_encode( [
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __( 'The adult price field is not a number.', 'trizen-helper' )
                        ] );
                        die();
                    }
                    if ( filter_var( $_POST[ 'calendar_child_price' ], FILTER_VALIDATE_FLOAT ) === false ) {
                        echo json_encode( [
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __( 'The child price field is not a number.', 'trizen-helper' )
                        ] );
                        die();
                    }
                } else {
                    if (filter_var($_POST['calendar_price'], FILTER_VALIDATE_FLOAT) === false) {
                        echo json_encode([
                            'type' => 'error',
                            'status' => 0,
                            'message' => __('The price field is not a number.', 'trizen-helper')
                        ]);
                        die();
                    }
                }
            }
            $price       = floatval(request('calendar_price', 0));
            $post_id     = request('calendar_post_id', '');
            $post_id     = post_origin($post_id);
//            $adult_price = floatval( request( 'calendar_adult_price', '' ) );
//            $child_price = floatval( request( 'calendar_child_price', '' ) );
            $parent_id   = get_post_meta($post_id, 'room_parent', true);
            for ($i = $check_in; $i <= $check_out; $i = strtotime('+1 day', $i)) {
                $data = [
                    'post_id'     => $post_id,
                    'post_type'   => 'hotel_room',
                    'check_in'    => $i,
                    'check_out'   => $i,
                    'price'       => $price,
                    'status'      => $status,
                    'parent_id'   => $parent_id,
                    'is_base'     => 0,
//                    'adult_price' => $adult_price,
//                    'child_price' => $child_price,
                ];
                TS_Hotel_Room_Availability::inst()->insertOrUpdate($data);
            }
            echo json_encode([
                'type'    => 'success',
                'status'  => 1,
                'message' => __('Successfully', 'trizen-helper')
            ]);
            die();
        }

        /* Available activity by month */
        public static function get_current_availability($post_id, $max_people) {
            global $wpdb;
            $post_type = get_post_type($post_id);
            $where_book_limit = '';
            if ($max_people > 0) {
                if($post_type == 'ts_tours' || $post_type == 'ts_activity'){
                    $where_book_limit = " AND number_booked < number * count_starttime ";
                }elseif($post_type == 'hotel_room' || $post_type == 'ts_rental'){
                    $where_book_limit = " AND number_booked < number ";
                }
            }
            if($post_type == 'ts_activity'){
                $table = $wpdb->prefix . 'ts_activity_availability';
                $booking_period = intval(get_post_meta($post_id, 'activity_booking_period', true));
            }elseif($post_type == 'ts_tours'){
                $table = $wpdb->prefix . 'ts_tour_availability';
                $booking_period = intval(get_post_meta($post_id, 'tours_booking_period', true));
            }elseif($post_type == 'hotel_room'){
                $table = $wpdb->prefix . 'ts_room_availability';
                $hotel_id = get_post_meta($post_id, 'room_parent', true);
                if(!empty($hotel_id)){
                    $booking_period = intval(get_post_meta($hotel_id, 'hotel_booking_period', true));
                }else{
                    $booking_period = 0;
                }
            }elseif($post_type == 'ts_rental'){
                $table = $wpdb->prefix . 'ts_rental_availability';
                $booking_period = intval(get_post_meta($post_id, 'rentals_booking_period', true));
            }
            $newCheckIn = strtotime('+ ' . $booking_period . ' day', strtotime(date('Y-m-d')));
            $sql = "
                    SELECT check_in
                    FROM
                    	{$table}
                    WHERE
                    	post_id = {$post_id}
                  		{$where_book_limit}
                  	AND
                  		status = 'available'
                  	AND
                  		check_in >= {$newCheckIn}
                  	ORDER BY
                  		check_in ASC
                  	LIMIT 1";
            $results = $wpdb->get_col($sql, 0);
            if (!empty($results)) {
                return date('Y-m-d', $results[0]);
            } else {
                return date('Y-m-d');
            }
        }
    }

    new AvailabilityHelper();
}


