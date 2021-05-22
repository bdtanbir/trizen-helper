<?php



add_action( 'wp_ajax_add_price_inventory', 'add_price_inventory_hotels' );
add_action( 'wp_ajax_trizen_calendar_bulk_edit_form', 'trizen_calendar_bulk_edit_form' );
add_action( 'wp_ajax_ts_add_room_number_inventory', 'ts_add_room_number_inventory' );
add_action( 'wp_ajax_ts_fetch_inventory', 'ts_fetch_inventory' );

function ts_fetch_inventory() {
	$post_id = post('post_id', '');
	if (get_post_type($post_id) == 'ts_hotel') {
		$start = strtotime(post('start', ''));
		$end   = strtotime(post('end', ''));
		if ($start > 0 && $end > 0) {
			$args = [
				'post_type'      => 'hotel_room',
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'     => 'trizen_hotel_room_select',
						'value'   => $post_id,
						'compare' => '='
					]
				]
			];
			if (!current_user_can('administrator')) {
				$args['author'] = get_current_user_id();
			}
			$rooms = [];
			$query = new WP_Query($args);
			while ($query->have_posts()): $query->the_post();
				$rooms[] = [
					'id'   => get_the_ID(),
					'name' => get_the_title()
				];
			endwhile;
			wp_reset_postdata();
			$datarooms = [];
			if (!empty($rooms)) {
				foreach ($rooms as $key => $value) {
					$datarooms[] = featch_dataroom($post_id, $value['id'], $value['name'], $start, $end);
				}
			}
			echo json_encode([
				'status' => 1,
				'rooms'  => $datarooms
			]);
			die;
		}
	}
	echo json_encode([
		'status'  => 0,
		'message' => __('Can not fetch data', 'trizen-helper'),
		'rooms'   => ''
	]);
	die;
}

function featch_dataroom($hotel_id, $post_id, $post_name, $start, $end)
{
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
	$return   = [
		'name'                => esc_html($post_name),
		'values'              => [],
		'id'                  => $post_id,
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
					if ($allow_fullday == 'on') {
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
					'from'                => "/Date({$date})/",
					'to'                  => "/Date({$date})/",
					'label'               => $number_room - $ordered,
					'desc'                => sprintf(__('%s left', 'trizen-helper'), $number_room - $ordered),
					'customClass'         => 'ganttBlue',
					'price'               => $price,
					'adult_price'         => $adult_price,
					'child_price'         => $child_price,
					'price_by_per_person' => $price_by_per_person
				];
			} else {
				$return['values'][] = [
					'from'                => "/Date({$date})/",
					'to'                  => "/Date({$date})/",
					'label'               => __('O', 'trizen-helper'),
					'desc'                => __('Out of stock', 'trizen-helper'),
					'customClass'         => 'ganttOrange',
					'price'               => $price,
					'adult_price'         => $adult_price,
					'child_price'         => $child_price,
					'price_by_per_person' => $price_by_per_person
				];
			}
		} else {
			$return['values'][] = [
				'from'                => "/Date({$date})/",
				'to'                  => "/Date({$date})/",
				'label'               => __('N', 'trizen-helper'),
				'desc'                => __('Not Available', 'trizen-helper'),
				'customClass'         => 'ganttRed',
				'price'               => $price,
				'adult_price'         => $adult_price,
				'child_price'         => $child_price,
				'price_by_per_person' => $price_by_per_person
			];
		}
	}
	return $return;
}

