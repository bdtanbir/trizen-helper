<?php

function request($index = NULL, $default = false) {
	// Check if a field has been provided
	if ($index === NULL AND ! empty($_REQUEST)) {
		return $_REQUEST;
	}


	if (isset($_REQUEST[$index]) && !empty($_REQUEST[$index])) {
		return $_REQUEST[$index];
	}

	return $default;
}

function post($index = NULL, $default = false) {
	// Check if a field has been provided
	if ($index === NULL AND ! empty($_POST)) {
		return $_POST;
	}

	if (isset($_POST[$index]))
		return $_POST[$index];

	return $default;
}
function get($index = NULL, $default = false) {
	// Check if a field has been provided
	if ($index === NULL AND ! empty($_GET)) {
		return $_GET;
	}

	if (isset($_GET[$index]))
		return $_GET[$index];

	return $default;
}

function set_message($message,$type='info')
{
	$_SESSION['bt_message']['content']=$message;
	$_SESSION['bt_message']['type']=$type;
}
function dateCompare($start, $end) {
	$start_ts = strtotime($start);
	$end_ts = strtotime($end);

	return $end_ts - $start_ts;
}


function dateDiff($start, $end){
	$start = strtotime($start);
	$end = strtotime($end);
	return ($end - $start) / (60 * 60 * 24);
}


function _getdataHotel( $post_id, $check_in, $check_out )
{
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
			FROM
				{$wpdb->prefix}st_room_availability
			WHERE
			post_id = %d
			AND post_type='hotel_room'
			AND check_in >=%d and check_in <=%d";
	$results = $wpdb->get_results( $wpdb->prepare($sql,[$post_id,$check_in,$check_out]) );

	return $results;
}



function getRoomPriceOnlyCustomPrice($room_id = '', $check_in = '', $check_out = '', $number_room = 1, $adult_number = '', $child_number = ''){
	$room_id = intval($room_id);

	$hotel_id = get_post_meta($room_id, 'room_parent', true);

	if(get_post_type($room_id) == 'hotel_room'){
		$price_ori = floatval(get_post_meta($room_id, 'trizen_hotel_regular_price', true));
		if($price_ori < 0) $price_ori = 0;

		$total_price = 0;
		$custom_price = _getdataHotel($room_id, $check_in, $check_out);

		$price_key = 0;
		for($i = $check_in; $i <= $check_out; $i = strtotime('+1 day', $i)){
			if(is_array($custom_price) && count($custom_price)){
				$in_date = false;
				$price = 0;
				foreach($custom_price as $key => $val){
					if($i >= $val->check_in && $i <= $val->check_out){
						$price = floatval($val->price);
						if(!$in_date) $in_date = true;
					}
				}
				if($in_date){
					$price_key = floatval($price);
				}else{
					$price_key = $price_ori;
				}
			}else{
				$price_key = $price_ori;
			}
			if($i < $check_out){
				$total_price += $price_key;

			}
		}

		return $total_price * $number_room;
	}
	return 0;
}


function getGroupDay($start = '', $end = ''){
	$list = array();
	for($i = $start; $i <= $end; $i = strtotime('+1 day', $i)){
		$next = strtotime('+1 day', $i);
		if($next <= $end){
			$list[] = array($i, $next);
		}
	}
	return $list;
}



