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


function ts_compare_encrypt( $string = '', $encrypt = '' ) {
    if ( empty( $string ) || empty( $encrypt ) ) {
        return false;
    }
    if ( md5( md5( 'ts-' . md5( $string ) ) ) == $encrypt ) {
        return true;
    }
    return false;
}

function ip_address() {

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters('tsinput_ip_address', $ip);
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
function timestamp_diff_day($date1,$date2){
    $total_time= $date2-$date1;
    $day   = floor($total_time /(3600*24));
    return $day;
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


add_filter('ts_cart_total_with_out_tax_for_coupon', 're_calculator_totla_price_for_coupon');
function re_calculator_totla_price_for_coupon($total){
    $total = getTotal(false, true, true);
    return $total;
}

function get_total_with_out_tax_for_coupon( $deposit_calculator = false ) {
    if ( isset( $_COOKIE['ts_cart'] ) ) {
        $cart = unserialize(stripslashes(gzuncompress(base64_decode($_COOKIE['ts_cart']))));

        if ( ! empty( $cart ) ) {
            $total = getTotal();
            $total = apply_filters( 'ts_cart_total_with_out_tax_for_coupon', $total );
            return $total;
        }
    } else {
        return 0;
    }

}

function cart_count() {
	if (isset($_COOKIE['ts_cart'])) {
		//return count( unserialize( stripslashes( $_COOKIE['ts_cart'] ) ) );
		return count(unserialize(stripslashes(gzuncompress(base64_decode($_COOKIE['ts_cart'])))));
	} else {
		return 0;
	}
}


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

function getExtraPrice($room_id = '', $extra_price = array(), $number_room = 0, $numberday = 0){
	$total_price = 0;
    $price_unit = get_post_meta($room_id, 'extra_price_unit', true);
//    $extra_price = get_post_meta($room_id, 'extra_services', true);
//	if(isset($extra_price) && is_array($extra_price) && count($extra_price)){
		foreach($extra_price as $number){
			$price_item = $number['trizen_hotel_room_extra_service_price'];
			if($price_item <= 0) $price_item = 0;
//			$number_item = intval($number['trizen_hotel_room_extra_service_price']);
//			if($number_item <= 0) $number_item = 0;
			$total_price += $price_item;
		}
//	}
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
	if(TSCart::use_coupon()){
		$price_coupon = floatval(TSCart::get_coupon_amount());
		if($price_coupon < 0) $price_coupon = 0;
		return $price_coupon;
	}
	return 0;
}

function get_current_available_calendar($post_id) {
	if (!empty($post_id)) {
		$post_type = get_post_type($post_id);
		if ($post_type == 'ts_tours' || $post_type == 'ts_activity') {
			$max_people = get_post_meta($post_id, 'max_people', true);
			if (empty($max_people))
				$max_people = 0;
		} elseif ($post_type == 'hotel_room') {
			$max_people = get_post_meta($post_id, 'number_room', true);
			if (empty($max_people))
				$max_people = 0;
		} elseif ($post_type == 'ts_rental') {
			$max_people = get_post_meta($post_id, 'number_room', true);
			if (empty($max_people))
				$max_people = 0;
		}
		$data_availbility = AvailabilityHelper::get_current_availability($post_id, $max_people);

		return $data_availbility;
	}

	return date('Y-m-d');
}

function convert_money($money = false, $rate = false, $round = true) {
	if (!$money)
		$money = 0;
	if (!$rate) {
        $current_rate = TravelHelper::get_current_currency( 'rate' );
        $current      = TravelHelper::get_current_currency( 'name' );

        $default = TravelHelper::get_default_currency( 'name' );

		if ($current != $default)
			$money = $money * $current_rate;
	} else {
		$current_rate = $rate;
		$money = $money * $current_rate;
	}
	if ($round) {
		return round((float) $money, 2);
	} else {
		return (float) $money;
	}
}


function getTotal($div_room = false, $disable_coupon = false, $disable_deposit = false){
	 $cart = get_carts();
	 $total = 0;

	 if(is_array($cart) && count($cart)){
	 	foreach($cart as $key => $val){
	 		$post_id = intval($key);
	 		/*if(!isset($val['data']['deposit_money'])){
	 			$val['data']['deposit_money'] = array();
	 		}*/
	 		if(get_post_type($post_id) == 'ts_hotel' or get_post_type($post_id) == 'hotel_room'){
	 			$room_id     = intval($val['data']['room_id']);
	 			$check_in    = $val['data']['check_in'];
	 			$check_out   = $val['data']['check_out'];
	 			$number_room = intval($val['number']);
	 			$numberday = dateDiff($check_in, $check_out);
	 			$adult_number = intval($val['data']['adult_number']);
	 			$child_number = intval($val['data']['child_number']);

	 			$sale_price = TSPrice::getRoomPrice($room_id, strtotime($check_in), strtotime($check_out), $number_room, $adult_number, $child_number);
	 			$extras = isset($val['data']['extras']) ? $val['data']['extras'] : array();
	 			$extra_price = getExtraPrice($room_id, $extras, $number_room, $numberday);

	 			$price_with_tax = getPriceWithTax($sale_price + $extra_price);

                $total = $price_with_tax;
	 			if($div_room){
	 				$total /= $number_room;
	 			}
	 		}
	 	}
	 }
	 return convert_money($total, false, false);
}

function message() {

	$content=isset($_SESSION['bt_message']['content'])?$_SESSION['bt_message']['content']:false;
	$type=isset($_SESSION['bt_message']['type'])?$_SESSION['bt_message']['type']:false;
	if(!$content) return;

	$html="<div class='alert alert-{$type}'>
                <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"".__('Close','trizen-helper')."\"><span aria-hidden=\"true\">&times;</span></button>
                {$content}
        </div>";

	//Reset Message
	$_SESSION['bt_message']=array();

	return $html;
}


add_action('wp', 'hotel_add_to_cart', 20);
function hotel_add_to_cart() {
	if ( request( 'action' ) == 'hotel_add_to_cart' ) {

		if ( do_add_to_cart() ) {
			$link = get_cart_link();
			wp_safe_redirect( $link );
			die;
		}
	}

}

// hotel booking
add_action('wp_ajax_hotel_add_to_cart', 'ajax_hotel_add_to_cart');
add_action('wp_ajax_nopriv_hotel_add_to_cart', 'ajax_hotel_add_to_cart');

function ajax_hotel_add_to_cart() {
	if (request('action') == 'hotel_add_to_cart') {
		$response = array();
		$response['status'] = 0;
		$response['message'] = "";
		$response['redirect'] = '';
		if (do_add_to_cart()) {
			$link = get_cart_link();
			$response['redirect'] = $link;
			$response['status'] = 1;
			echo json_encode($response);
			wp_die();
		} else {
			$message = message();
			$response['message'] = $message;
			echo json_encode($response);
			wp_die();
		}
	}
}
function is_wpml() {
	if (defined('ICL_LANGUAGE_CODE')) {
		return true;
	}

	return false;
}
function post_origin($post_id, $post_type = 'post') {
	if (is_wpml()) {
		global $sitepress;
		return apply_filters('wpml_object_id', $post_id, $post_type, true, $sitepress->get_default_language());
	} else {
		return $post_id;
	}
}

function getDateFormatJs( $need = null, $type = '' ) {
	//$need from theme options placeholder fields
	if ( $need ) return $need;
	$format    = '{mm}/{dd}/{yyyy}';
	$format_js = str_replace( [ '{', '}' ], '', $format );
	if ( $type == 'calendar' ) {
		$format_js = str_replace( 'M', 'MMM', $format_js );
	}
	if ($type == 'admin-calendar') {
		$year = strpos($format, 'yyyy');
		if ($year !== false) {
			$format_js = str_replace('yyyy', 'yy', $format_js);
		}
	}
	return $format_js;
}

function ts_options_id() {
	return apply_filters('ts_options_id', 'option_tree');
}
function ts_traveler_get_option($option_id, $default = false) {
	//global $ts_traveler_cached_options;
	//if ( empty( $ts_traveler_cached_options ) ) $ts_traveler_cached_options = get_option( ts_options_id() );
	$ts_traveler_cached_options = get_option(ts_options_id());
	if (isset($ts_traveler_cached_options[$option_id]) && !empty($ts_traveler_cached_options[$option_id]))
		return $ts_traveler_cached_options[$option_id];
	return $default;
}
function trizen_get_option($option_id, $default = false) {
	return ts_traveler_get_option($option_id, $default);
}
function getDateFormat() {
	$format = '{mm}/{dd}/{yyyy}';

	$ori_format = [
		'{d}' => 'j',
		'{dd}' => 'd',
		'{D}' => 'D',
		'{DD}' => 'l',
		'{m}' => 'n',
		'{mm}' => 'm',
		'{M}' => 'M',
		'{MM}' => 'F',
		'{yy}' => 'y',
		'{yyyy}' => 'Y'
	];
	preg_match_all("/({)[a-zA-Z]+(})/", $format, $out);

	$out = $out[0];
	foreach ($out as $key => $val) {
		foreach ($ori_format as $ori_key => $ori_val) {
			if ($val == $ori_key) {
				$format = str_replace($val, $ori_val, $format);
			}
		}
	}

	return $format;
}
function convertDateFormat($date) {
	$format = getDateFormat();
	if (!empty($date)) {
		//$date = str_replace('/', '-', $date);
		$pos = strpos($date, ' ');
		if ($pos)
			$date = substr($date, 0, $pos);
		$myDateTime = DateTime::createFromFormat($format, $date);
		if ($myDateTime)
			return $myDateTime->format('m/d/Y');
	}

	return '';
}

function getDateFormatMoment() {
    $format = '{dd}/{mm}/{yyyy}';
    $ori_format = [
        '{d}'    => 'D',
        '{dd}'   => 'DD',
        '{D}'    => 'D',
        '{DD}'   => 'l',
        '{m}'    => 'M',
        '{mm}'   => 'MM',
        '{M}'    => 'MMM',
        '{MM}'   => 'MMMM',
        '{yy}'   => 'YY',
        '{yyyy}' => 'YYYY'
    ];
    preg_match_all( "/({)[a-zA-Z]+(})/", $format, $out );
    $out = $out[ 0 ];
    foreach ( $out as $key => $val ) {
        foreach ( $ori_format as $ori_key => $ori_val ) {
            if ( $val == $ori_key ) {
                $format = str_replace( $val, $ori_val, $format );
            }
        }
    }
    return $format;
}

function getDateFormatMomentText() {
    $format = '{dd}/{mm}/{yyyy}';

    $ori_format = [
        '{d}'    => 'd',
        '{dd}'   => 'dd',
        '{D}'    => 'D',
        '{DD}'   => 'l',
        '{m}'    => 'm',
        '{mm}'   => 'mm',
        '{M}'    => 'M',
        '{MM}'   => 'MM',
        '{yy}'   => 'yy',
        '{yyyy}' => 'yyyy'
    ];
    preg_match_all("/({)[a-zA-Z]+(})/", $format, $out);

    $out = $out[0];
    foreach ($out as $key => $val) {
        foreach ($ori_format as $ori_key => $ori_val) {
            if ($val == $ori_key) {
                $format = str_replace($val, $ori_val, $format);
            }
        }
    }

    return $format;
}

function get_discount_rate($post_id = '', $check_in = ''){
    $post_type = get_post_type($post_id);
    $discount_text = 'discount' ;
    if($post_type =='ts_hotel' or $post_type =='ts_rental' or $post_type =='hotel_room') $discount_text = 'discount_rate';
    $tour_price_by = '';
    if($post_type == 'st_tours'){
        $tour_price_by = get_post_meta($post_id, 'tour_price_by', true);
    }
    $discount_type = get_post_meta( $post_id, 'discount_type' , true );
    $discount_rate = floatval(get_post_meta($post_id,$discount_text,true));
    if($discount_rate < 0) $discount_rate = 0;
    if($discount_rate > 100 && $discount_type == 'percent') $discount_rate = 100;
    $is_sale_schedule = get_post_meta($post_id, 'is_sale_schedule', true);
    if($is_sale_schedule == false || empty($is_sale_schedule)) $is_sale_schedule = 'off';
    if($is_sale_schedule == 'on'){
        if($post_type == 'ts_tours'){
            if($tour_price_by != 'fixed_depart'){
                $sale_from = intval(strtotime(get_post_meta($post_id, 'sale_price_from',true)));
                $sale_to = intval(strtotime(get_post_meta($post_id, 'sale_price_to',true)));
                if($sale_from > 0 && $sale_to > 0 && $sale_from < $sale_to){
                    if($check_in >= $sale_from && $check_in <= $sale_to){
                        return $discount_rate ;
                    }else {
                        return 0 ;
                    }
                }
            }
        }else{
            $sale_from = intval(strtotime(get_post_meta($post_id, 'sale_price_from',true)));
            $sale_to   = intval(strtotime(get_post_meta($post_id, 'sale_price_to',true)));
            if($sale_from > 0 && $sale_to > 0 && $sale_from < $sale_to){
                if($check_in >= $sale_from && $check_in <= $sale_to){
                    return $discount_rate ;
                }else {
                    return 0 ;
                }
            }
        }

    }else{
        return $discount_rate;
    }
}

function do_add_to_cart() {
 	$pass_validate = true;

 	$item_id = intval( request( 'room_id', '' ) );
 	/* start */
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

	$room_origin = post_origin( $room_id, 'hotel_room' );
	$check_in = request( 'check_in', '' );

	if ( empty( $check_in ) ) {
		set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}
	$check_in = convertDateFormat( $check_in );

	$check_out = request( 'check_out', '' );
	if ( empty( $check_out ) ) {
		set_message( __( 'Date is invalid', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}
	$check_out       = convertDateFormat( $check_out );
	$room_num_search = intval(request('number_room', ''));

	$adult_number = intval( request( 'adult_number', '' ) );
	if ( $adult_number <= 0 ) $adult_number = 1;

	 	$child_number = intval( request( 'child_number', '' ) );
	 	if ( $child_number <= 0 ) $child_number = 0;

    /*$trizen_hotel_room_extra_service_data    = get_post_meta(get_the_ID(), 'extra_services', true);
    if($trizen_hotel_room_extra_service_data) {
        foreach ($trizen_hotel_room_extra_service_data as $key => $item) {
            $extra_price_title = strtolower(str_replace(' ', '-', $item['trizen_hotel_room_extra_service_title']));
            $extra_service_price = request($extra_price_title, '');
        }
    }*/

//	$checkin_ymd  = date( 'Y-m-d', strtotime( $check_in ) );
//	$checkout_ymd = date( 'Y-m-d', strtotime( $check_out ) );

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
	 	$children = intval( get_post_meta( $room_origin, 'child_number', true ) );

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
	/*if ( $child_number > $children ) {
		set_message( __( 'Number of children in the room are incorrect.', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}*/
	$today           = date( 'm/d/Y' );
	$period          = dateDiff( $today, $check_in );
	$booking_min_day = intval( get_post_meta( $item_id, 'min_book_room', true ) );
	$compare         = dateCompare( $today, $check_in );
	$booking_period  = get_post_meta( $item_id, 'hotel_booking_period', true );
//	if ( empty( $booking_period ) || $booking_period <= 0 ) $booking_period = 0;

	/*if ( $compare < 0 ) {
		set_message( __( 'You can not set check-in date in the past', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}*/
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
	 * @since  1.0
	 */
	/*$partner_create_booking = request('add_booking_partner_field');
	if ( !st_validate_guest_name( $room_id, $adult_number, 0 ) && empty($partner_create_booking)) {
		set_message( esc_html__( 'Please enter the Guest Name', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}*/
 	/* end */

 	$numberday     = dateDiff( $check_in, $check_out );
 	if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on' ) {
 		$item_price = floatval( get_post_meta( $room_origin, 'adult_price', true ) ) * floatval( $adult_number ) * $numberday + floatval( get_post_meta( $room_origin, 'child_price', true ) ) * floatval( $child_number ) * $numberday;
 	} else {
 		$item_price = floatval( get_post_meta( $room_origin, 'price', true ) );
 	}
 	// Extra price added
 	$extras = request( 'extra_services', [] );
// 	$extras = get_post_meta(get_the_ID(), 'extra_services', true);

 	$extra_price   = getExtraPrice( $room_origin, $extras, $room_num_search, $numberday );
 	$sale_price    = TSPrice::getRoomPrice( $room_origin, strtotime( $check_in ), strtotime( $check_out ), $room_num_search, $adult_number, $child_number );

    $discount_rate = get_discount_rate( $room_origin, strtotime( $check_in ) );
 	$data          = [
 		'item_price'      => $item_price,
        'ori_price'       => $sale_price + $extra_price,
 		'check_in'        => $check_in,
 		'check_out'       => $check_out,
 		'room_num_search' => $room_num_search,
 		'room_id'         => $room_id,
 		'adult_number'    => $adult_number,
 		'child_number'    => $child_number,
 		'extras'          => $extras,
 		'extra_price'     => $extra_price,
// 		'commission'      => TravelHelper::get_commission( $item_id ),
 		'discount_rate'   => $discount_rate,
 		'guest_title'     => post( 'guest_title' ),
 		'guest_name'      => post( 'guest_name' ),
 	];
 	/*if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on') {
 		$data['adult_price'] = floatval( get_post_meta( $room_origin, 'adult_price', true ) );
 		$data['child_price'] = floatval( get_post_meta( $room_origin, 'child_price', true ) );
 	}*/
 	if ( $pass_validate ) {
 		$pass_validate = apply_filters( 'ts_hotel_add_cart_validate', $pass_validate, $data );

	    add_cart($item_id, $room_num_search, $extra_price, $data);
 	}
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

function _create_new_product( $item_id, $cart_item ) {

	$default = [
		'title'  => '',
		'price'  => 0,
		'number' => 1,
		'data'   => ''
	];

	$cart_item             = wp_parse_args( $cart_item, $default );
	$total_cart_item_price = 0;

	if ( ! $cart_item['number'] ) {
		$cart_item['number'] = 1;
	}

	$total_cart_item_price = $cart_item['price'];

	$total_cart_item_price = apply_filters( 'ts_' . get_post_type( $item_id ) . '_item_total', $total_cart_item_price, $item_id, $cart_item );

	// Check if product exists
	$check_exists = [
		'post_type'      => 'product',
		'meta_key'       => '_ts_booking_id',
		'meta_value'     => $item_id,
		'posts_per_page' => 1
	];
	$query_exists = new WP_Query( $check_exists );
	// if product exists
	if ( $query_exists->have_posts() ) {
		while ( $query_exists->have_posts() ) {
			$query_exists->the_post();
			// Create a variation
			$variation = [
				'post_content'   => '',
				'post_status'    => "publish",
				'post_title'     => sprintf( __( '%s in %s', 'trizen-helper' ), $cart_item['title'], date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) ) ),
				'post_parent'    => get_the_ID(),
				'post_type'      => "product_variation",
				'comment_status' => 'closed'
			];

			$variation_id = wp_insert_post( $variation );
			if ( is_wp_error( $variation_id ) ) {
				set_message( __( 'Sorry! Can not create variation product', 'trizen-helper' ) );

				return false;
			}

			update_post_meta( get_the_ID(), '_stock_status', 'instock' );

			// Product Meta
			update_post_meta( $variation_id, '_stock_status', 'instock' );
			update_post_meta( $variation_id, '_visibility', 'visible' );
			update_post_meta( $variation_id, '_downloadable', 'no' );
			update_post_meta( $variation_id, '_virtual', 'no' );
			update_post_meta( $variation_id, '_featured', 'no' );
			update_post_meta( $variation_id, '_sold_individually', 'yes' );
			update_post_meta( $variation_id, '_manage_stock', 'no' );
			update_post_meta( $variation_id, '_backorders', 'no' );
			update_post_meta( $variation_id, '_regular_price', $total_cart_item_price );
			update_post_meta( $variation_id, '_ts_booking_id', $item_id );
			update_post_meta( $variation_id, 'data', $cart_item['data'] );
			update_post_meta( $variation_id, 'attribute_types', '' );
			update_post_meta( $variation_id, '_product_version', '3.0.1' );

			/**
			 * Return the variation
			 */
			return [
				'product_id'   => get_the_ID(),
				'variation_id' => $variation_id
			];
		}
		wp_reset_postdata();
	} else {
		// if not , create new product
		$post = [
			'post_content'   => '',
			'post_status'    => "publish",
			'post_title'     => $cart_item['title'],
			'post_parent'    => '',
			'post_type'      => "product",
			'comment_status' => 'closed'
		];

		$product_id = wp_insert_post( $post );
		if ( is_wp_error( $product_id ) ) {
			set_message( __( 'Sorry! Can not create product', 'trizen-helper' ) );

			return false;
		}
		// Product Type simple
		wp_set_object_terms( $product_id, 'variable', 'product_type' );


		// Product Meta
		update_post_meta( $product_id, '_stock_status', 'instock' );
		update_post_meta( $product_id, '_visibility', 'visible' );
		update_post_meta( $product_id, '_downloadable', 'no' );
		update_post_meta( $product_id, '_virtual', 'no' );
		update_post_meta( $product_id, '_featured', 'no' );
		update_post_meta( $product_id, '_sold_individually', 'yes' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_backorders', 'no' );
		update_post_meta( $product_id, '_price', $total_cart_item_price );
		update_post_meta( $product_id, '_ts_booking_id', $item_id );
		update_post_meta( $product_id, 'data', $cart_item['data'] );

		$data_variation = [
			'types' => [
				'name'         => 'types',
				'value'        => 'service',
				'position'     => 0,
				'is_visible'   => 1,
				'is_variation' => 1,
				'is_taxonomy'  => 1
			]
		];

		update_post_meta( $product_id, '_product_attributes', $data_variation );
		update_post_meta( $product_id, '_product_version', '3.0.1' );

		return $product_id;
	}

}

function _add_product_to_cart( $product_id, $cart_data = [] ) {
	global $woocommerce;
	if ( is_array( $product_id ) and ! empty( $product_id['product_id'] ) and ! empty( $product_id['variation_id'] ) ) {
		$cart = WC()->cart->add_to_cart( $product_id['product_id'], 1, $product_id['variation_id'], [], [ 'ts_booking_data' => $cart_data ] );
	} elseif ( $product_id > 0 ) {
		$cart = WC()->cart->add_to_cart( $product_id, 1, '', [], [ 'ts_booking_data' => $cart_data ] );
	}
}

function getDepositData($post_id = '', $cart_data = array()){
	$cart_data['data']['deposit_money'] = array(
		'type' => '',
		'amount' => ''
	);
	$post_id = intval($post_id);
	$status = get_post_meta( $post_id , 'deposit_payment_status' , true );
	if(!$status) $status = '';
	if(!empty($status)){
		if($status == 'amount'){
			$status = 'percent';
		}
		$amount = floatval(get_post_meta($post_id , 'deposit_payment_amount' , true ));
		if($amount < 0) $amount = 0;
		if($amount > 100) $amount = 100;
		$cart_data['data']['deposit_money'] = array(
			'type' => $status,
			'amount' => $amount
		);
	}
	return $cart_data;
}

function add_cart( $item_id, $number = 1, $price = false, $data = [] ) {
     $data['ts_booking_post_type'] = ( $item_id == 'car_transfer' ) ? 'car_transfer' : get_post_type( $item_id );
     $data['ts_booking_id']        = ( $item_id == 'car_transfer' ) ? $data['car_id'] : $item_id;
     $data['sharing']              = get_post_meta( $item_id, 'sharing_rate', true );
     // $data['duration_unit']        = get_duration_unit( $item_id ); // from 1.1.9
     //check is woocommerce
     $ts_is_woocommerce_checkout = apply_filters( 'ts_is_woocommerce_checkout', false );

     //Enable booking fee for woocommerce
     if ( ! $ts_is_woocommerce_checkout ) {
	     $data = $price;
     }
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
         if ( get_post_type( $item_id ) == 'ts_hotel' ) {
             $post_id = intval( $cart_data['data']['room_id'] );
         } else {
             $post_id = intval( $item_id );
         }
         $product_id = _create_new_product( $post_id, $cart_data );
         if ( $product_id ) {
             _add_product_to_cart( $product_id, $cart_data['data'] );
         }
     } else {
         if ( get_post_type( $item_id ) == 'ts_hotel' ) {
             $post_id = intval( $cart_data['data']['room_id'] );
         } else {
             $post_id = intval( $item_id );

         }
          $cart_data = getDepositData( $post_id, $cart_data );
//         $cart_data = '';
     }

     $cart_data['data']['user_id'] = get_current_user_id();
     destroy_cart();
}

function get_cart() {
    if (isset($_COOKIE['ts_cart_package']) && !empty($_COOKIE['ts_cart_package'])) {
        return unserialize(stripslashes($_COOKIE['ts_cart_package']));
    }

    return false;
}


//add_filter('woocommerce_order_get_items', '_change_wc_order_item_rate');
function  _change_wc_order_item_rate($items=[]) {
    if(!empty($items)) {
        foreach($items as $key=>$value) {
            $items[$key]['line_total'] = convert_money($value['line_total']);
        }
    }
    return $items;
}

function _get_order_total_price( $post_id, $st_is_woocommerce_checkout = null ) {
    /*if ( $st_is_woocommerce_checkout === null )
        $st_is_woocommerce_checkout = apply_filters( 'st_is_woocommerce_checkout', false );
    if ( $st_is_woocommerce_checkout ) {*/
        global $wpdb;
        $querystr   = "SELECT meta_value FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
                WHERE
                1=1
                AND order_item_id = '{$post_id}'
                AND (
                    meta_key = '_line_total'
                    OR meta_key = '_line_tax'
                    OR meta_key = '_ts_booking_fee_price'
                )
                ";
        $price      = $wpdb->get_results( $querystr, OBJECT );
        $data_price = 0;
        if ( !empty( $price ) ) {
            foreach ( $price as $k => $v ) {
                $data_price += $v->meta_value;
            }
        }
        return $data_price;
    /*} else {
        $data_prices = get_post_meta( $post_id, 'data_prices', true );
        $data_prices = isset($data_prices['price_with_tax']) ? $data_prices['price_with_tax'] : 0;
        return $data_prices;
        // return $data_prices['price_with_tax'];
    }*/
}

function _get_price_item_order_woo( $order_woo_id )
{
    global $wpdb;
    $querystr   = "SELECT meta_value
            FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
            WHERE
            1=1
            AND order_item_id = '{$order_woo_id}'
            AND (
                meta_key = '_line_total'
                OR meta_key = '_line_tax'
            )
            ORDER BY meta_key DESC
            ";
    $price      = $wpdb->get_results( $querystr, ARRAY_A );
    $data_price = [];
    if ( !empty( $price ) ) {
        $data_price = $price;
    }
    return $data_price;
}

add_filter('woocommerce_order_get_total', '_change_order_amount_total');
function _change_order_amount_total($total)
{
    $debug = debug_backtrace();
    if(isset($debug[0]['function']) && $debug[0]['function'] ==='_change_order_amount_total') return $total;
    return convert_money($total);
}

if (!function_exists('ts_get_profile_avatar')) {
    function ts_get_profile_avatar($id, $size) {
        $gravatar_pic_url = get_avatar($id, $size, null, TravelHelper::get_alt_image());
        return $gravatar_pic_url;
    }
}

function get_avg_price_hotel( $hotel_id ) {
    if ( empty( $hotel_id ) ) $hotel_id = get_the_ID();
    $price = get_post_meta( $hotel_id, 'price_avg', true );
    return $price;
}

function ts_apply_discount($price, $type = 'percent', $amount = '', $booking_date = '', $is_sale_schedule = 'off', $from_date = '', $to_date = '') {
    if (!$amount)
        return $price;
    $is_discount = false;
    if ($is_sale_schedule != 'on') {
        $is_discount = true;
    } else {
        if ($booking_date and $from_date and $to_date and ( $booking_date ) >= ( $from_date ) and ( $booking_date ) <= ( $to_date ))
            $is_discount = true;
    }
    if ($is_discount) {
        switch ($type) {
            case "amount":
            case "fixed":
                $price -= $amount;
                break;
            case "percent":
            default:
                $price -= ( $price * $amount / 100 );
                break;
        }
    }
    if ($price <= 0)
        $price = 0;
    return (float) $price;
}

function get_price( $hotel_id = false ){
    return get_avg_price_hotel( $hotel_id );
}

add_action( 'wp_ajax_ts_fetch_inventory', 'ts_fetch_inventory');
function ts_fetch_inventory() {
    $post_id = post( 'post_id', '' );
    if ( get_post_type( $post_id ) == 'ts_hotel' ) {
        $start = strtotime( post( 'start', '' ) );
        $end   = strtotime( post( 'end', '' ) );
        if ( $start > 0 && $end > 0 ) {
            $args = [
                'post_type'      => 'hotel_room',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'     => 'room_parent',
                        'value'   => $post_id,
                        'compare' => '='
                    ]
                ]
            ];
            if ( ! current_user_can('administrator') ) {
                $args['author'] = get_current_user_id();
            }
            $rooms = [];
            $query = new WP_Query( $args );
            while ( $query->have_posts() ): $query->the_post();
                $rooms[] = [
                    'id'   => get_the_ID(),
                    'name' => get_the_title()
                ];
            endwhile;
            wp_reset_postdata();
            $datarooms = [];
            if ( !empty( $rooms ) ) {
                foreach ( $rooms as $key => $value ) {
                    $datarooms[] = featch_dataroom( $post_id, $value[ 'id' ], $value[ 'name' ], $start, $end );
                }
            }
            echo json_encode( [
                'status' => 1,
                'rooms'  => $datarooms
            ] );
            die;
        }
    }
    echo json_encode( [
        'status'  => 0,
        'message' => __( 'Can not fetch data', 'trizen-helper' ),
        'rooms'   => ''
    ] );
    die;
}