function ts_add_room_number_inventory() {
	$room_id     = post( 'room_id', '' );
	$number_room = post( 'number_room', '' );

	$current_user = wp_get_current_user();
	$roles        = $current_user->roles;
	$role         = array_shift( $roles );

	if ( $role != 'administrator' && $role != 'partner' ) {
		$return = [
			'status'  => 0,
			'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
		];
		echo json_encode( $return );
		die;
	} else {
		if ( $role == 'partner' ) {
			$current_user_id = $current_user->ID;
			$post            = get_post( $room_id );
			$authid          = $post->post_author;
			if ( $current_user_id != $authid ) {
				$return = [
					'status'  => 0,
					'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
				];
				echo json_encode( $return );
				die;
			}
		}
	}

	if ( get_post_type( $room_id ) != 'hotel_room' ) {
		$return = [
			'status'  => 0,
			'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
		];
		echo json_encode( $return );
		die;
	}

	if ( $room_id < 0 || $room_id == '' || !is_numeric( $room_id ) ) {
		$return = [
			'status'  => 0,
			'message' => esc_html__( 'Room is invalid!', 'trizen-helper' ),
		];
		echo json_encode( $return );
		die;
	}

	if ( $number_room < 0 || $number_room == '' || !is_numeric( $number_room ) ) {
		$return = [
			'status'  => 0,
			'message' => esc_html__( 'Number of room is invalid!', 'trizen-helper' ),
		];
		echo json_encode( $return );
		die;
	}
	$res = update_post_meta( $room_id, 'number_room', $number_room );

	//Update number room in available table
	$update_number_room = TS_Hotel_Room_Availability::inst()
                                    ->where( 'post_id', $room_id )
                                    ->update( [ 'number' => $number_room ] );

	if ( $res && $update_number_room > 0 ) {
		$return = [
			'status'  => 1,
			'message' => esc_html__( 'Update success!', 'trizen-helper' ),
		];
		echo json_encode( $return );
		die;
	} else {
		$return = [
			'status'  => 0,
			'message' => esc_html__( 'Can not set number for room', 'trizen-helper' )
		];
		echo json_encode( $return );
		die;
	}
}