function getRoomPrice($room_id = ''){
//	$room_id = intval($room_id);
//	$total_price = 0;
//	/**
//	 *@since 1.2.8
//	 *   sale by number day
//	 **/
//	$sale_by_day = array();
//	$sale_count_date = 0;
//
//	if(get_post_type($room_id) == 'hotel_room'){
//
//		$price_ori = floatval(get_post_meta($room_id, 'trizen_hotel_regular_price', true));
//
//		if($price_ori < 0) $price_ori = 0;
//
//		$discount_rate = floatval(get_post_meta($room_id,'discount_rate',true));
//
//		if($discount_rate < 0) $discount_rate = 0;
//		if($discount_rate > 100) $discount_rate = 100;
//
//
//		// Price wiht custom price
//		$custom_price = _getdataHotel($room_id, $check_in, $check_out);
//
//		$groupday = STPrice::getGroupDay($check_in, $check_out);
//
//		if(is_array($groupday) && count($groupday)){
//			foreach($groupday as $key => $date){
//				$price_tmp = 0;
//				$status = 'available';
//				$priority = 0;
//				$in_date = false;
//				foreach($custom_price as $key => $val){
//					if($date[0] >= $val->check_in && $date[0] <= $val->check_out){
//						$status = $val->status;
//						$price = floatval($val->price);
//						if(!$in_date) $in_date = true;
//					}
//				}
//
//				$price_tmp = $price_ori;
//
//				$total_price += $price_tmp;
//				$sale_by_day[] = $price_tmp;
//
//			}
//
//			$convert = self::convert_sale_price_by_day( $room_id );
//
//			$discount_type = get_post_meta( $room_id, 'discount_type_no_day', true);
//			if( !$discount_type ){ $discount_type = 'percent'; }
//
//			if( !empty( $convert ) ){
//				$total_price = 0;
//
//				$total_day = STDate::dateDiff(date('Y-m-d', $check_in), date('Y-m-d', $check_out));
//
//				while( !empty( $convert ) ){
//					foreach( $convert as $key => $discount ){
//						if( $total_day - $key >= 0 ){
//							$price = 0;
//							for( $i = 0; $i < $key; $i++ ){
//								$price += $sale_by_day[ $i ];
//							}
//							if( $discount_type == 'percent' ){
//								$price  -= $price * ($discount / 100 );
//							}else{
//								$price -= $discount;
//							}
//
//							$total_price += $price;
//							$total_day -= $key;
//							$sale_by_day = array_slice( $sale_by_day, $key );
//							break;
//						}else{
//							unset( $convert[ $key ] );
//						}
//					}
//
//				}
//				if( $total_day > 0 ){
//					for( $i = 0; $i < count( $sale_by_day ); $i++ ){
//						$total_price += $sale_by_day[ $i ];
//					}
//				}
//				$total_price  = $total_price * $number_room;
//				$total_price -= $total_price * ( $discount_rate / 100 );
//				return $total_price;
//			}
//		}
//		$total_price  = $total_price * $number_room;
//		$total_price -= $total_price * ($discount_rate / 100);
//		return $total_price;
//	}
//	return 0;
}



/*function count() {
	if ( isset( $_COOKIE['ts_cart'] ) ) {
		//return count( unserialize( stripslashes( $_COOKIE['ts_cart'] ) ) );
		return count (unserialize(stripslashes(gzuncompress(base64_decode($_COOKIE['ts_cart'])))));
	} else {
		return 0;
	}
}*/


function check_cart() {
	$cart = isset( $_COOKIE['ts_cart'] ) ? unserialize(stripslashes(gzuncompress(base64_decode($_COOKIE['ts_cart'])))) : false;
	//$cart = isset( $_COOKIE['ts_cart'] ) ? unserialize( stripslashes( $_COOKIE['ts_cart'] ) ) : false;

	if ( ! is_array( $cart ) ) {
		return false;
	}

	return true;
}

function get_carts() {
	return isset( $_COOKIE['ts_cart'] ) ? unserialize(stripslashes(gzuncompress(base64_decode($_COOKIE['ts_cart'])))) : false;
	//return isset( $_COOKIE['ts_cart'] ) ? unserialize(stripslashes($_COOKIE['ts_cart'])) : false;
}


function getExtraPrice($number_room = 0){
	$total_price = 0;
	if(isset($extra_price['value']) && is_array($extra_price['value']) && count($extra_price['value'])){
		foreach($extra_price['value'] as $name => $number){
			$price_item = floatval($extra_price['price'][$name]);
			if($price_item <= 0) $price_item = 0;
			$number_item = intval($extra_price['value'][$name]);
			if($number_item <= 0) $number_item = 0;
			$total_price += $price_item * $number_item;
		}
	}
	return $total_price * $number_room;
}


function getPriceWithTax($price = 0, $tax = false){
	$price = floatval($price);
	if($price < 0) $price = 0;
	/*if(!$tax){
		$tax = 0;
		if(st()->get_option('tax_enable','off') == 'on' && st()->get_option('st_tax_include_enable', 'off') == 'off'){

			$tax = floatval(st()->get_option('tax_value',0));
		}
	}
	$price = $price + ($price / 100) * $tax;*/
	return $price;
}

