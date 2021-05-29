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

        static function _getAllRoomHotelID( $hotel_id ) {
            global $wpdb;
            if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                $sql = "SELECT
						{$wpdb->prefix}posts.ID
					FROM
						{$wpdb->prefix}posts
					INNER JOIN {$wpdb->prefix}postmeta as mt on mt.post_id = {$wpdb->prefix}posts.ID and mt.meta_key = 'room_parent'
					JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->prefix}posts.ID = t.element_id
					AND t.element_type = 'post_hotel_room'
					JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l. CODE
					AND l.active = 1
					where mt.meta_value = '{$hotel_id}'
					and post_type = 'hotel_room'
					and post_status = 'publish'
					AND t.language_code = '" . ICL_LANGUAGE_CODE . "'";
            } else {
                $sql = "SELECT
						{$wpdb->prefix}posts.ID
					FROM
						{$wpdb->prefix}posts
					INNER JOIN {$wpdb->prefix}postmeta as mt on mt.post_id = {$wpdb->prefix}posts.ID and mt.meta_key = 'room_parent'
					where
					mt.meta_value = '{$hotel_id}'
					and post_type = 'hotel_room'
					and post_status = 'publish'";
            }
            $rooms = $wpdb->get_col( $sql );
            return $rooms;
        }

        static function _hotelValidateByID( $hotel_id, $check_in, $check_out, $adult_num, $child_num, $number_room ) {
            $cant_book = [];
            global $wpdb;
            $rooms = HotelHelper::_getAllRoomHotelID( $hotel_id );
            //Unique room
            //$rooms = array_unique($rooms);
            if ( is_array( $rooms ) && count( $rooms ) ) {
                foreach ( $rooms as $room ) {
                    $default_state = get_post_meta( $room, 'default_state', true );
                    if ( !$default_state ) $default_state = 'available';

                    $number_room_ori = intval( get_post_meta( $room, 'number_room', true ) );
                    $room_price      = TSPrice::getRoomPriceOnlyCustomPrice( $room, $check_in, $check_out, 1 );

                    if ( $room_price <= 0 ) {
                        $cant_book[] = $room;
                    } else {
                        $adult_number = intval( get_post_meta( $room, 'adult_number', true ) );
                        $child_number = intval( get_post_meta( $room, 'children_number', true ) );
                        if ( $adult_number < $adult_num || $child_number < $child_num ) { // overload people
                            $cant_book[] = $room;
                        } else {
                            $data_room = AvailabilityHelper::_getdataHotel( $room, $check_in, $check_out );

                            if ( is_array( $data_room ) && count( $data_room ) ) {
                                $start = $check_in;
                                $end   = $check_out;
                                for ( $i = $start; $i <= $end; $i = strtotime( '+1 day', $i ) ) {
                                    $in_date  = false;
                                    $status   = 'available';
                                    $num_room = 0;
                                    foreach ( $data_room as $key => $val ) {
                                        if ( $i == $val->check_in && $i == $val->check_out ) { //in date
                                            $status = $val->status;
                                            if ( !$in_date ) $in_date = true;
                                        }
                                    }
                                    if ( $in_date ) {
                                        if ( $status != 'available' ) {
                                            $cant_book[] = $room;
                                            break;
                                        }
                                    } else {
                                        if ( $default_state == 'available' ) {
                                            if ( $number_room > $number_room_ori ) {
                                                $cant_book[] = $room;
                                                break;
                                            }
                                        } else {
                                            $cant_book[] = $room;
                                            break;
                                        }
                                    }
                                }
                            } else { // don't have custom price
                                if ( $default_state == 'available' ) {
                                    if ( $number_room > $number_room_ori ) {
                                        $cant_book[] = $room;
                                    }
                                } else {
                                    $cant_book[] = $room;
                                }
                            }
                        }
                    }
                }
            }
            $room_full_ordered = HotelHelper::_get_room_cant_book_by_id( $hotel_id, date( 'Y-m-d', $check_in ), date( 'Y-m-d', $check_out ), $number_room );
            if ( is_array( $room_full_ordered ) && count( $room_full_ordered ) ) {
                $cant_book = array_unique( array_merge( $cant_book, $room_full_ordered ) );
            }
            return $cant_book;
        }

        static function _get_room_cant_book_by_id( $hotel_id = '', $check_in = '', $check_out = '', $number_room = 0 ) {
            if ( !TSAdminRoom::checkTableDuplicate( 'ts_hotel' ) ) return "''";
            global $wpdb;

            if ( empty( $check_in ) || empty( $check_out ) )
                return "''";
            $sql     = "
					SELECT
						ts_booking_id,
						room_id,
						mt.meta_value AS number_room,
						SUM(DISTINCT room_num_search) AS booked_room,
						mt.meta_value - SUM(DISTINCT room_num_search) AS free_room,
						check_in,
						check_out
					FROM
						{$wpdb->prefix}ts_order_item_meta
					INNER JOIN {$wpdb->prefix}postmeta AS mt ON mt.post_id = {$wpdb->prefix}ts_order_item_meta.room_id
					AND mt.meta_key = 'number_room'
					INNER JOIN {$wpdb->prefix}ts_hotel AS mt1 ON mt1.post_id = {$wpdb->prefix}ts_order_item_meta.ts_booking_id
					WHERE
					(
							(
								(
									mt1.allow_full_day = 'on'
									OR mt1.allow_full_day = ''
								)
								AND (
									(
										STR_TO_DATE('{$check_in}', '%Y-%m-%d') < STR_TO_DATE(check_in, '%m/%d/%Y')
										AND STR_TO_DATE('{$check_out}', '%Y-%m-%d') > STR_TO_DATE(check_out, '%m/%d/%Y')
									)
									OR (
										STR_TO_DATE('{$check_in}', '%Y-%m-%d') BETWEEN STR_TO_DATE(check_in, '%m/%d/%Y')
										AND STR_TO_DATE(check_out, '%m/%d/%Y')
									)
									OR (
										STR_TO_DATE('{$check_out}', '%Y-%m-%d') BETWEEN STR_TO_DATE(check_in, '%m/%d/%Y')
										AND STR_TO_DATE(check_out, '%m/%d/%Y')
									)
								)
							)
							OR (
								mt1.allow_full_day = 'off'
								AND (
									(
										STR_TO_DATE('{$check_in}', '%Y-%m-%d') <= STR_TO_DATE(check_in, '%m/%d/%Y')
										AND STR_TO_DATE('{$check_out}', '%Y-%m-%d') >= STR_TO_DATE(check_out, '%m/%d/%Y')
									)
									OR (
										(
											STR_TO_DATE('{$check_in}', '%Y-%m-%d') BETWEEN STR_TO_DATE(check_in, '%m/%d/%Y')
											AND STR_TO_DATE(check_out, '%m/%d/%Y')
										)
										AND (
											STR_TO_DATE('{$check_in}', '%Y-%m-%d') < STR_TO_DATE(check_out, '%m/%d/%Y')
										)
									)
									OR (
										(
											STR_TO_DATE('{$check_out}', '%Y-%m-%d') BETWEEN STR_TO_DATE(check_in, '%m/%d/%Y')
											AND STR_TO_DATE(check_out, '%m/%d/%Y')
										)
										AND STR_TO_DATE('{$check_out}', '%Y-%m-%d') > STR_TO_DATE(check_in, '%m/%d/%Y')
									)
								)
							)
						)
					AND ts_booking_post_type = 'ts_hotel'
					AND ts_booking_id = '{$hotel_id}'
					AND status NOT IN ('trash', 'canceled')
					GROUP BY
						room_id
					HAVING
						number_room - SUM(DISTINCT room_num_search) < {$number_room}
				";
            $results = $wpdb->get_col( $sql, 1 );
            return $results;
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





