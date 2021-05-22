<?php
/**
 * @since 1.0
 **/

if ( !class_exists( 'HotelHelper' ) ) {
    class HotelHelper
    {
        protected static $priceData = [];

        public function init() {

        }

        static function get_minimum_price_hotel( $hotel_id ) {
            if ( empty( $hotel_id ) ) $hotel_id = get_the_ID();
            $hotel_id = post_origin( $hotel_id, 'ts_hotel' );
            if(array_key_exists($hotel_id,self::$priceData)) return self::$priceData[$hotel_id];
            $min_price = get_post_meta( $hotel_id, 'min_price', true );

            if ( (float)$min_price == 0 ) {
                global $wpdb;
                //$rooms     = self::_getAllRoomHotelID( $hotel_id );
                $min_price = 0;
                $check_in  = request( 'start', '' );
                $check_out = request( 'end', '' );
                if ( empty( $check_in ) ) {
                    $check_in = date( 'm/d/Y' );
                } else {
                    $check_in = convertDateFormat( $check_in );
                }
                if ( !empty( $check_out ) ) {
                    $check_out = convertDateFormat( $check_out );
                }
                $room_num_search = request( 'room_num_search', 1 );
                if ( intval( $room_num_search ) <= 0 ) {
                    $room_num_search = 1;
                }

//                    $room_full_ordered = HotelHelper::_get_room_cant_book_by_id( $hotel_id, date( 'Y-m-d', strtotime( $check_in ) ), date( 'Y-m-d', strtotime( $check_out ) ), $room_num_search );
//
//                    if ( is_array( $rooms ) && count( $rooms ) ) {
//                        foreach ( $rooms as $room ) {
//                            if ( !in_array( $room, $room_full_ordered ) ) {
//                                $price = STPrice::getRoomPriceOnlyCustomPrice( $room, strtotime( $check_in ), strtotime( $check_out ), $room_num_search );
//                                if ( $min_price == 0 && $price > 0 ) {
//                                    $min_price = $price;
//                                }
//                                if ( $min_price > 0 && $min_price > $price && $price > 0 ) {
//                                    $min_price = $price;
//                                }
//                            }
//
//                        }
//                    }
                global $wpdb;
                $whereNumber="AND (number  - COALESCE(number_booked, 0)) >= %d";
                if(get_post_meta($hotel_id,'allow_full_day',true)=='off'){
                    $whereNumber="AND (number  - COALESCE(number_booked, 0) + COALESCE(number_end, 0)) >= %d";
                }

                if(!empty($check_out)) $whereNumber.=$wpdb->prepare(" AND check_out <= %s ",strtotime($check_out));
                $sql="
                        SELECT (
							CASE WHEN IFNULL( adult_price, 0) = 0 THEN price
							ELSE adult_price
							END
						) as min_price FROM {$wpdb->prefix}ts_room_availability
                        WHERE
                        parent_id = %d
                        AND check_in >= %s
                        {$whereNumber}
                        AND status = 'available'
                        LIMIT 1
                    ";

                $row=$wpdb->get_row($wpdb->prepare($sql,$hotel_id,strtotime($check_in),$room_num_search));
                if(isset($row->min_price)){
                    $min_price = $row->min_price;
                } else {
                    $min_price = 0;
                }
            }
            self::$priceData[$hotel_id]=$min_price;
            return $min_price;
        }
    }
    $hotelhelper = new HotelHelper();
    $hotelhelper->init();
}