function featch_dataroom($hotel_id, $post_id, $post_name, $start, $end) {
    $number_room         = (int)get_post_meta($post_id, 'number_room', true);
    $allow_fullday       = get_post_meta($hotel_id, 'allow_full_day', true);
    $base_price          = (float)get_post_meta($post_id, 'price', true);
    $adult_price         = floatval(get_post_meta($post_id, 'adult_price', true));
    $child_price         = floatval(get_post_meta($post_id, 'child_price', true));
    $price_by_per_person = (get_post_meta($post_id, 'price_by_per_person', true) == 'on') ? true : false;
    global $wpdb;
    $sql = "SELECT
                    *
                FROM
                    {$wpdb->prefix}ts_room_availability AS avai
                WHERE
                    (
                        (
                            avai.check_in <= {$start}
                            AND avai.check_out >= {$start}
                        )
                        OR (
                            avai.check_in <= {$end}
                            AND avai.check_out >= {$end}
                        )
                        OR (
                            avai.check_in <= {$start}
                            AND avai.check_out >= {$end}
                        )
                        OR (
                            avai.check_in >= {$start}
                            AND avai.check_out <= {$end}
                        )
                    )
                and avai.post_id = {$post_id}";
    $avai_rs = $wpdb->get_results($sql);
    $column = 'ts_booking_id';
    if (get_post_type($post_id) == 'hotel_room') {
        $column = 'room_id';
    }
    $sql = "SELECT
                    *
                FROM
                    {$wpdb->prefix}ts_order_item_meta AS _order
                WHERE
                    (
                        (
                            _order.check_in_timestamp <= {$start}
                            AND _order.check_out_timestamp >= {$start}
                        )
                        OR (
                            _order.check_in_timestamp <= {$end}
                            AND _order.check_out_timestamp >= {$end}
                        )
                        OR (
                            _order.check_in_timestamp <= {$start}
                            AND _order.check_out_timestamp >= {$end}
                        )
                        OR (
                            _order.check_in_timestamp >= {$start}
                            AND _order.check_out_timestamp <= {$end}
                        )
                    )
                AND _order.{$column} = {$post_id} AND _order.`status` NOT IN ('cancelled', 'wc-cancelled')";
    $order_rs = $wpdb->get_results($sql);
    $return = [
        'name'   => esc_html($post_name),
        'values' => [],
        'id'     => $post_id,
        'price_by_per_person' => $price_by_per_person
    ];
    for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
        $date      = $i * 1000;
        $available = true;
        $price     = $base_price;
        if (!empty($avai_rs)) {
            foreach ($avai_rs as $key => $value) {
                if ($i >= $value->check_in && $i <= $value->check_out) {
                    if ($value->status == 'available') {
                        if ($price_by_per_person) {
                            $adult_price = floatval($value->adult_price);
                            $child_price = floatval($value->child_price);
                        } else {
                            $price = (float)$value->price;
                        }
                    } else {
                        $available = false;
                    }
                    break;
                }
            }
        }
        if ($available) {
            $ordered = 0;
            if (!empty($order_rs)) {
                foreach ($order_rs as $key => $value) {
                    if ($allow_fullday == 1) {
                        if ($i >= $value->check_in_timestamp && $i <= $value->check_out_timestamp) {
                            $ordered += (int)$value->room_num_search;
                        }
                    } else {
                        if ($i >= $value->check_in_timestamp && $i == strtotime('-1 day', $value->check_out_timestamp)) {
                            $ordered += (int)$value->room_num_search;
                        }
                    }
                }
            }
            if ($number_room - $ordered > 0) {
                $return['values'][] = [
                    'from'        => "/Date({$date})/",
                    'to'          => "/Date({$date})/",
                    'label'       => $number_room - $ordered,
                    'desc'        => sprintf(__('%s left', 'trizen-helper'), $number_room - $ordered),
                    'customClass' => 'ganttBlue',
                    'price'       => TravelHelper::format_money($price, ['simple_html' => true]),
                    'adult_price' => TravelHelper::format_money($adult_price, ['simple_html' => true]),
                    'child_price' => TravelHelper::format_money($child_price, ['simple_html' => true]),
                    'price_by_per_person' => $price_by_per_person
                ];
            } else {
                $return['values'][] = [
                    'from'        => "/Date({$date})/",
                    'to'          => "/Date({$date})/",
                    'label'       => __('O', 'trizen-helper'),
                    'desc'        => __('Out of stock', 'trizen-helper'),
                    'customClass' => 'ganttOrange',
                    'price'       => TravelHelper::format_money($price, ['simple_html' => true]),
                    'adult_price' => TravelHelper::format_money($adult_price, ['simple_html' => true]),
                    'child_price' => TravelHelper::format_money($child_price, ['simple_html' => true]),
                    'price_by_per_person' => $price_by_per_person
                ];
            }
        } else {
            $return['values'][] = [
                'from'        => "/Date({$date})/",
                'to'          => "/Date({$date})/",
                'label'       => __('N', 'trizen-helper'),
                'desc'        => __('Not Available', 'trizen-helper'),
                'customClass' => 'ganttRed',
                'price'       => TravelHelper::format_money($price, ['simple_html' => true]),
                'adult_price' => TravelHelper::format_money($adult_price, ['simple_html' => true]),
                'child_price' => TravelHelper::format_money($child_price, ['simple_html' => true]),
                'price_by_per_person' => $price_by_per_person
            ];
        }
    }
    return $return;
}



