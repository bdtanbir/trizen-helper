<?php

if ( !class_exists( 'AvailabilityHelper' ) ) {
    class AvailabilityHelper {
        public function __construct()
        {

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
    }

    new AvailabilityHelper();
}