function getCouponPrice(){
	if(STCart::use_coupon()){
		$price_coupon = floatval(STCart::get_coupon_amount());
		if($price_coupon < 0) $price_coupon = 0;

		return $price_coupon;
	}
	return 0;
}

/*function convert_money( $money = false, $rate = false, $round = true )
{
	if ( !$money ) $money = 0;
	if ( !$rate ) {
		$current_rate = get_current_currency( 'rate' );
		$current      = get_current_currency( 'name' );

		$default = self::get_default_currency( 'name' );

		if ( $current != $default )
			$money = $money * $current_rate;
	} else {
		$current_rate = $rate;
		$money        = $money * $current_rate;
	}
	if ( $round ) {
		return round( (float)$money, 2 );
	} else {
		return (float)$money;
	}
}*/


/*function get_current_currency( $need = false )
{

	//Check session of user first

	if ( isset( $_SESSION[ 'currency' ][ 'name' ] ) ) {
		$name = $_SESSION[ 'currency' ][ 'name' ];

		if ( $session_currency = self::find_currency( $name ) ) {
			if ( $need and isset( $session_currency[ $need ] ) ) return $session_currency[ $need ];

			return $session_currency;
		}
	}

	return self::get_default_currency( $need );
}*/


function getTotal($div_room = false, $disable_coupon = false, $disable_deposit = false){
	 $cart = get_carts();
	 $total = 0;

//	 if(is_array($cart) && count($cart)){
//	 	foreach($cart as $key => $val){
//	 		$post_id = intval($key);
//	 		/*if(!isset($val['data']['deposit_money'])){
//	 			$val['data']['deposit_money'] = array();
//	 		}*/
//	 		if(get_post_type($post_id) == 'ts_hotel' or get_post_type($post_id) == 'hotel_room'){
//	 			$room_id = intval($val['data']['room_id']);
////	 			$check_in = $val['data']['check_in'];
////	 			$check_out = $val['data']['check_out'];
////	 			$number_room = intval($val['number']);
////	 			$numberday = STDate::dateDiff($check_in, $check_out);
////	 			$adult_number = intval($val['data']['adult_number']);
////	 			$child_number = intval($val['data']['child_number']);
//
////	 			$sale_price = STPrice::getRoomPrice($room_id, strtotime($check_in), strtotime($check_out), $number_room, $adult_number, $child_number);
//	 			$sale_price = getRoomPrice($room_id);
//	 			$extras = isset($val['data']['extras']) ? $val['data']['extras'] : array();
//	 			$extra_price = getExtraPrice();
//
//	 			$price_with_tax = getPriceWithTax($sale_price + $extra_price);
//
//                $total = $price_with_tax;
//	 			if($div_room){
//	 				$total /= $number_room;
//	 			}
//	 		}
//	 	}
//	 }
//	 return TravelHelper::convert_money($total, false, false);
}


function hotel_add_to_cart()
{
	if ( request( 'action' ) == 'hotel_add_to_cart' ) {

		if ( do_add_to_cart() ) {
			$link = get_cart_link();
			wp_safe_redirect( $link );
			die;
		}
	}

}

