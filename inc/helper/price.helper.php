<?php
if(!class_exists('TSPrice')) {
    class TSPrice {
        public function __construct()
        {

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
    }
}
new TSPrice();
