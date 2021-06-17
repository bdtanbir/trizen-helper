<?php

if ( !class_exists( 'AvailabilityHelper' ) ) {
    class AvailabilityHelper {
        public function __construct()
        {
            if (is_admin()) {
                add_action( 'wp_ajax_ts_get_availability_hotel', '_get_availability_hotel' );
            }

            // Update Status
            add_action('st_booking_change_status',array(__CLASS__,'_st_booking_change_status'),10,3);

            add_action('woocommerce_order_status_changed',array(__CLASS__,'_woocommerce_order_status_changed'),10,3);
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
            $results = $wpdb->get_results( $wpdb->prepare($sql,[$post_id,$check_in,$check_out]) );
            return $results;
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
                $data = _getdataHotel($post_id, $check_in, $check_out);
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
    }

    new AvailabilityHelper();
}