function do_add_to_cart()
{
 	$pass_validate = true;

 	$item_id = intval( request( 'item_id', '' ) );
 	if ( $item_id <= 0 ) {
 		set_message( __( 'This hotel is not available.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}


 	$room_id = intval( request( 'room_id', '' ) );
 	if ( $room_id <= 0 || get_post_type( $room_id ) != 'hotel_room' ) {
 		set_message( __( 'This room is not available.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	$room_origin = TravelHelper::post_origin( $room_id, 'hotel_room' );

 	$check_in = request( 'check_in', '' );

 	if ( empty( $check_in ) ) {
 		set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	$check_in = TravelHelper::convertDateFormat( $check_in );

 	$check_out = request( 'check_out', '' );
 	if ( empty( $check_out ) ) {
 		set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	$check_out       = TravelHelper::convertDateFormat( $check_out );
 	$room_num_search = intval( request( 'room_num_search', '' ) );
 	if ( empty( $room_num_search ) )
 		$room_num_search = intval( request( 'number_room', '' ) );
 	if ( $room_num_search <= 0 ) $room_num_search = 1;

 	$adult_number = intval( request( 'adult_number', '' ) );
 	if ( $adult_number <= 0 ) $adult_number = 1;

 	$child_number = intval( request( 'child_number', '' ) );
 	if ( $child_number <= 0 ) $child_number = 0;

 	$checkin_ymd  = date( 'Y-m-d', strtotime( $check_in ) );
 	$checkout_ymd = date( 'Y-m-d', strtotime( $check_out ) );

 	/*if ( !HotelHelper::check_day_cant_order( $room_origin, $checkin_ymd, $checkout_ymd, $room_num_search, $adult_number, $child_number ) ) {
 		set_message( sprintf( __( 'This room is not available from %s to %s.', 'trizen-helper' ), $checkin_ymd, $checkout_ymd ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	if ( !HotelHelper::_check_room_only_available( $room_origin, $checkin_ymd, $checkout_ymd, $room_num_search ) ) {
 		set_message( __( 'This room is not available.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}*/

 	if ( strtotime( $check_out ) - strtotime( $check_in ) <= 0 ) {
 		set_message( __( 'The check-out is later than the check-in.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	$num_room = intval( get_post_meta( $room_origin, 'number_room', true ) );
 	$adult    = intval( get_post_meta( $room_origin, 'adult_number', true ) );
 	if ( $adult == 0 ) {
 		$adult = 1;
 	}
 	$children = intval( get_post_meta( $room_origin, 'children_number', true ) );

 	if ( $room_num_search > $num_room ) {
 		set_message( __( 'Max of rooms are incorrect.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	if ( $adult_number > $adult ) {
 		set_message( sprintf( __( 'Max of adults is %d people.', 'trizen-helper' ), $adult ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	if ( $child_number > $children ) {
 		set_message( __( 'Number of children in the room are incorrect.', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	$today = date( 'm/d/Y' );

 	$period = dateDiff( $today, $check_in );

 	$booking_min_day = intval( get_post_meta( $item_id, 'min_book_room', true ) );
 	$compare         = dateCompare( $today, $check_in );

 	$booking_period = get_post_meta( $item_id, 'hotel_booking_period', true );
 	if ( empty( $booking_period ) || $booking_period <= 0 ) $booking_period = 0;

 	if ( $compare < 0 ) {
 		set_message( __( 'You can not set check-in date in the past', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}
 	if ( $period < $booking_period ) {
 		set_message( sprintf( _n( 'This hotel allow minimum booking is %d day', 'This hotel allow minimum booking is %d day(s)', $booking_period, 'trizen-helper' ), $booking_period ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	if ( $booking_min_day and $booking_min_day > dateDiff( $check_in, $check_out ) ) {
 		set_message( sprintf( _n( 'Please book at least %d day in total', 'Please book at least %d days in total', $booking_min_day,'trizen-helper' ), $booking_min_day ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	/**
 	 * Validate Guest Name
 	 *
 	 * @since  2.1.2
 	 * @author dannie
 	 */
 	$partner_create_booking = request('add_booking_partner_field');
 	if ( !st_validate_guest_name( $room_id, $adult_number, $child_number, 0 ) && empty($partner_create_booking)) {
 		set_message( esc_html__( 'Please enter the Guest Name', 'trizen-helper' ), 'danger' );
 		$pass_validate = false;

 		return false;
 	}

 	$numberday     = dateDiff( $check_in, $check_out );
 	if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on' ) {
 		$item_price = floatval( get_post_meta( $room_origin, 'adult_price', true ) ) * floatval( $adult_number ) * $numberday + floatval( get_post_meta( $room_origin, 'child_price', true ) ) * floatval( $child_number ) * $numberday;
 	} else {
 		$item_price = floatval( get_post_meta( $room_origin, 'price', true ) );
 	}
 	// Extra price added in the new version 1.1.9
 	$extras = request( 'extra_price', [] );

 	$extra_price   = '4';
 	$sale_price    = '3';
 	$data          = [
 		'item_price'      => $item_price,
 		// 'ori_price'       => $sale_price + $extra_price,
 		'check_in'        => $check_in,
 		'check_out'       => $check_out,
 		'room_num_search' => $room_num_search,
 		'room_id'         => $room_id,
 		'adult_number'    => $adult_number,
 		'child_number'    => $child_number,
 		'extras'          => $extras,
 		 'extra_price'     => $extra_price,
// 		'commission'      => TravelHelper::get_commission( $item_id ),
// 		'discount_rate'   => $discount_rate,
 		'guest_title'     => post( 'guest_title' ),
 		'guest_name'      => post( 'guest_name' ),
 	];
 	if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on') {
 		$data['adult_price'] = floatval( get_post_meta( $room_origin, 'adult_price', true ) );
 		$data['child_price'] = floatval( get_post_meta( $room_origin, 'child_price', true ) );
 	}
 	if ( $pass_validate ) {
 		$pass_validate = apply_filters( 'ts_hotel_add_cart_validate', $pass_validate, $data );
 	}


// 	if ( $pass_validate ) {
// 		add_cart( $item_id, $room_num_search, $sale_price + $extra_price, $data );
// 	}

 	return $pass_validate;

 }


function get_cart_link() {
	$cart_link                  = get_permalink(  );
	$ts_is_woocommerce_checkout = apply_filters( 'ts_is_woocommerce_checkout', false );

	if ( $ts_is_woocommerce_checkout ) {
		$url = wc_get_cart_url();
		if ( $url ) {
			$cart_link = $url;
		}
	}

	return apply_filters( 'ts_cart_link', $cart_link );
}


/**
 *
 *
 *
 * @update 1.1.3
 * */
function add_cart( $item_id, $number = 1, $price = false, $data = [] ) {
     /*$data['st_booking_post_type'] = ( $item_id == 'car_transfer' ) ? 'car_transfer' : get_post_type( $item_id );
     $data['st_booking_id']        = ( $item_id == 'car_transfer' ) ? $data['car_id'] : $item_id;
     $data['sharing']              = get_post_meta( $item_id, 'sharing_rate', true );
     // $data['duration_unit']        = get_duration_unit( $item_id ); // from 1.1.9
     //check is woocommerce
     $ts_is_woocommerce_checkout = apply_filters( 'ts_is_woocommerce_checkout', false );

     //Enable booking fee for woocommerce
     //if ( ! $ts_is_woocommerce_checkout ) {
     $data = self::_get_data_booking_fee( $price, $data );
     //}
     $number    = intval( $number );
     $cart_data = [
         'number' => $number,
         'price'  => $price,
         'data'   => $data,
         'title'  => ( $item_id == 'car_transfer' ) ? get_the_title((int) $data['car_id']) : get_the_title( $item_id )
     ];
     if ( $ts_is_woocommerce_checkout ) {

         $cart_data['price']               = floatval( $data['ori_price'] );
         $cart_data['data']['total_price'] = floatval( $data['ori_price'] );
         if ( get_post_type( $item_id ) == 'ts_flight' ) {
             $cart_data['price']               = floatval( $data['total_price'] );
             $cart_data['data']['total_price'] = floatval( $data['total_price'] );
         }
         if ( get_post_type( $item_id ) == 'ts_hotel' ) {
             $post_id = intval( $cart_data['data']['room_id'] );
         } else {
             $post_id = intval( $item_id );
         }
         if ( $item_id == 'car_transfer' ) {
             $post_id = (int) $data['car_id'];
         }
         $product_id = self::_create_new_product( $post_id, $cart_data );
         if ( $product_id ) {
             self::_add_product_to_cart( $product_id, $cart_data['data'] );
         }
     } else {
         if ( get_post_type( $item_id ) == 'ts_hotel' ) {
             $post_id = intval( $cart_data['data']['room_id'] );
         } else {
             if ( $item_id == 'car_transfer' ) {
                 $post_id = $data['car_id'];
             } else {
                 $post_id = intval( $item_id );
             }

         }
//          $cart_data = STPrice::getDepositData( $post_id, $cart_data );
         $cart_data = '';
     }

     $cart_data['data']['user_id'] = get_current_user_id();
     self::destroy_cart();*/
}



/**
 * @since   1.3.1
 * @updated 1.3.1
 * */
function get_cart() {
    if (isset($_COOKIE['ts_cart_package']) && !empty($_COOKIE['ts_cart_package'])) {
        return unserialize(stripslashes($_COOKIE['ts_cart_package']));
    }

    return false;
}





