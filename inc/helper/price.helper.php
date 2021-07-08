<?php
if(!class_exists('TSPrice')) {
    class TSPrice {
        public function __construct()
        {

        }


        static function getGroupDay($start = '', $end = ''){
            $list = array();
            for($i = $start; $i <= $end; $i = strtotime('+1 day', $i)){
                $next = strtotime('+1 day', $i);
                if($next <= $end){
                    $list[] = array($i, $next);
                }
            }
            return $list;
        }

        /**
         *@since 1.0
         *	Only use for hotel room
         **/
        static function getRoomPriceOnlyCustomPrice($room_id = '', $check_in = '', $check_out = '', $number_room = 1, $adult_number = '', $child_number = ''){
            $room_id = intval($room_id);
            $default_state = get_post_meta($room_id, 'default_state', true);
            if(!$default_state) $default_state = 'available';
            $hotel_id = get_post_meta($room_id, 'room_parent', true);
            if(get_post_type($room_id) == 'hotel_room'){
                if ( get_post_meta( $room_id, 'price_by_per_person', true ) == 'on' ) {
                    $price_ori = floatval( get_post_meta( $room_id, 'adult_price', true ) ) * floatval( $adult_number ) + floatval( get_post_meta( $room_id, 'child_price', true ) ) * floatval( $child_number );
                } else {
                    $price_ori = floatval(get_post_meta($room_id, 'price', true));
                }
                if($price_ori < 0) $price_ori = 0;

                $total_price = 0;
                $custom_price = AvailabilityHelper::_getdataHotel($room_id, $check_in, $check_out);

                $price_key = 0;
                for($i = $check_in; $i <= $check_out; $i = strtotime('+1 day', $i)){
                    if(is_array($custom_price) && count($custom_price)){
                        $in_date = false;
                        $price   = 0;
                        $status  = 'available';
                        foreach($custom_price as $key => $val){
                            if($i >= $val->check_in && $i <= $val->check_out){
                                $status = $val->status;
                                if ( get_post_meta( $room_id, 'price_by_per_person', true ) == 'on' ) {
                                    $price = floatval( $adult_number ) * floatval( $val->adult_price ) + floatval( $child_number ) * floatval( $val->child_price );
                                } else {
                                    $price = floatval($val->price);
                                }
                                if(!$in_date) $in_date = true;
                            }
                        }
                        if($in_date){
                            if($status == 'available'){
                                $price_key = floatval($price);
                            }
                        }else{
                            if($default_state == 'available'){
                                $price_key = $price_ori;
                            }
                        }
                    }else{
                        if($default_state == 'available'){
                            $price_key = $price_ori;
                        }
                    }
                    if($i < $check_out){
                        $total_price += $price_key;
                    }
                }
                return $total_price * $number_room;
            }
            return 0;
        }

        static function convert_sale_price_by_day( $room_id ){
            $convert = array();
            $list_sale_date = get_post_meta($room_id, 'discount_by_day', true);
            if( !empty( $list_sale_date ) && is_array( $list_sale_date ) ){
                foreach( $list_sale_date as $key => $item ){
                    if( !empty( $item['number_day']) && !empty( $item['discount']) ){
                        $convert[ (int)$item['number_day'] ] = (float)$item['discount'];
                    }
                }
            }
            krsort($convert);
            return $convert;
        }

        static function getRoomPrice($room_id = '', $check_in = '', $check_out = '', $number_room = 1, $adult_number = '', $child_number = ''){
            $number_room   = !empty($number_room) ? $number_room : 1;
            $room_id       = intval($room_id);
            $default_state = get_post_meta($room_id, 'default_state', true);
            if(!$default_state) $default_state = 'available';
            $total_price = 0;
            /**
             * @since 1.0.0
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
//                $is_sale_schedule = get_post_meta($room_id, 'is_sale_schedule', true);
//                if($is_sale_schedule == false || empty($is_sale_schedule)) $is_sale_schedule = 'off';
                // Price with custom price
                $room_origin_id = post_origin($room_id, 'hotel_room');
                $custom_price = AvailabilityHelper::_getdataHotel($room_origin_id, $check_in, $check_out);
                $groupday = TSPrice::getGroupDay($check_in, $check_out);
                if(is_array($groupday) && count($groupday)){
                    foreach($groupday as $key => $date){
                        $price_tmp = 0;
                        $status    = 'available';
                        $priority  = 0;
                        $in_date   = false;
                        foreach($custom_price as $key2 => $val){
                            if($date[0] >= $val->check_in && $date[0] <= $val->check_out){
                                $status = $val->status;
                                if ( $price_by_per_person == 'on' ) {
                                    $price = floatval( floatval( $adult_number ) * $val->adult_price + floatval( $child_number ) * $val->child_price );
                                } else {
                                    $price = floatval($val->price);
                                }
                                if(!$in_date) $in_date = true;
                            }
                        }
                        if($in_date){
                            if($status = 'available'){
                                $price_tmp = $price;
                            }
                        }else{
                            if($default_state == 'available'){
                                $price_tmp = $price_ori;
                            }
                        }
                        $total_price += $price_tmp;
                        $sale_by_day[] = $price_tmp;
                    }
                    $convert       = self::convert_sale_price_by_day( $room_id );
                    $discount_type = get_post_meta( $room_id, 'discount_type_no_day', true);
                    if( !$discount_type ){ $discount_type = 'percent'; }
                    if( !empty( $convert ) ){
                        $total_price = 0;
                        $total_day = dateDiff(date('Y-m-d', $check_in), date('Y-m-d', $check_out));
                        while( !empty( $convert ) ){
                            foreach( $convert as $key => $discount ){
                                if( $total_day - $key >= 0 ) {
                                    $price  = 0;
                                    for( $i = 0; $i < $key; $i++ ){
                                        $price += $sale_by_day[ $i ];
                                    }
                                    if( $discount_type == 'percent' ){
                                        $price  -= $price * ($discount / 100 );
                                    } else {
                                        $price -= $discount;
                                    }
                                    $total_price += $price;
                                    $total_day -= $key;
                                    $sale_by_day = array_slice( $sale_by_day, $key );
                                    break;
                                } else {
                                    unset( $convert[ $key ] );
                                }
                            }
                        }
                        if( $total_day > 0 ){
                            for( $i = 0; $i < count( $sale_by_day ); $i++ ){
                                $total_price += $sale_by_day[ $i ];
                            }
                        }
                        $total_price  = $total_price * $number_room;
                        $total_price -= $total_price * ( $discount_rate / 100 );
                        return $total_price;
                    }
                }
                $total_price  = $total_price * $number_room;
                $total_price -= $total_price * ($discount_rate / 100);
                return $total_price;
            }
            return 0;
        }
    }
}
new TSPrice();