add_action( 'wp_ajax_add_price_inventory', 'add_price_inventory_hotels' );
function add_price_inventory_hotels(){
    $post_id = (int)post( 'post_id' );
    $price   = post( 'price' );
    $status  = post( 'status', 'available' );
    $start   = (float)post( 'start' );
    $end     = (float)post( 'end' );
    $start   /= 1000;
    $end     /= 1000;
    $adult_price = post( 'adult_price' );
    $child_price = post( 'child_price' );
    $price_by_per_person = get_post_meta( $post_id, 'price_by_per_person', true );
    $start = strtotime( date( 'Y-m-d', $start ) );
    $end   = strtotime( date( 'Y-m-d', $end ) );
    /*if ( get_post_type( $post_id ) != 'hotel_room' ) {
        echo json_encode( [
            'status'  => 0,
            'message' => esc_html__( 'Can not set price for this room :(', 'trizen-helper' )
        ] );
        die;
    }*/
    if ( $price_by_per_person == 'on' ) {
        if ( ( $status == 'available' )
            && ( $adult_price == '' && $child_price == '' ) && ( ( $adult_price == '' || !is_numeric( $adult_price ) || (float)$adult_price < 0 )
                || ( $child_price == '' || !is_numeric( $child_price ) || (float)$child_price < 0 ) ) ) {
            echo json_encode( [
                'status'  => 0,
                'message' => esc_html__( 'Price is incorrect', 'trizen-helper' )
            ] );
            die;
        }
    } else {
        if ( ( $status == 'available' ) && ( $price == '' || !is_numeric( $price ) || (float)$price < 0 ) ) {
            echo json_encode( [
                'status'  => 0,
                'message' => esc_html__( 'Price is incorrect', 'trizen-helper' )
            ] );
            die;
        }
    }
    $price = (float)$price;
    $adult_price = floatval( $adult_price );
    $child_price = floatval( $child_price );
    $base_id = (int)TSHotel::ts_origin_id( $post_id, 'hotel_room' );
    $new_item = inventory_save_data( $post_id, $base_id, $start, $end, $price, $status, $adult_price, $child_price );

    if ( $new_item > 0 ) {
        echo json_encode( [
            'status'  => 1,
            'message' => esc_html__( 'Successfully added', 'trizen-helper' )
        ] );
        die;
    } else {
        echo json_encode( [
            'status'  => 0,
            'message' => esc_html__( 'Getting an error when adding new item.', 'trizen-helper' )
        ] );
        die;
    }
}



