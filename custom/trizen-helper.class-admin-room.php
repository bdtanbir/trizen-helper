<?php
function _get_currency_book_history( $post_id ) {
	$st_is_woocommerce_checkout = apply_filters( 'ts_is_woocommerce_checkout', false );
	if ( $st_is_woocommerce_checkout ) {
		global $wpdb;
		$querystr    = "SELECT meta_value FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
                                    WHERE
                                    1=1
                                    AND order_item_id = '{$post_id}'
                                    AND meta_key = '_ts_currency'";
		$st_currency = $wpdb->get_row( $querystr, OBJECT );
		if ( !empty( $st_currency->meta_value ) ) {
			return $st_currency->meta_value;
		}
	} else {
		$currency = get_post_meta( $post_id, 'currency', true );
		if ( isset( $currency[ 'symbol' ] ) ) {
			return $currency[ 'symbol' ];
		}
	}

	return null;
}

function format_money_raw( $money = '', $symbol = false, $precision = 2, $template = null )
{
	if ( $money == 0 ) {
		return esc_html__( "Free", 'trizen-helper' );
	}

	/*if ( !$symbol ) {
		$symbol = self::get_current_currency( 'symbol' );
	}*/

	if ( $precision ) {
		$money = round( $money, $precision );
	}
//	if ( !$template ) $template = self::get_current_currency( 'booking_currency_pos' );

	if ( !$template ) {
		$template = 'left';
	}

	$money = number_format( (float)$money, $precision );

	switch ( $template ) {
		case "right":
			$money_string = $money . $symbol;
			break;
		case "left_space":
			$money_string = $symbol . " " . $money;
			break;

		case "right_space":
			$money_string = $money . " " . $symbol;
			break;
		case "left":
		default:
			$money_string = $symbol . $money;
			break;
	}

	return $money_string;
}
if (!function_exists('ts_guest_title_to_text')) {

	function ts_guest_title_to_text($title_id) {
		switch ($title_id) {
			case "mr":
				return esc_html__('Mr', 'trizen-helper');
				break;
			case "mrs":
				return esc_html__('Mrs', 'trizen-helper');
				break;
			case "miss":
				return esc_html__('Miss', 'trizen-helper');
				break;
		}
	}

}
if (!function_exists('ts_admin_print_order_item_guest_name')) {

	function ts_admin_print_order_item_guest_name($data) {
		if (!empty($data['guest_name'])) {
			?>
			<div class="form-row">
				<label class="form-label" for="">
                    <?php esc_html_e('Guest Name', 'trizen-helper') ?>
                </label>
				<div class="controls">
					<?php
					$guest_title = isset($data['guest_title']) ? $data['guest_title'] : [];
					$html        = [];
					foreach ($data['guest_name'] as $k => $name) {
						$str    = isset($guest_title[$k]) ? ts_guest_title_to_text($guest_title[$k]) . ' ' : '';
						$str   .= $name;
						$html[] = $str;
					}
					echo implode(', ', $html);
					?>
				</div>
			</div>

			<?php
		}
	}

}



add_action('init', 'ts_register_location_tax');
function ts_register_location_tax() {
    $booking_type = apply_filters('ts_booking_post_type', [
        'ts_hotel',
        'hotel_room'
    ]);
}

/* Room Booking End */




