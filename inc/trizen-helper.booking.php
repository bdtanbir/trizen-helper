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
				{$wpdb->prefix}ts_room_availability
			WHERE
			post_id = %d
			AND post_type='hotel_room'
			AND check_in >=%d and check_in <=%d";
	$results = $wpdb->get_results( $wpdb->prepare($sql,[$post_id,$check_in,$check_out]) );

	return $results;
}


function getRoomPriceOnlyCustomPrice($room_id = '', $check_in = '', $check_out = '', $number_room = 1, $adult_number = '', $child_number = ''){
	$room_id = intval($room_id);

	$hotel_id = get_post_meta($room_id, 'trizen_hotel_room_select', true);

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
	$room_id = intval($room_id);
	$total_price = 0;
	/**
	 *@since 1.2.8
	 *   sale by number day
	 **/
	$sale_by_day = array();
	$sale_count_date = 0;

	if(get_post_type($room_id) == 'hotel_room'){

		$price_ori = floatval(get_post_meta($room_id, 'trizen_hotel_regular_price', true));

		if($price_ori < 0) $price_ori = 0;

		$discount_rate = floatval(get_post_meta($room_id,'discount_rate',true));

		if($discount_rate < 0) $discount_rate = 0;
		if($discount_rate > 100) $discount_rate = 100;


		// Price wiht custom price
		$custom_price = _getdataHotel($room_id);

		/*$groupday = STPrice::getGroupDay($check_in, $check_out);

		if(is_array($groupday) && count($groupday)){
			foreach($groupday as $key => $date){
				$price_tmp = 0;
				$status = 'available';
				$priority = 0;
				$in_date = false;
				foreach($custom_price as $key => $val){
					if($date[0] >= $val->check_in && $date[0] <= $val->check_out){
						$status = $val->status;
						$price = floatval($val->price);
						if(!$in_date) $in_date = true;
					}
				}

				$price_tmp = $price_ori;

				$total_price += $price_tmp;
				$sale_by_day[] = $price_tmp;

			}

			$convert = self::convert_sale_price_by_day( $room_id );

			$discount_type = get_post_meta( $room_id, 'discount_type_no_day', true);
			if( !$discount_type ){ $discount_type = 'percent'; }

			if( !empty( $convert ) ){
				$total_price = 0;

				$total_day = STDate::dateDiff(date('Y-m-d', $check_in), date('Y-m-d', $check_out));

				while( !empty( $convert ) ){
					foreach( $convert as $key => $discount ){
						if( $total_day - $key >= 0 ){
							$price = 0;
							for( $i = 0; $i < $key; $i++ ){
								$price += $sale_by_day[ $i ];
							}
							if( $discount_type == 'percent' ){
								$price  -= $price * ($discount / 100 );
							}else{
								$price -= $discount;
							}

							$total_price += $price;
							$total_day -= $key;
							$sale_by_day = array_slice( $sale_by_day, $key );
							break;
						}else{
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
		}*/
		$total_price -= $total_price * ($discount_rate / 100);
		return $total_price;
	}
	return 0;
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

//function inventory_save_data( $post_id, $base_id, $check_in, $check_out, $price, $status, $adult_price = '', $child_price = '' )
//{
//	global $wpdb;
////	$result = get_availability( $base_id, $check_in, $check_out );
//
//	$number         = get_post_meta( $base_id, 'number_room', true );
//	$parent_id      = get_post_meta( $base_id, 'trizen_hotel_room_select', true );
////	$booking_period = get_post_meta( $parent_id, 'hotel_booking_period', true );
////	$allow_full_day = get_post_meta( $base_id, 'allow_full_day', true );
//	$adult_number   = get_post_meta( $base_id, 'trizen_room_facility_num_of_adults', true );
////	$child_number   = get_post_meta( $base_id, 'children_number', true );
//
//	$string_insert      = '';
//	$check_total_update = 0;
////	if ( !empty( $result ) ) {
//		/*if ( !empty( $check_in ) && !empty( $check_out ) ) {
//			$arr_to_insert = [];
//			for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
//				$check_available = ST_Hotel_Room_Availability::inst()
//				                                             ->where( 'post_id', $base_id )
//				                                             ->where( 'check_in', $i )
//				                                             ->get()->result();
//				if ( !empty( $check_available ) ) {
//					$check_update       = ST_Hotel_Room_Availability::inst()
//					                                                ->where( 'post_id', $base_id )
//					                                                ->where( 'check_in', $i )
//					                                                ->update( [
//						                                                'price'          => $price,
//						                                                'post_type'      => 'hotel_room',
//						                                                'number'         => $number,
//						                                                'parent_id'      => $parent_id,
//						                                                'allow_full_day' => $allow_full_day,
//						                                                'booking_period' => $booking_period,
//						                                                'adult_number'   => $adult_number,
//						                                                'child_number'   => $child_number,
//						                                                'status'         => $status,
//						                                                'adult_price'    => $adult_price,
//						                                                'child_price'    => $child_price,
//					                                                ] );
//					$check_total_update += $check_update;
//				} else {
//					array_push( $arr_to_insert, $i );
//				}
//			}
//			if ( !empty( $arr_to_insert ) ) {
//				foreach ( $arr_to_insert as $kk => $vv ) {
//					$string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $booking_period, $allow_full_day, $adult_number, $child_number, $base_id, $vv, $vv, $price, 'available', $adult_price, $child_price );
//				}
//			}
//		}*/
////	} else {
//		for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
//			$string_insert .= $wpdb->prepare( "(null, %s, %s, %d, %d, %d, %s, %d, %d, %s, %s,%s, %s, %s, %s, %s),", 'hotel_room', '0', $number, $parent_id, $adult_number, $base_id, $i, $i, $price, 'available', $adult_price, $child_price );
//		}
////	}
//
//	if ( !empty( $string_insert ) || $check_total_update > 0 ) {
//		if ( !empty( $string_insert ) ) {
//			$string_insert = substr( $string_insert, 0, -1 );
//			$sql           = "INSERT INTO {$wpdb->prefix}ts_room_availability (id, post_type, is_base, `number`, parent_id, adult_number, post_id,check_in,check_out,price, status, adult_price, child_price ) VALUES {$string_insert}";
//			$result        = $wpdb->query( $sql );
//
//			return $result;
//		} else {
//			return $check_total_update;
//		}
//	} else {
//		return 0;
//	}
//}

function get_current_availability($post_id, $max_people)
{
	global $wpdb;
	$post_type = get_post_type($post_id);
	$where_book_limit = '';
	/*if ($max_people > 0) {
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
		$hotel_id = get_post_meta($post_id, 'trizen_hotel_room_select', true);
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
	} else {*/
		return date('Y-m-d');
	/*}*/
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
		$data_availbility = get_current_availability($post_id, $max_people);

		return $data_availbility;
	}

	return date('Y-m-d');
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

function convert_money($money = false, $rate = false, $round = true) {
	if (!$money)
		$money = 0;
	if (!$rate) {
		$current_rate = '$';
		$current = '$';

		$default = '$';

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
	 			$room_id = intval($val['data']['room_id']);
//	 			$check_in = $val['data']['check_in'];
//	 			$check_out = $val['data']['check_out'];
	 			$number_room = intval($val['number']);
//	 			$numberday = STDate::dateDiff($check_in, $check_out);
//	 			$adult_number = intval($val['data']['adult_number']);
//	 			$child_number = intval($val['data']['child_number']);

//	 			$sale_price = STPrice::getRoomPrice($room_id, strtotime($check_in), strtotime($check_out), $number_room, $adult_number, $child_number);
	 			$sale_price = getRoomPrice($room_id);
	 			$extras = isset($val['data']['extras']) ? $val['data']['extras'] : array();
	 			$extra_price = getExtraPrice();

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

// hotel booking
add_action('wp_ajax_hotel_add_to_cart', 'ajax_hotel_add_to_cart');
add_action('wp_ajax_nopriv_hotel_add_to_cart', 'ajax_hotel_add_to_cart');

function ajax_hotel_add_to_cart()
{
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

function getDateFormatJs( $need = null, $type = '' )
{
	//$need from theme options placeholder fields
	if ( $need ) return $need;
	$format    = trizen_get_option( 'datetime_format', '{mm}/{dd}/{yyyy}' );
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
	$format = trizen_get_option('datetime_format', '{mm}/{dd}/{yyyy}');

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
/*if (!function_exists('ts_traveler_get_option')) {
	function ts_traveler_get_option($option_id, $default = false) {
		//global $ts_traveler_cached_options;
		//if ( empty( $ts_traveler_cached_options ) ) $ts_traveler_cached_options = get_option( ts_options_id() );
		$ts_traveler_cached_options = get_option(ts_options_id());
		if (isset($ts_traveler_cached_options[$option_id]) && !empty($ts_traveler_cached_options[$option_id]))
			return $ts_traveler_cached_options[$option_id];
		return $default;
	}
}*/
function do_add_to_cart()
{
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

	$adult_number = intval( request( 'trizen_room_facility_num_of_adults', '' ) );
	if ( $adult_number <= 0 ) $adult_number = 1;

	// 	$child_number = intval( request( 'child_number', '' ) );
	// 	if ( $child_number <= 0 ) $child_number = 0;

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
	$adult    = intval( get_post_meta( $room_origin, 'trizen_room_facility_num_of_adults', true ) );
	if ( $adult == 0 ) {
		$adult = 1;
	}
	// 	$children = intval( get_post_meta( $room_origin, 'children_number', true ) );

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
	$today = date( 'm/d/Y' );

	$period = dateDiff( $today, $check_in );

	$booking_min_day = intval( get_post_meta( $item_id, 'min_book_room', true ) );
	$compare         = dateCompare( $today, $check_in );

	$booking_period = get_post_meta( $item_id, 'hotel_booking_period', true );
//	if ( empty( $booking_period ) || $booking_period <= 0 ) $booking_period = 0;

	/*if ( $compare < 0 ) {
		set_message( __( 'You can not set check-in date in the past', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}*/
	/*if ( $period < $booking_period ) {
		set_message( sprintf( _n( 'This hotel allow minimum booking is %d day', 'This hotel allow minimum booking is %d day(s)', $booking_period, 'trizen-helper' ), $booking_period ), 'danger' );
		$pass_validate = false;

		return false;
	}*/

	/*if ( $booking_min_day and $booking_min_day > dateDiff( $check_in, $check_out ) ) {
		set_message( sprintf( _n( 'Please book at least %d day in total', 'Please book at least %d days in total', $booking_min_day,'trizen-helper' ), $booking_min_day ), 'danger' );
		$pass_validate = false;

		return false;
	}*/

	/**
	 * Validate Guest Name
	 *
	 * @since  2.1.2
	 * @author dannie
	 */
	$partner_create_booking = request('add_booking_partner_field');
	if ( !st_validate_guest_name( $room_id, $adult_number, 0 ) && empty($partner_create_booking)) {
		set_message( esc_html__( 'Please enter the Guest Name', 'trizen-helper' ), 'danger' );
		$pass_validate = false;

		return false;
	}
 	/* end */

 	/*$numberday     = dateDiff( $check_in, $check_out );
 	if ( get_post_meta( $room_origin, 'price_by_per_person', true ) == 'on' ) {
 		$item_price = floatval( get_post_meta( $room_origin, 'adult_price', true ) ) * floatval( $adult_number ) * $numberday + floatval( get_post_meta( $room_origin, 'child_price', true ) ) * floatval( $child_number ) * $numberday;
 	} else {*/
 		$item_price = floatval( get_post_meta( $room_origin, 'price', true ) );
 	/*}*/
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
// 		'child_number'    => $child_number,
 		'extras'          => $extras,
 		'extra_price'     => $extra_price,
// 		'commission'      => TravelHelper::get_commission( $item_id ),
// 		'discount_rate'   => $discount_rate,
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



/**
 * Create new Woocommerce Product by cart item information
 *
 *
 * @since 1.1.1
 * */
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


/**
 * Add product to cart by product id
 *
 * @since 1.1.1
 * */
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

/**
 *
 *
 *
 * @update 1.1.3
 * */
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