function inventory_save_data( $post_id, $base_id, $check_in, $check_out, $price, $status, $adult_price = '', $child_price = '' ){
    global $wpdb;
    $result = get_availability( $base_id, $check_in, $check_out );

    $number         = get_post_meta( $base_id, 'number_room', true );
    $parent_id      = get_post_meta( $base_id, 'room_parent', true );
    $booking_period = get_post_meta( $parent_id, 'hotel_booking_period', true );
    $allow_full_day = get_post_meta( $base_id, 'allow_full_day', true );
    $adult_number   = get_post_meta( $base_id, 'adult_number', true );
    $child_number   = get_post_meta( $base_id, 'children_number', true );
    if($allow_full_day == 1) {
        $allowd_fullday = 'on';
    } else {
        $allowd_fullday = 'off';
    }

    $string_insert      = '';
    $check_total_update = 0;
    if ( !empty( $result ) ) {
        if ( !empty( $check_in ) && !empty( $check_out ) ) {
            $arr_to_insert = [];
            for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
                $check_available = TS_Hotel_Room_Availability::inst()
                    ->where( 'post_id', $base_id )
                    ->where( 'check_in', $i )
                    ->get()->result();
                if ( !empty( $check_available ) ) {
                    $check_update       = TS_Hotel_Room_Availability::inst()
                        ->where( 'post_id', $base_id )
                        ->where( 'check_in', $i )
                        ->update( [
                            'price'          => $price,
                            'post_type'      => 'hotel_room',
                            'number'         => $number,
                            'parent_id'      => $parent_id,
                            'allow_full_day' => $allowd_fullday,
                            'booking_period' => $booking_period,
                            'adult_number'   => $adult_number,
                            'child_number'   => $child_number,
                            'status'         => $status,
                            'adult_price'    => $adult_price,
                            'child_price'    => $child_price,
                        ] );
                    $check_total_update += $check_update;
                } else {
                    array_push( $arr_to_insert, $i );
                }
            }
            if ( !empty( $arr_to_insert ) ) {
                foreach ( $arr_to_insert as $kk => $vv ) {
                    $string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $booking_period, $allow_full_day, $adult_number, $child_number, $base_id, $vv, $vv, $price, 'available', $adult_price, $child_price );
                }
            }
        }
    } else {
        for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
            $string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $booking_period, $allow_full_day, $adult_number, $child_number, $base_id, $i, $i, $price, 'available', $adult_price, $child_price );
        }
    }

    if ( !empty( $string_insert ) || $check_total_update > 0 ) {
        if ( !empty( $string_insert ) ) {
            $string_insert = substr( $string_insert, 0, -1 );
            $sql           = "INSERT INTO {$wpdb->prefix}ts_room_availability (id, post_type, is_base, `number`, parent_id, booking_period, allow_full_day, adult_number, child_number, post_id,check_in,check_out,price, status, adult_price, child_price ) VALUES {$string_insert}";
            $result        = $wpdb->query( $sql );

            return $result;
        } else {
            return $check_total_update;
        }
    } else {
        return 0;
    }
}