function trizen_get_availability( $post_id = '', $check_in = '', $check_out = '', $table = null ) {
	if(empty($table))
		$table = 'ts_availability';
	global $wpdb;

	$table = $wpdb->prefix . $table;

	$sql = "SELECT * FROM {$table} WHERE post_id = {$post_id} AND ( ( CAST( check_in AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED) AND CAST( check_in AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) OR ( CAST( check_out AS UNSIGNED ) >= CAST( {$check_in} AS UNSIGNED ) AND ( CAST( check_out AS UNSIGNED ) <= CAST( {$check_out} AS UNSIGNED ) ) ) )";

	$result = $wpdb->get_results( $sql, ARRAY_A );

	$return = [];

	if ( !empty( $result ) ) {
		foreach ( $result as $item ) {
			$return[] = [
				'id'             => $item[ 'id' ],
				'post_id'        => $item[ 'post_id' ],
				'check_in'       => date( 'Y-m-d', $item[ 'check_in' ] ),
				'check_out'      => date( 'Y-m-d', strtotime( '+1 day', $item[ 'check_out' ] ) ),
				'price'          => (float)$item[ 'price' ],
				'adult_price'    => isset($item[ 'adult_price' ]) ? (float)$item[ 'adult_price' ] : '' ,
				'children_price' => isset($item[ 'child_price' ]) ? (float)$item[ 'child_price' ] : '',
				'infant_price'   => isset($item[ 'infant_price' ]) ? (float)$item[ 'infant_price' ] : '',
				'status'         => $item[ 'status' ],
				'groupday'       => isset($item[ 'groupday' ]) ? $item[ 'groupday' ] : '',
			];
			if($table == 'ts_tour_availability' or $table == 'ts_activity_availability')
				$return['starttime'] = $item['starttime'];
		}
	}

	return $return;
}
function trizen_split_availability( $result = [], $check_in = '', $check_out = '' ) {
	$return = [];

	if ( !empty( $result ) ) {
		foreach ( $result as $item ) {
			$check_in  = (int)$check_in;
			$check_out = (int)$check_out;

			if(isset($item[ 'start' ]) && isset($item[ 'start' ])) {
				$start = strtotime( $item['start'] );
				$end   = strtotime( '-1 day', strtotime( $item['end'] ) );

				if ( $start < $check_in && $end >= $check_in ) {
					$return['insert'][] = [
						'post_id'   => $item['post_id'],
						'check_in'  => strtotime( $item['check_in'] ),
						'check_out' => strtotime( '-1 day', $check_in ),
						'price'     => (float) $item['price'],
						'status'    => $item['status'],
						'groupday'  => $item['groupday'],
					];
				}

				if ( $start <= $check_out && $end > $check_out ) {
					$return['insert'][] = [
						'post_id'   => $item['post_id'],
						'check_in'  => strtotime( '+1 day', $check_out ),
						'check_out' => strtotime( '-1 day', strtotime( $item['check_out'] ) ),
						'price'     => (float) $item['price'],
						'status'    => $item['status'],
						'groupday'  => $item['groupday'],
					];
				}
			}

			$return['delete'][] = [
				'id' => $item['id']
			];
		}
	}
	return $return;
}
function traveler_delete_availability( $id = '', $table = null ) {
	if(empty($table))
		$table = 'ts_availability';
	global $wpdb;

	$table = $wpdb->prefix . $table;

	$wpdb->delete(
		$table,
		[
			'id' => $id
		]
	);
}
function trizen_insert_availability( $post_id = '', $check_in = '', $check_out = '', $price = '', $adult_price = '', $children_price = '', $infant_price = '', $starttime = '', $status = '', $group_day = '', $table = null ) {
	if(empty($table))
		$table = 'ts_availability';
	global $wpdb;

	if ( $group_day == 1 ) {
		$data_insert  = [
			'post_id'      => $post_id,
			'check_in'     => $check_in,
			'check_out'    => $check_out,
			'price'        => $price,
			'adult_price'  => $adult_price,
			'child_price'  => $children_price,
			'infant_price' => $infant_price,
			'status'       => $status,
			'groupday'     => 1,
		];
		if( $table == 'ts_rental_availability' ){
			unset($data_insert['adult_price']);
			unset($data_insert['child_price']);
			unset($data_insert['infant_price']);
		}
		if ( $table == 'ts_room_availability' ) {
			unset($data_insert['infant_price']);
		}
		if( $table == 'ts_room_availability' ){
			$parent_id = get_post_meta($post_id, 'trizen_hotel_room_select', true);
			unset($data_insert['groupday']);
			$data_insert['post_type']      = 'hotel_room';
			$data_insert['number']         = get_post_meta($post_id, 'number_room', true);
			$data_insert['allow_full_day'] = get_post_meta($post_id, 'allow_full_day', true);
			$data_insert['booking_period'] = get_post_meta($parent_id, 'hotel_booking_period', true);
			$data_insert['adult_number']   = get_post_meta($post_id, 'adult_number', true);
			$data_insert['child_number']   = get_post_meta($post_id, 'children_number', true);
			$data_insert['is_base']        = 0;
			$data_insert['parent_id']      = $parent_id;
		}
		if($table == 'ts_tour_availability' or $table == 'ts_activity_availability') {
			$data_insert['starttime'] = $starttime;
			$data_insert['is_base'] = 0;
		}
		$wpdb->insert(
			$wpdb->prefix . $table,
			$data_insert
		);
	} else {
		for ( $i = $check_in; $i <= $check_out; $i = strtotime( '+1 day', $i ) ) {
			$data_insert = [
				'post_id'      => $post_id,
				'check_in'     => $i,
				'check_out'    => $i,
				'price'        => $price,
				'adult_price'  => $adult_price,
				'child_price'  => $children_price,
				'infant_price' => $infant_price,
				'status'       => $status,
				'groupday'     => 0,
			];

			if( $table == 'ts_rental_availability' ){
				unset($data_insert['adult_price']);
				unset($data_insert['child_price']);
				unset($data_insert['infant_price']);
			}
			if ( $table == 'ts_room_availability' ) {
				unset($data_insert['infant_price']);
			}
			if($table == 'ts_room_availability'){
				$parent_id = get_post_meta($post_id, 'trizen_hotel_room_select', true);
				unset($data_insert['groupday']);
				$data_insert['post_type']      = 'hotel_room';
				$data_insert['number']         = get_post_meta($post_id, 'number_room', true);
				$data_insert['allow_full_day'] = get_post_meta($post_id, 'allow_full_day', true);
				$data_insert['booking_period'] = get_post_meta($parent_id, 'hotel_booking_period', true);
				$data_insert['adult_number']   = get_post_meta($post_id, 'adult_number', true);
				$data_insert['child_number']   = get_post_meta($post_id, 'children_number', true);
				$data_insert['is_base']        = 0;
				$data_insert['parent_id']      = $parent_id;
			}
			if($table == 'ts_tour_availability' or $table == 'ts_activity_availability'){
				$data_insert['starttime'] = $starttime;
				$data_insert['is_base']   = 0;
			}
			$wpdb->insert(
				$wpdb->prefix . $table,
				$data_insert
			);
		}
	}
	return (int)$wpdb->insert_id;
}
function insert_calendar_bulk( $data, $posts_per_page, $total, $current_page, $all_days, $post_id )
{
	$post_type = get_post_type($post_id);
	$table     = '';
	switch ($post_type){
		case 'ts_tours':
			$table = 'ts_tour_availability';
			break;
		case 'ts_activity':
			$table = 'ts_activity_availability';
			break;
		case 'hotel_room':
			$table = 'ts_room_availability';
			break;
		case 'ts_rental':
			$table = 'ts_rental_availability';
			break;
	}

	$start = ( $current_page - 1 ) * $posts_per_page;

	$end = ( $current_page - 1 ) * $posts_per_page + $posts_per_page - 1;

	if ( $end > $total - 1 ) $end = $total - 1;

	if ( $data[ 'groupday' ] == 0 ) {
		for ( $i = $start; $i <= $end; $i++ ) {

			$data[ 'start' ] = $all_days[ $i ];
			$data[ 'end' ]   = $all_days[ $i ];

			/*  Delete old item */
			$result = trizen_get_availability( $post_id, $all_days[ $i ], $all_days[ $i ], $table );
			$split  = trizen_split_availability( $result, $all_days[ $i ], $all_days[ $i ] );
			if ( isset( $split[ 'delete' ] ) && !empty( $split[ 'delete' ] ) ) {
				foreach ( $split[ 'delete' ] as $item ) {
					traveler_delete_availability( $item[ 'id' ], $table );
				}
			}
			/*  .End */

			if(!isset($data['starttime']))
				$data['starttime'] = '';

			trizen_insert_availability( $data[ 'post_id' ], $data[ 'start' ], $data[ 'end' ], $data[ 'price' ], $data[ 'adult_price' ], $data[ 'children_price' ], $data[ 'infant_price' ], $data[ 'starttime' ], $data[ 'status' ], $data[ 'groupday' ] , $table);
		}
	} else {
		for ( $i = $start; $i <= $end; $i++ ) {
			$data[ 'start' ] = $all_days[ $i ][ 'min' ];
			$data[ 'end' ]   = $all_days[ $i ][ 'max' ];
			/*  Delete old item */
			$result = trizen_get_availability( $post_id, $all_days[ $i ][ 'min' ], $all_days[ $i ][ 'max' ], $table );
			$split  = trizen_split_availability( $result, $all_days[ $i ][ 'min' ], $all_days[ $i ][ 'max' ] );
			if ( isset( $split[ 'delete' ] ) && !empty( $split[ 'delete' ] ) ) {
				foreach ( $split[ 'delete' ] as $item ) {
					traveler_delete_availability( $item[ 'id' ], $table );
				}
			}
			/*  .End */

			if(!isset($data['starttime']))
				$data['starttime'] = '';

			trizen_insert_availability( $data[ 'post_id' ], $data[ 'start' ], $data[ 'end' ], $data[ 'price' ], $data[ 'adult_price' ], $data[ 'children_price' ], $data[ 'infant_price' ], $data[ 'starttime' ], $data[ 'status' ], $data[ 'groupday' ], $table );
		}
	}


	$next_page = (int)$current_page + 1;

	$progress = ( $current_page / $total ) * 100;

	$return = [
		'all_days'       => $all_days,
		'current_page'   => $next_page,
		'posts_per_page' => $posts_per_page,
		'total'          => $total,
		'status'         => 2,
		'data'           => $data,
		'progress'       => $progress,
		'post_id'        => $post_id,
	];

	return $return;
}
function change_allday_to_group( $all_days = [] )
{
	$return_tmp = [];
	$return     = [];

	foreach ( $all_days as $item ) {
		$month = date( 'm', $item );
		if ( !isset( $return_tmp[ $month ] ) ) {
			$return_tmp[ $month ][ 'min' ] = $item;
			$return_tmp[ $month ][ 'max' ] = $item;
		} else {
			if ( $return_tmp[ $month ][ 'min' ] > $item ) {
				$return_tmp[ $month ][ 'min' ] = $item;
			}
			if ( $return_tmp[ $month ][ 'max' ] < $item ) {
				$return_tmp[ $month ][ 'max' ] = $item;
			}
		}
	}

	foreach ( $return_tmp as $key => $val ) {
		$return[] = [
			'min' => $val[ 'min' ],
			'max' => $val[ 'max' ],
		];
	}

	return $return;
}
function trizen_calendar_bulk_edit_form() {
	$post_id = (int)post( 'post_id', 0 );
	if ( $post_id > 0 ) {

		if ( isset( $_POST[ 'all_days' ] ) && !empty( $_POST[ 'all_days' ] ) ) {
			$data           = post( 'data', '' );
			$all_days       = post( 'all_days', '' );
			$posts_per_page = (int)post( 'posts_per_page', '' );
			$current_page   = (int)post( 'current_page', '' );
			$total          = (int)post( 'total', '' );
			if ( $current_page > ceil( $total / $posts_per_page ) ) {
				echo json_encode( [
					'status'  => 1,
					'message' => '<div class="text-success">' . esc_html__( 'Added successful.', 'trizen-helper' ) . '</div>'
				] );
				die;
			} else {
				$return = insert_calendar_bulk( $data, $posts_per_page, $total, $current_page, $all_days, $post_id );
				echo json_encode( $return );
				die;
			}
		}

		$day_of_week  = post( 'day-of-week', '' );
		$day_of_month = post( 'day-of-month', '' );

		$array_month = [
			'January'   => '1',
			'February'  => '2',
			'March'     => '3',
			'April'     => '4',
			'May'       => '5',
			'June'      => '6',
			'July'      => '7',
			'August'    => '8',
			'September' => '9',
			'October'   => '10',
			'November'  => '11',
			'December'  => '12',
		];

		$months         = post( 'months', '' );
		$years          = post( 'years', '' );
		$price          = post( 'price_bulk', 0 );
		$adult_price    = post( 'adult-price_bulk', 0 );
		$children_price = post( 'children-price_bulk', 0 );
		$infant_price   = post( 'infant-price_bulk', 0 );

		if($price == '')
			$price = 0;
		if($adult_price == '')
			$adult_price = 0;
		if($children_price == '')
			$children_price = 0;
		if($infant_price == '')
			$infant_price = 0;

		$start_time_arr = request( 'starttime', '' );
		$start_time_str = '';
		if(isset($start_time_arr) && !empty($start_time_arr))
			$start_time_str = implode(', ', $start_time_arr);

		if ( !is_numeric( $price ) || !is_numeric( $adult_price ) || !is_numeric( $children_price ) || !is_numeric( $infant_price ) ) {
			echo json_encode( [
				'status'  => 0,
				'message' => '<div class="text-error">' . esc_html__( 'The price field is not a number.', 'trizen-helper' ) . '</div>'
			] );
			die;
		}
		$price          = (float)$price;
		$adult_price    = (float)$adult_price;
		$children_price = (float)$children_price;
		$infant_price   = (float)$infant_price;

		$status = post( 'status', 'available' );

		$group_day = post( 'calendar_groupday', 0 );

		/*  Start, End is a timestamp */
		$all_years  = [];
		$all_months = [];
		$all_days   = [];

		if ( !empty( $years ) ) {

			sort( $years, 1 );

			foreach ( $years as $year ) {
				$all_years[] = $year;
			}

			if ( !empty( $months ) ) {

				foreach ( $months as $month ) {
					foreach ( $all_years as $year ) {
						$all_months[] = $month . ' ' . $year;
					}
				}

				if ( !empty( $day_of_week ) && !empty( $day_of_month ) ) {
					// Each day in month
					foreach ( $day_of_month as $day ) {
						// Each day in week
						foreach ( $day_of_week as $day_week ) {
							// Each month year
							foreach ( $all_months as $month ) {
								$time = strtotime( $day . ' ' . $month );

								if ( date( 'l', $time ) == $day_week ) {
									$all_days[] = $time;
								}
							}
						}
					}
				} elseif ( empty( $day_of_week ) && empty( $day_of_month ) ) {
					foreach ( $all_months as $month ) {
						for ( $i = strtotime( 'first day of ' . $month ); $i <= strtotime( 'last day of ' . $month ); $i = strtotime( '+1 day', $i ) ) {
							$all_days[] = $i;
						}
					}
				} elseif ( empty( $day_of_week ) && !empty( $day_of_month ) ) {

					foreach ( $day_of_month as $day ) {
						foreach ( $all_months as $month ) {
							$month_tmp = trim( $month );
							$month_tmp = explode( ' ', $month );

							$num_day = cal_days_in_month( CAL_GREGORIAN, $array_month[ $month_tmp[ 0 ] ], $month_tmp[ 1 ] );

							if ( $day <= $num_day ) {
								$all_days[] = strtotime( $day . ' ' . $month );
							}
						}
					}
				} elseif ( !empty( $day_of_week ) && empty( $day_of_month ) ) {
					foreach ( $day_of_week as $day ) {
						foreach ( $all_months as $month ) {
							for ( $i = strtotime( 'first ' . $day . ' of ' . $month ); $i <= strtotime( 'last ' . $day . ' of ' . $month ); $i = strtotime( '+1 week', $i ) ) {
								$all_days[] = $i;
							}
						}
					}
				}


				if ( !empty( $all_days ) ) {
					$posts_per_page = 10;

					if ( $group_day == 1 ) {
						$all_days = change_allday_to_group( $all_days );
					}

					$total = count( $all_days );

					$current_page = 1;

					$data = [
						'post_id'        => $post_id,
						'status'         => $status,
						'groupday'       => $group_day,
						'price'          => $price,
						'adult_price'    => $adult_price,
						'children_price' => $children_price,
						'infant_price'   => $infant_price,
					];

					if($start_time_str != '')
						$data['starttime'] = $start_time_str;

					$return = insert_calendar_bulk( $data, $posts_per_page, $total, $current_page, $all_days, $post_id );

					echo json_encode( $return );
					die;
				}
			} else {
				echo json_encode( [
					'status'  => 0,
					'message' => '<div class="text-error">' . esc_html__( 'The months field is required.', 'trizen-helper' ) . '</div>'
				] );
				die;
			}

		} else {
			echo json_encode( [
				'status'  => 0,
				'message' => '<div class="text-error">' . esc_html__( 'The years field is required.', 'trizen-helper' ) . '</div>'
			] );
			die;
		}
	} else {
		echo json_encode( [
			'status'  => 0,
			'message' => '<div class="text-error">' . esc_html__( 'The room field is required.', 'trizen-helper' ) . '</div>'
		] );
		die;
	}
}



function ts_origin_id( $post_id, $service_type = 'post' ){
    if ( function_exists( 'wpml_object_id_filter' ) ) {
        global $sitepress;
        $a = wpml_object_id_filter( $post_id, $service_type, true, $sitepress->get_default_language() );

        return $a;
    } else {
        return $post_id;
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
                'id'         => $item[ 'id' ],
                'post_id'    => $item[ 'post_id' ],
                'start'      => date( 'Y-m-d', $item[ 'check_in' ] ),
                'end'        => date( 'Y-m-d', strtotime( '+1 day', $item[ 'check_out' ] ) ),
                'price'      => (float)$item[ 'price' ],
                'price_text' => format_money( $item[ 'price' ] ),
                'status'     => $item[ 'status' ],
                'adult_price' => floatval( $item['adult_price'] ),
                'child_price' => floatval( $item['child_price'] ),
            ];

            $return[] = $item_array;
        }
    }

    return $return;
}










