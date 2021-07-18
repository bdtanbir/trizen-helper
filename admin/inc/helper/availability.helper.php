<?php

if ( !class_exists( 'AvailabilityHelper' ) ) {
    class AvailabilityHelper {
        public function __construct()
        {
            if (is_admin()) {
               add_action( 'wp_ajax_ts_get_availability_hotel', [&$this, '_get_availability_hotel'] );
            }

            add_action( 'wp_ajax_ts_add_custom_price', [$this, '_add_custom_price'] );

            add_action( 'wp_ajax_trizen_calendar_bulk_edit_form', [$this, 'trizen_calendar_bulk_edit_form'] );
            // Update Status
            add_action('ts_booking_change_status', array(__CLASS__,'_ts_booking_change_status'),10,3);

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
                    case "ts_hotel":
                    case "hotel_room":
                        $table  = $wpdb->prefix . 'ts_room_availability';
                        if (!empty($data['room_origin'])) {
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
                            if(get_post_meta($data['ts_booking_id'],'allow_full_day',true) == 1){
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

        public function insert_calendar_bulk($data, $posts_per_page, $total, $current_page, $all_days, $post_id) {
            $post_type = get_post_type($post_id);
            $table = '';
            switch ($post_type) {
                case 'hotel_room':
                    $table = 'ts_room_availability';
                    break;
            }
            $start = ($current_page - 1) * $posts_per_page;
            $end   = ($current_page - 1) * $posts_per_page + $posts_per_page - 1;
            if ($end > $total - 1) $end = $total - 1;
            if ($data['groupday'] == 0) {
                for ($i = $start; $i <= $end; $i++) {
                    $data['start'] = $all_days[$i];
                    $data['end']   = $all_days[$i];
                    /*  Delete old item */
                    $result = $this->trizen_get_availability($post_id, $all_days[$i], $all_days[$i], $table);
                    $split = $this->trizen_split_availability($result, $all_days[$i], $all_days[$i]);
                    if (isset($split['delete']) && !empty($split['delete'])) {
                        foreach ($split['delete'] as $item) {
                            $this->trizen_delete_availability($item['id'], $table);
                        }
                    }
                    /*  .End */
                    if (!isset($data['starttime']))
                        $data['starttime'] = '';
                    $this->trizen_insert_availability($data['post_id'], $data['start'], $data['end'], $data['price'], $data['adult_price'], $data['children_price'], $data['infant_price'], $data['starttime'], $data['status'], $data['groupday'], $table);
                }
            } else {
                for ($i = $start; $i <= $end; $i++) {
                    $data['start'] = $all_days[$i]['min'];
                    $data['end'] = $all_days[$i]['max'];
                    /*  Delete old item */
                    $result = $this->trizen_get_availability($post_id, $all_days[$i]['min'], $all_days[$i]['max'], $table);
                    $split = $this->trizen_split_availability($result, $all_days[$i]['min'], $all_days[$i]['max']);
                    if (isset($split['delete']) && !empty($split['delete'])) {
                        foreach ($split['delete'] as $item) {
                            $this->trizen_delete_availability($item['id'], $table);
                        }
                    }
                    /*  .End */
                    if (!isset($data['starttime']))
                        $data['starttime'] = '';
                    $this->trizen_insert_availability($data['post_id'], $data['start'], $data['end'], $data['price'], $data['adult_price'], $data['children_price'], $data['infant_price'], $data['starttime'], $data['status'], $data['groupday'], $table);
                }
            }
            $next_page = (int)$current_page + 1;
            $progress = ($current_page / $total) * 100;
            $return = [
                'all_days' => $all_days,
                'current_page' => $next_page,
                'posts_per_page' => $posts_per_page,
                'total' => $total,
                'status' => 2,
                'data' => $data,
                'progress' => $progress,
                'post_id' => $post_id,
            ];
            return $return;
        }
        public function trizen_delete_availability($id = '', $table = null)
        {
            if (empty($table))
                $table = 'ts_availability';
            global $wpdb;
            $table = $wpdb->prefix . $table;
            $wpdb->delete(
                $table,
                [
                    'id' => $id
                ]
            );
        }
        public function trizen_insert_availability($post_id = '', $check_in = '', $check_out = '', $price = '', $adult_price = '', $children_price = '', $infant_price = '', $starttime = '', $status = '', $group_day = '', $table = null) {
            if (empty($table))
                $table = 'ts_availability';
            global $wpdb;
            if ($group_day == 1) {
                $data_insert = [
                    'post_id'      => $post_id,
                    'check_in'     => $check_in,
                    'check_out'    => $check_out,
                    'price'        => $price,
                    'adult_price'  => $adult_price,
                    'child_price'  => $children_price,
                    'infant_price' => $infant_price,
                    'status'       => $status,
                    'groupday'     => 1,
                ];
                if ($table == 'ts_rental_availability') {
                    unset($data_insert['adult_price']);
                    unset($data_insert['child_price']);
                    unset($data_insert['infant_price']);
                }
                if ( $table=='ts_room_availability' ) {
                    unset($data_insert['infant_price']);
                }
                if ($table == 'ts_room_availability') {
                    $parent_id = get_post_meta($post_id, 'room_parent', true);
                    if(get_post_meta($post_id, 'allow_full_day', true) == 1) {
                        $allowed_fullday = 'on';
                    } else {
                        $allowed_fullday = 'off';
                    }
                    unset($data_insert['groupday']);
                    $data_insert['post_type']      = 'hotel_room';
                    $data_insert['number']         = get_post_meta($post_id, 'number_room', true);
                    $data_insert['allow_full_day'] = $allowed_fullday;
                    $data_insert['booking_period'] = get_post_meta($parent_id, 'hotel_booking_period', true);
                    $data_insert['adult_number']   = get_post_meta($post_id, 'adult_number', true);
                    $data_insert['child_number']   = get_post_meta($post_id, 'children_number', true);
                    $data_insert['is_base']        = 0;
                    $data_insert['parent_id']      = $parent_id;
                }
                $wpdb->insert(
                    $wpdb->prefix . $table,
                    $data_insert
                );
            } else {
                for ($i = $check_in; $i <= $check_out; $i = strtotime('+1 day', $i)) {
                    $data_insert = [
                        'post_id'      => $post_id,
                        'check_in'     => $i,
                        'check_out'    => $i,
                        'price'        => $price,
                        'adult_price'  => $adult_price,
                        'child_price'  => $children_price,
                        'infant_price' => $infant_price,
                        'status'       => $status,
                        'groupday'     => 0,
                    ];
                    if ($table == 'ts_rental_availability') {
                        unset($data_insert['adult_price']);
                        unset($data_insert['child_price']);
                        unset($data_insert['infant_price']);
                    }
                    if ( $table=='ts_room_availability' ) {
                        unset($data_insert['infant_price']);
                    }
                    if ($table == 'ts_room_availability') {
                        $parent_id = get_post_meta($post_id, 'room_parent', true);
                        if(get_post_meta($post_id, 'allow_full_day', true) == 1) {
                            $allowed_fullday = 'on';
                        } else {
                            $allowed_fullday = 'off';
                        }
                        unset($data_insert['groupday']);
                        $data_insert['post_type'] = 'hotel_room';
                        $data_insert['number'] = get_post_meta($post_id, 'number_room', true);
                        $data_insert['allow_full_day'] = $allowed_fullday;
                        $data_insert['booking_period'] = get_post_meta($parent_id, 'hotel_booking_period', true);
                        $data_insert['adult_number'] = get_post_meta($post_id, 'adult_number', true);
                        $data_insert['child_number'] = get_post_meta($post_id, 'children_number', true);
                        $data_insert['is_base'] = 0;
                        $data_insert['parent_id'] = $parent_id;
                    }
                    $wpdb->insert(
                        $wpdb->prefix . $table,
                        $data_insert
                    );
                }
            }
            return (int)$wpdb->insert_id;
        }
        public function trizen_split_availability($result = [], $check_in = '', $check_out = '')
        {
            $return = [];
            if (!empty($result)) {
                foreach ($result as $item) {
                    $check_in = (int)$check_in;
                    $check_out = (int)$check_out;
                    if (isset($item['start']) && isset($item['start'])) {
                        $start = strtotime($item['start']);
                        $end = strtotime('-1 day', strtotime($item['end']));
                        if ($start < $check_in && $end >= $check_in) {
                            $return['insert'][] = [
                                'post_id' => $item['post_id'],
                                'check_in' => strtotime($item['check_in']),
                                'check_out' => strtotime('-1 day', $check_in),
                                'price' => (float)$item['price'],
                                'status' => $item['status'],
                                'groupday' => $item['groupday'],
                            ];
                        }
                        if ($start <= $check_out && $end > $check_out) {
                            $return['insert'][] = [
                                'post_id' => $item['post_id'],
                                'check_in' => strtotime('+1 day', $check_out),
                                'check_out' => strtotime('-1 day', strtotime($item['check_out'])),
                                'price' => (float)$item['price'],
                                'status' => $item['status'],
                                'groupday' => $item['groupday'],
                            ];
                        }
                    }
                    $return['delete'][] = [
                        'id' => $item['id']
                    ];
                }
            }
            return $return;
        }

        public function trizen_get_availability($post_id = '', $check_in = '', $check_out = '', $table = null) {
            if (empty($table))
                $table = 'ts_availability';
            global $wpdb;
            $table = $wpdb->prefix . $table;
            $sql   = "SELECT * FROM {$table} WHERE post_id = {$post_id} AND ( ( CAST( check_in AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED) AND CAST( check_in AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) OR ( CAST( check_out AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED ) AND ( CAST( check_out AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) ) )";
            $result = $wpdb->get_results($sql, ARRAY_A);
            $return = [];
            if (!empty($result)) {
                foreach ($result as $item) {
                    $return[] = [
                        'id'             => $item['id'],
                        'post_id'        => $item['post_id'],
                        'check_in'       => date('Y-m-d', $item['check_in']),
                        'check_out'      => date('Y-m-d', strtotime('+1 day', $item['check_out'])),
                        'price'          => (float)$item['price'],
                        'adult_price'    => isset($item['adult_price']) ? (float)$item['adult_price'] : '',
                        'children_price' => isset($item['child_price']) ? (float)$item['child_price'] : '',
                        'infant_price'   => isset($item['infant_price']) ? (float)$item['infant_price'] : '',
                        'status'         => $item['status'],
                        'groupday'       => isset($item['groupday']) ? $item['groupday'] : '',
                    ];
                }
            }
            return $return;
        }

        function _get_availability_hotel() {
            $results       = [];
            $post_id       = $_REQUEST['post_id'];
            $post_id       = post_origin($post_id);
            $check_in      = $_REQUEST['start'];
            $check_out     = $_REQUEST['end'];
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


        public function trizen_calendar_bulk_edit_form() {
            $post_id = (int)post('post_id', 0);
            if ($post_id > 0) {
                if (isset($_POST['all_days']) && !empty($_POST['all_days'])) {
                    $data           = post('data', '');
                    $all_days       = post('all_days', '');
                    $posts_per_page = (int)post('posts_per_page', '');
                    $current_page   = (int)post('current_page', '');
                    $total          = (int)post('total', '');
                    if ($current_page > ceil($total / $posts_per_page)) {
                        echo json_encode([
                            'status' => 1,
                            'message' => '<div class="text-success">' . __('Added successful.', 'trizen-helper') . '</div>'
                        ]);
                        die;
                    } else {
                        $return = $this->insert_calendar_bulk($data, $posts_per_page, $total, $current_page, $all_days, $post_id);
                        echo json_encode($return);
                        die;
                    }
                }
                $day_of_week  = post('day-of-week', '');
                $day_of_month = post('day-of-month', '');
                $array_month  = [
                    'January'   => '1',
                    'February'  => '2',
                    'March'     => '3',
                    'April'     => '4',
                    'May'       => '5',
                    'June'      => '6',
                    'July'      => '7',
                    'August'    => '8',
                    'September' => '9',
                    'October'   => '10',
                    'November'  => '11',
                    'December'  => '12',
                ];
                $months         = post('months', '');
                $years          = post('years', '');
                $price          = post('price_bulk', 0);
                $adult_price    = post('adult-price_bulk', 0);
                $children_price = post('children-price_bulk', 0);
                $infant_price   = post('infant-price_bulk', 0);
                if ($price == '')
                    $price = 0;
                if ($adult_price == '')
                    $adult_price = 0;
                if ($children_price == '')
                    $children_price = 0;
                if ($infant_price == '')
                    $infant_price = 0;
                $start_time_arr = request('starttime', '');
                $start_time_str = '';
                if (isset($start_time_arr) && !empty($start_time_arr))
                    $start_time_str = implode(', ', $start_time_arr);
                if (!is_numeric($price) || !is_numeric($adult_price) || !is_numeric($children_price) || !is_numeric($infant_price)) {
                    echo json_encode([
                        'status'  => 0,
                        'message' => '<div class="text-error">' . __('The price field is not a number.', 'trizen-helper') . '</div>'
                    ]);
                    die;
                }
                $price          = (float)$price;
                $adult_price    = (float)$adult_price;
                $children_price = (float)$children_price;
                $infant_price   = (float)$infant_price;
                $status         = post('status', 'available');
                $group_day      = post('calendar_groupday', 0);
                /*  Start, End is a timestamp */
                $all_years  = [];
                $all_months = [];
                $all_days   = [];
                if (!empty($years)) {
                    sort($years, 1);
                    foreach ($years as $year) {
                        $all_years[] = $year;
                    }
                    if (!empty($months)) {
                        foreach ($months as $month) {
                            foreach ($all_years as $year) {
                                $all_months[] = $month . ' ' . $year;
                            }
                        }
                        if (!empty($day_of_week) && !empty($day_of_month)) {
                            // Each day in month
                            foreach ($day_of_month as $day) {
                                // Each day in week
                                foreach ($day_of_week as $day_week) {
                                    // Each month year
                                    foreach ($all_months as $month) {
                                        $time = strtotime($day . ' ' . $month);
                                        if (date('l', $time) == $day_week) {
                                            $all_days[] = $time;
                                        }
                                    }
                                }
                            }
                        } elseif (empty($day_of_week) && empty($day_of_month)) {
                            foreach ($all_months as $month) {
                                for ($i = strtotime('first day of ' . $month); $i <= strtotime('last day of ' . $month); $i = strtotime('+1 day', $i)) {
                                    $all_days[] = $i;
                                }
                            }
                        } elseif (empty($day_of_week) && !empty($day_of_month)) {
                            foreach ($day_of_month as $day) {
                                foreach ($all_months as $month) {
                                    $month_tmp = trim($month);
                                    $month_tmp = explode(' ', $month);
                                    //$num_day = date('t', mktime(0, 0, 0, $array_month[ $month_tmp[ 0 ] ], 1, $month_tmp[ 1 ]));
                                    $num_day = cal_days_in_month(CAL_GREGORIAN, $array_month[$month_tmp[0]], $month_tmp[1]);
                                    if ($day <= $num_day) {
                                        $all_days[] = strtotime($day . ' ' . $month);
                                    }
                                }
                            }
                        } elseif (!empty($day_of_week) && empty($day_of_month)) {
                            foreach ($day_of_week as $day) {
                                foreach ($all_months as $month) {
                                    for ($i = strtotime('first ' . $day . ' of ' . $month); $i <= strtotime('last ' . $day . ' of ' . $month); $i = strtotime('+1 week', $i)) {
                                        $all_days[] = $i;
                                    }
                                }
                            }
                        }
                        if (!empty($all_days)) {
                            $posts_per_page = 10;
                            if ($group_day == 1) {
                                $all_days = $this->change_allday_to_group($all_days);
                            }
                            $total        = count($all_days);
                            $current_page = 1;
                            $data = [
                                'post_id'        => $post_id,
                                'status'         => $status,
                                'groupday'       => $group_day,
                                'price'          => $price,
                                'adult_price'    => $adult_price,
                                'children_price' => $children_price,
                                'infant_price'   => $infant_price,
                            ];
                            if ($start_time_str != '')
                                $data['starttime'] = $start_time_str;
                            $return = $this->insert_calendar_bulk($data, $posts_per_page, $total, $current_page, $all_days, $post_id);
                            echo json_encode($return);
                            die;
                        }
                    } else {
                        echo json_encode([
                            'status'  => 0,
                            'message' => '<div class="text-error">' . __('The months field is required.', 'trizen-helper') . '</div>'
                        ]);
                        die;
                    }
                } else {
                    echo json_encode([
                        'status'  => 0,
                        'message' => '<div class="text-error">' . __('The years field is required.', 'trizen-helper') . '</div>'
                    ]);
                    die;
                }
            } else {
                echo json_encode([
                    'status'  => 0,
                    'message' => '<div class="text-error">' . __('The room field is required.', 'trizen-helper') . '</div>'
                ]);
                die;
            }
        }
        public function change_allday_to_group($all_days = []) {
            $return_tmp = [];
            $return     = [];
            foreach ($all_days as $item) {
                $month = date('m', $item);
                if (!isset($return_tmp[$month])) {
                    $return_tmp[$month]['min'] = $item;
                    $return_tmp[$month]['max'] = $item;
                } else {
                    if ($return_tmp[$month]['min'] > $item) {
                        $return_tmp[$month]['min'] = $item;
                    }
                    if ($return_tmp[$month]['max'] < $item) {
                        $return_tmp[$month]['max'] = $item;
                    }
                }
            }
            foreach ($return_tmp as $key => $val) {
                $return[] = [
                    'min' => $val['min'],
                    'max' => $val['max'],
                ];
            }
            return $return;
        }

        /* Available activity by month */
        public static function get_current_availability($post_id, $max_people) {
            global $wpdb;
            $post_type = get_post_type($post_id);
            $where_book_limit = '';
            if ($max_people > 0) {
                if($post_type == 'hotel_room'){
                    $where_book_limit = " AND number_booked < number ";
                }
            }
            if($post_type == 'hotel_room'){
                $table = $wpdb->prefix . 'ts_room_availability';
                $hotel_id = get_post_meta($post_id, 'room_parent', true);
                if(!empty($hotel_id)){
                    $booking_period = intval(get_post_meta($hotel_id, 'hotel_booking_period', true));
                }else{
                    $booking_period = 0;
                }
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
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __('The price field is not a number.', 'trizen-helper')
                        ]);
                        die();
                    }
                }
            }
            $price       = floatval(request('calendar_price', 0));
            $post_id     = request('calendar_post_id', '');
            $post_id     = post_origin($post_id);
            $adult_price = floatval( request( 'calendar_adult_price', '' ) );
            $child_price = floatval( request( 'calendar_child_price', '' ) );
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
                    'adult_price' => $adult_price,
                    'child_price' => $child_price,
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
    }

    new AvailabilityHelper();
}