function get_availability( $base_id = '', $check_in = '', $check_out = '' ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ts_room_availability';
    $sql = "SELECT * FROM {$table} WHERE post_id = {$base_id} AND ( ( CAST( `check_in` AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED) AND CAST( `check_in` AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) OR ( CAST( `check_out` AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED ) AND ( CAST( `check_out` AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) ) ) ";
    $result = $wpdb->get_results( $sql, ARRAY_A );
    $return = [];
    if ( !empty( $result ) ) {
        foreach ( $result as $item ) {
            $item_array = [
                'id'          => $item[ 'id' ],
                'post_id'     => $item[ 'post_id' ],
                'start'       => date( 'Y-m-d', $item[ 'check_in' ] ),
                'end'         => date( 'Y-m-d', strtotime( '+1 day', $item[ 'check_out' ] ) ),
                'price'       => (float)$item[ 'price' ],
                'price_text'  => TravelHelper::format_money( $item[ 'price' ] ),
                'status'      => $item[ 'status' ],
                'adult_price' => floatval( $item['adult_price'] ),
                'child_price' => floatval( $item['child_price'] ),
            ];
            $return[] = $item_array;
        }
    }

    return $return;
}


function _getdataHotel( $post_id, $check_in, $check_out ) {
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

function get_min_max_price( $post_type = 'ts_hotel' ) {
	$meta_key = 'avg_price';
	if ($meta_key == 'avg_price')
		$meta_key = "price_avg";

	if ( empty( $post_type ) || !TravelHelper::checkTableDuplicate( $post_type ) ) {
		return [ 'price_min' => 0, 'price_max' => 500 ];
	}

	global $wpdb;
	$sql = "
        select
            min(CAST({$meta_key} as DECIMAL)) as min,
            max(CAST({$meta_key} as DECIMAL)) as max
        from {$wpdb->prefix}ts_hotel";

	$results = $wpdb->get_results( $sql, OBJECT );

	$price_min = $results[ 0 ]->min;
	$price_max = $results[ 0 ]->max;

	if ( empty( $price_min ) ) $price_min = 0;
	if ( empty( $price_max ) ) $price_max = 500;

	return [ 'min' => ceil( $price_min ), 'max' => ceil( $price_max ) ];
}


add_action( 'wp_ajax_ts_add_room_number_inventory', 'ts_add_room_number_inventory' );
function ts_add_room_number_inventory() {
    $room_id      = post('room_id', '');
    $number_room  = post('number_room', '');
    $current_user = wp_get_current_user();
    $roles        = $current_user->roles;
    $role         = array_shift($roles);
    if ($role != 'administrator' && $role != 'partner') {
        $return = [
            'status'  => 0,
            'message' => esc_html__('Can not set number for room', 'trizen-helper')
        ];
        echo json_encode($return);
        die;
    } else {
        if ($role == 'partner') {
            $current_user_id = $current_user->ID;
            $post   = get_post($room_id);
            $authid = $post->post_author;
            if ($current_user_id != $authid) {
                $return = [
                    'status'  => 0,
                    'message' => esc_html__('Can not set number for room', 'trizen-helper')
                ];
                echo json_encode($return);
                die;
            }
        }
    }
    if (get_post_type($room_id) != 'hotel_room') {
        $return = [
            'status'  => 0,
            'message' => esc_html__('Can not set number for room', 'trizen-helper')
        ];
        echo json_encode($return);
        die;
    }
    if ($room_id < 0 || $room_id == '' || !is_numeric($room_id)) {
        $return = [
            'status'  => 0,
            'message' => esc_html__('Room is invalid!', 'trizen-helper'),
        ];
        echo json_encode($return);
        die;
    }
    if ($number_room < 0 || $number_room == '' || !is_numeric($number_room)) {
        $return = [
            'status' => 0,
            'message' => esc_html__('Number of room is invalid!', 'trizen-helper'),
        ];
        echo json_encode($return);
        die;
    }
    $res = update_post_meta($room_id, 'number_room', $number_room);
    //Update number room in available table
    $update_number_room = TS_Hotel_Room_Availability::inst()
        ->where('post_id', $room_id)
        ->update(['number' => $number_room]);
    if ($res && $update_number_room > 0) {
        $return = [
            'status'  => 1,
            'message' => esc_html__('Update success!', 'trizen-helper'),
        ];
        echo json_encode($return);
        die;
    } else {
        $return = [
            'status'  => 0,
            'message' => esc_html__('Can not set number for room', 'trizen-helper')
        ];
        echo json_encode($return);
        die;
    }
}



add_action( 'comment_post', 'save_review_stars' );
function save_review_stars($comment_id) {
    $comemntObj = get_comment($comment_id);
    $post_id    = $comemntObj->comment_post_ID;

    if (get_post_type($post_id) == 'ts_hotel') {
        $all_stars = TSHotel::get_review_stars();
        $ts_review_stars = isset($_POST['ts_review_stars']) ? $_POST['ts_review_stars']: [];

        if (!empty($all_stars) and is_array($all_stars)) {
            $total_point = 0;
            foreach ( $all_stars as $key => $value ) {
                if ( isset( $ts_review_stars[$value] ) ) {
                    //Now Update the Each Star Value
                    if( is_numeric( $ts_review_stars[$value] ) ) {
                        $ts_review_stars[$value] = intval( $ts_review_stars[$value] );
                    } else {
                        $ts_review_stars[$value] = 5;
                    }
                    $total_point += $ts_review_stars[$value];
                    update_comment_meta($comment_id, 'ts_star_' . sanitize_title($value), $ts_review_stars[$value]);
                }
            }
            $avg = round($total_point / count($all_stars), 1);
            //Update comment rate with avg point
            $rate = wp_filter_nohtml_kses($avg);
            if ($rate > 5) {
                //Max rate is 5
                $rate = 5;
            }

            update_comment_meta($comment_id, 'comment_rate', $rate);
            //Now Update the Stars Value
            update_comment_meta($comment_id, 'ts_review_stars', $ts_review_stars);
        }
    }

    if (get_post_type($post_id) == 'hotel_room') {
        $all_stars = get_option( 'room_review_stars' );
        $ts_review_stars = isset($_POST['ts_review_stars']) ? $_POST['ts_review_stars']: [];

        if (!empty($all_stars) and is_array($all_stars)) {
            $total_point = 0;
            foreach ( $all_stars as $key => $value ) {
                if ( isset( $ts_review_stars[$value] ) ) {
                    //Now Update the Each Star Value
                    if( is_numeric( $ts_review_stars[$value] ) ) {
                        $ts_review_stars[$value] = intval( $ts_review_stars[$value] );
                    } else {
                        $ts_review_stars[$value] = 5;
                    }
                    $total_point += $ts_review_stars[$value];
                    update_comment_meta($comment_id, 'ts_star_' . sanitize_title($value), $ts_review_stars[$value]);
                }
            }
            $avg = round($total_point / count($all_stars), 1);
            //Update comment rate with avg point
            $rate = wp_filter_nohtml_kses($avg);
            if ($rate > 5) {
                //Max rate is 5
                $rate = 5;
            }

            update_comment_meta($comment_id, 'comment_rate', $rate);
            //Now Update the Stars Value
            update_comment_meta($comment_id, 'ts_review_stars', $ts_review_stars);
        }
    }
    if (post('comment_rate')) {
        update_comment_meta($comment_id, 'comment_rate', post('comment_rate'));
    }
    //review_stars
    $avg = TSReview::get_avg_rate($post_id);
    update_post_meta($post_id, 'rate_review', $avg);
}


add_action('wp_ajax_ajax_search_room', 'ajax_search_room');
add_action('wp_ajax_nopriv_ajax_search_room', 'ajax_search_room');
function ajax_search_room() {
    check_ajax_referer('ts_frontend_security', 'security');
    $result = [
        'html'   => '',
        'status' => 1,
        'data'   => '',
    ];
    // $post           = request();
    $hotel_id       = $_POST['room_parent'];
    $today          = date('m/d/Y');
    $check_in       = convertDateFormat($_POST['start']);
    $check_out      = convertDateFormat($_POST['end']);
    $date_diff      = dateDiff($check_in, $check_out);
    $booking_period = intval(get_post_meta($hotel_id, 'hotel_booking_period', true));
    $period         = dateDiff($today, $check_in);
    if ($booking_period && $period < $booking_period) {
        ob_start();
        include(TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-none.php');
        $room_item_none_html = ob_get_clean();
        $result = [
            'status'  => 0,
            'html'    => $room_item_none_html,
            'message' => sprintf(__('This hotel allow minimum booking is %d day(s)', 'trizen-helper'), $booking_period),
        ];
        echo json_encode($result);
        die;
    }
    if ($date_diff < 1) {
        ob_start();
        include(TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-none.php');
        $room_item_none_html = ob_get_clean();
        $result = [
            'status'    => 0,
            'html'      => $room_item_none_html,
            'message'   => __('Make sure your check-out date is at least 1 day after check-in.', 'trizen-helper'),
            'more-data' => $date_diff
        ];
        echo json_encode($result);
        die;
    }
    global $post;
    $old_post = $post;
    
    $query = search_room();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            ob_start();
            include(TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-item.php');
            $room_item_html = ob_get_clean();
            $result['html'] .= $room_item_html;
        }
    } else {
        ob_start();
        include(TRIZEN_HELPER_PATH . 'inc/hotel/search/loop-room-none.php');
        $room_item_none_html = ob_get_clean();
        $result['html'] .= $room_item_none_html;
    }
    wp_reset_postdata();
    $post = $old_post;
    echo json_encode($result);
    die();
}
