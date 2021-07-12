<?php


// search Load post hotel filter ajax location
add_action('wp_ajax_ts_filter_hotel_ajax_location', 'ts_filter_hotel_ajax_location');
add_action('wp_ajax_nopriv_ts_filter_hotel_ajax_location', 'ts_filter_hotel_ajax_location');
// search Load post hotel filter ajax
add_action( 'wp_ajax_ts_filter_hotel_ajax', 'ts_filter_hotel_ajax' );
add_action( 'wp_ajax_nopriv_ts_filter_hotel_ajax','ts_filter_hotel_ajax' );

function setQueryHotelSearch() {
	$page_number = get('page');
	global $wp_query, $ts_search_query;
	alter_search_query();
	set_query_var('paged', $page_number);
	$paged = $page_number;
	$args = [
		'post_type'   => 'ts_hotel',
		's'           => '',
		'post_status' => ['publish'],
		'paged'       => $paged
	];
	query_posts($args);
	$ts_search_query = $wp_query;
	remove_alter_search_query();
}

function removeSearchServiceLocationByAuthor($query) {
	$query->set('author', '');
	return $query;
}


function ts_filter_hotel_ajax_location() {
	$page_number             = get('page');
	$posts_per_page          = get('posts_per_page');
	$id_location             = get('id_location');
	$_REQUEST['location_id'] = get('id_location');
	global $wp_query, $ts_search_query;
	add_filter('pre_get_posts', 'removeSearchServiceLocationByAuthor');
	setQueryHotelSearch();
	add_filter('pre_get_posts', 'removeSearchServiceLocationByAuthor');
	$query_service = $ts_search_query;
	ob_start();
	?>
	<div class="row row-wrapper">
		<?php if ($query_service->have_posts()) {
			while ($query_service->have_posts()) {
				$query_service->the_post();
				require_once TRIZEN_HELPER_PATH . 'inc/hotel/search/hotel-grid.php';
			}
		} else {
			echo '<div class="col-xs-12">';
			esc_html_e('No Hotel', 'trizen-helper');
			echo '</div>';
		}
		wp_reset_postdata(); ?>
	</div>
	<?php
	$ajax_filter_content = ob_get_contents();
	ob_clean();
	ob_end_flush();
	ob_start();
	TravelHelper::paging(false, false); ?>
	<span class="count-string">
            <?php
            if ($query_service->found_posts):
                $posts_per_page = $posts_per_page;
                if (!$page_number) {
                    $page = 1;
                } else {
                    $page = $page_number;
                }
                $last = (int)$posts_per_page * (int)($page);
                if ($last > $query_service->found_posts) $last = $query_service->found_posts;
                echo sprintf(__('%d - %d of %d ', 'trizen-helper'), (int)$posts_per_page * ($page - 1) + 1, $last, $query_service->found_posts);
                echo ($query_service->found_posts == 1) ? __('Hotel', 'trizen-helper') : __('Hotels', 'trizen-helper');
            endif;
            ?>
        </span>
	<?php
	$ajax_filter_pag = ob_get_contents();
	ob_clean();
	ob_end_flush();
	$result = [
		'content' => $ajax_filter_content,
		'pag'     => $ajax_filter_pag,
		'page'    => $page_number,
	];
	wp_reset_query();
	wp_reset_postdata();
	echo json_encode($result);
	die;
}

function ts_filter_hotel_ajax() {
	$page_number   = get('page');
	$style         = get('layout');
	$format        = get('format');
	$is_popup_map  = get('is_popup_map');
	$half_map_show = get('half_map_show');
	$fullwidth     = get('fullwidth');
	if (empty($half_map_show))
		$half_map_show = 'yes';
	$popup_map = '';
	if ($is_popup_map) {
		$popup_map = '<div class="row list-style">';
	}
	if (!in_array($format, ['normal', 'halfmap', 'popupmap']))
		$format = 'normal';
	global $wp_query, $ts_search_query;
	setQueryHotelSearch();
	$query = $ts_search_query;
	//Map
	$map_lat_center = 0;
	$map_lng_center = 0;
	if (request('location_id')) {
		$location_id = post_origin(request('location_id'), 'location');
		$map_lat_center = get_post_meta($location_id, 'lat', true);
		$map_lng_center = get_post_meta($location_id, 'lng', true);
	}
	$data_map = [];
	$stt = 0;
	//End map
	ob_start();
//            echo st()->load_template('layouts/modern/common/loader', 'content');
	if (!isset($style) || empty($style)) $style = 'grid';
	switch ($format) {
		case 'halfmap':
			echo ($style == 'grid') ? '<div class="row">' : '<div class="row list-style">';
			break;
		default:
			echo ($style == 'grid') ? '<div class="row row-wrapper">' : '<div class="style-list">';
			break;
	}
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
//                    if ($fullwidth) {
//                        echo 'I am FullWidth Loop';
//                        echo st()->load_template('layouts/modern/hotel/elements/loop/' . esc_attr($format), $style, ['show_map' => $half_map_show, 'fullwidth' => true]);
//                    } else {
			require_once TRIZEN_HELPER_PATH .'inc/hotel/search/hotel-grid.php';
//                        echo st()->load_template('layouts/modern/hotel/elements/loop/' . esc_attr($format), $style, ['show_map' => $half_map_show]);
//                    }
//                    if ($is_popup_map)
//                        $popup_map .= st()->load_template('layouts/modern/hotel/elements/loop/popupmap');
			//Map
			$map_lat = get_post_meta(get_the_ID(), 'lat', true);
			$map_lng = get_post_meta(get_the_ID(), 'lng', true);
			if (!empty($map_lat) and !empty($map_lng)) {
				if (empty($map_lat_center)) $map_lat_center = $map_lat;
				if (empty($map_lng_center)) $map_lng_center = $map_lng;
				$post_type = get_post_type();
				$data_map[$stt]['id'] = get_the_ID();
				$data_map[$stt]['name'] = get_the_title();
				$data_map[$stt]['post_type'] = $post_type;
				$data_map[$stt]['lat'] = $map_lat;
				$data_map[$stt]['lng'] = $map_lng;
				$post_type_name = get_post_type_object($post_type);
				$post_type_name->label;
				$data_map[$stt]['content_html'] = preg_replace('/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_PATH .'inc/hotel/search/hotel-grid.php');
				$data_map[$stt]['content_adv_html'] = preg_replace('/^\s+|\n|\r|\s+$/m', '', TRIZEN_HELPER_PATH .'inc/hotel/search/hotel-grid.php');
				$stt++;
			}
			//End map
		}
	} else {
		if ($is_popup_map)
			$popup_map .= '<div class="col-xs-12">' . esc_html__('Hotel None', 'trizen-helper') . '</div>';
		echo ($style == 'grid') ? '<div class="col-xs-12">' : '';
		require_once TRIZEN_HELPER_PATH .'inc/hotel/search/loop-room-none.php';
		echo '</div>';
	}
	echo '</div>';
	$ajax_filter_content = ob_get_contents();
	ob_clean();
	ob_end_flush();
	if ($is_popup_map) {
		$popup_map .= '</div>';
	}
	ob_start();
	TravelHelper::paging(false, false, true); ?>
	<span class="count-string">
                    <?php
                    if (!empty($ts_search_query)) {
	                    $wp_query = $ts_search_query;
                    }
                    if ($wp_query->found_posts):
	                    $page           = get_query_var('paged');
	                    $posts_per_page = get_query_var('posts_per_page');
	                    if (!$page) $page = 1;
	                    $last = $posts_per_page * ($page);
	                    if ($last > $wp_query->found_posts) $last = $wp_query->found_posts;
	                    echo sprintf(__('%d - %d of %d ', 'trizen-helper'), $posts_per_page * ($page - 1) + 1, $last, $wp_query->found_posts);
	                    echo ($wp_query->found_posts == 1) ? __('Hotel', 'trizen-helper') : __('Hotels', 'trizen-helper');
                    endif;
                    ?>
                </span>
	<?php
	$ajax_filter_pag = ob_get_contents();
	ob_clean();
	ob_end_flush();
	$count = balanceTags(get_result_string()) . '<div id="btn-clear-filter" class="btn-clear-filter" style="display: none;">' . __('Clear filter', 'trizen-helper') . '</div>';
	//Map
	$map_icon = 'M Icon';
	$data_tmp = [
		'data_map'       => $data_map,
		'map_lat_center' => $map_lat_center,
		'map_lng_center' => $map_lng_center,
		'map_icon'       => $map_icon
	];
	//End map
	$result = [
		'content'       => $ajax_filter_content,
		'pag'           => $ajax_filter_pag,
		'count'         => $count,
		'page'          => $page_number,
		'content_popup' => $popup_map,
		'data_map'      => $data_tmp
	];
	wp_reset_query();
	wp_reset_postdata();
	echo json_encode($result);
	die;
}


function _get_join_query($join) {
    //if (!TravelHelper::checkTableDuplicate('ts_hotel')) return $join;
    global $wpdb;
    $table = $wpdb->prefix . 'ts_room_availability';
    $table2 = $wpdb->prefix . 'ts_hotel';
    $table3 = $wpdb->prefix . 'hotel_room';
    $disable_avai_check = get_option('disable_availability_check');
    if (!$disable_avai_check == 'on') {
        $join .= " INNER JOIN {$table} as tb ON {$wpdb->prefix}posts.ID = tb.parent_id AND status = 'available'";
        $join .= " INNER JOIN {$table3} as tb3 ON (tb.post_id = tb3.post_id and tb3.`status` IN ('publish', 'private'))";
    }
    $join .= " INNER JOIN {$table2} as tb2 ON {$wpdb->prefix}posts.ID = tb2.post_id";
    return $join;
}
function _get_where_query($where) {
    global $wpdb, $ts_search_args;
    if (!$ts_search_args) $ts_search_args = $_REQUEST;
    if (!empty($ts_search_args['ts_location'])) {
        if (empty($ts_search_args['only_featured_location']) or $ts_search_args['only_featured_location'] == 'no')
            $ts_search_args['location_id'] = $ts_search_args['ts_location'];
    }
    if (isset($ts_search_args['location_id']) && !empty($ts_search_args['location_id'])) {
        $location_id = $ts_search_args['location_id'];
        $location_id = post_origin($location_id, 'location');
        $where = TravelHelper::_ts_get_where_location($location_id, ['ts_hotel'], $where);
    } elseif (isset($_REQUEST['location_name']) && !empty($_REQUEST['location_name'])) {
        $location_name = request('location_name', '');
        $ids_location = TSHotel::_get_location_by_name($location_name);
        if (!empty($ids_location) && is_array($ids_location)) {
            foreach ($ids_location as $key => $id) {
                $ids_location[$key] = post_origin($id, 'location');
            }
            $where .= TravelHelper::_ts_get_where_location($ids_location, ['ts_hotel'], $where);
        } else {
            $where .= " AND (tb2.address LIKE '%{$location_name}%'";
            $where .= " OR {$wpdb->prefix}posts.post_title LIKE '%{$location_name}%')";
        }
    }
    if (isset($_REQUEST['item_name']) && !empty($_REQUEST['item_name'])) {
        $item_name = request('item_name', '');
        $where .= " AND {$wpdb->prefix}posts.post_title LIKE '%{$item_name}%'";
    }
    if (isset($_REQUEST['item_id']) and !empty($_REQUEST['item_id'])) {
        $item_id = request('item_id', '');
        $where .= " AND ({$wpdb->prefix}posts.ID = '{$item_id}')";
    }
    $check_in  = get('start', '');
    $check_out = get('end', '');
    if (!empty($check_in) && !empty($check_out)) {
        $check_in        = date('Y-m-d', strtotime(convertDateFormat($check_in)));
        $check_out       = date('Y-m-d', strtotime(convertDateFormat($check_out)));
        $check_in_stamp  = strtotime($check_in);
        $check_out_stamp = strtotime($check_out);
    } else {
        $check_in        = date('Y-m-d');
        $check_out       = date('Y-m-d', strtotime('+1 day'));
        $check_in_stamp  = strtotime($check_in);
        $check_out_stamp = strtotime($check_out);
    }
    if ($check_in && $check_out) {
        $today        = date('m/d/Y');
        $period       = dateDiff($today, $check_in);
        $adult_number = get('adult_number', 0);
        if (intval($adult_number) < 0) $adult_number = 0;
        $children_number = get('children_num', 0);
        if (intval($children_number) < 0) $children_number = 0;
        $number_room = get('room_num_search', 0);
        if (intval($number_room) < 0) $number_room = 0;
        $disable_avai_check = get_option('disable_availability_check');
        if (!$disable_avai_check == 'on') {
            $list_hotel = get_unavailability_hotel($check_in, $check_out, $adult_number, $children_number, $number_room);
            if (!is_array($list_hotel) || count($list_hotel) <= 0) {
                $list_hotel = "''";
            } else {
                $list_hotel = array_filter($list_hotel, function ($value) {
                    return $value !== '';
                });
                $list_hotel = implode(',', $list_hotel);
                if (!empty($list_hotel)) {
                    $check_in_rewhere = get('start', '');
                    $check_out_rewhere = get('end', '');
                    if (!empty($check_in_rewhere) || !empty($check_out_rewhere)) {
                        $where .= " AND {$wpdb->prefix}posts.ID NOT IN ({$list_hotel}) ";
                    }
                }
            }
            $where .= " AND tb.check_in >= {$check_in_stamp} AND tb.check_out <= {$check_out_stamp} ";
        }
        $where .= " AND CAST(tb2.hotel_booking_period AS UNSIGNED) <= {$period}";
    } else {
        $disable_avai_check = get_option('disable_availability_check');
        if (!$disable_avai_check == 'on') {
            $where .= " AND check_in >= UNIX_TIMESTAMP(CURRENT_DATE) ";
        }
    }
    if (isset($_REQUEST['star_rate']) && !empty($_REQUEST['star_rate'])) {
        $stars = get('star_rate', 1);
        $stars = explode(',', $stars);
        $all_star = [];
        if (!empty($stars) && is_array($stars)) {
            foreach ($stars as $val) {
                if ($val == 'zero') {
                    $val = 0;
                    for ($i = $val; $i < $val + 1; $i = $i + 0.1) {
                        $all_star[] = $i;
                    }
                } else {
                    for ($i = $val + 0.1; $i <= $val + 1.1; $i = $i + 0.1) {
                        $all_star[] = $i;
                    }
                }
            }
        }
        $list_star = implode(',', $all_star);
        if ($list_star) {
            $where .= " AND (tb2.rate_review IN ({$list_star}))";
        }
    }
    if (isset($_REQUEST['hotel_rate']) && !empty($_REQUEST['hotel_rate'])) {
        $hotel_rate = get('hotel_rate', '');
        $where .= " AND (tb2.hotel_star IN ({$hotel_rate}))";
    }
    if (isset($_REQUEST['range']) and isset($_REQUEST['location_id'])) {
        $range = get('range', '0;5');
        $rangeobj = explode(';', $range);
        $range_min = $rangeobj[0];
        $range_max = $rangeobj[1];
        $location_id = request('location_id');
        $post_type = get_query_var('post_type');
        $map_lat = (float)get_post_meta($location_id, 'lat', true);
        $map_lng = (float)get_post_meta($location_id, 'lng', true);
        global $wpdb;
        $where .= "
        AND $wpdb->posts.ID IN (
                SELECT ID FROM (
                    SELECT $wpdb->posts.*,( 6371 * acos( cos( radians({$map_lat}) ) * cos( radians( mt1.meta_value ) ) *
                                    cos( radians( mt2.meta_value ) - radians({$map_lng}) ) + sin( radians({$map_lat}) ) *
                                    sin( radians( mt1.meta_value ) ) ) ) AS distance
                                        FROM $wpdb->posts, $wpdb->postmeta as mt1,$wpdb->postmeta as mt2
                                        WHERE $wpdb->posts.ID = mt1.post_id
                                        and $wpdb->posts.ID=mt2.post_id
                                        AND mt1.meta_key = 'lat'
                                        and mt2.meta_key = 'lng'
                                        AND $wpdb->posts.post_status = 'publish'
                                        AND $wpdb->posts.post_type = '{$post_type}'
                                        AND $wpdb->posts.post_date < NOW()
                                        GROUP BY $wpdb->posts.ID HAVING distance >= {$range_min} and distance <= {$range_max}
                                        ORDER BY distance ASC
                ) as st_data
        )";
    }
    $where_room = '';
    if (!empty($_REQUEST['taxonomy_hotel_room'])) {
        $tax = request('taxonomy_hotel_room');
        if (!empty($tax) and is_array($tax)) {
            $tax_query = [];
            foreach ($tax as $key => $value) {
                if ($value) {
                    $ids = "";
                    $ids_tmp = explode(',', $value);
                    if (!empty($ids_tmp)) {
                        foreach ($ids_tmp as $k => $v) {
                            if (!empty($v)) {
                                $ids[] = $v;
                            }
                        }
                    }
                    if (!empty($ids)) {
                        $tax_query[] = [
                            'taxonomy' => $key,
                            'terms' => $ids
                        ];
                    }
                }
            }
            if (!empty($tax_query)) {
                $where_room = ' AND (';
                foreach ($tax_query as $k => $v) {
                    $ids = implode(',', $v['terms']);
                    if ($k > 0) {
                        $where_room .= " AND ";
                    }
                    $where_room .= "  (
                                            SELECT COUNT(1)
                                            FROM {$wpdb->prefix}term_relationships
                                            WHERE term_taxonomy_id IN ({$ids})
                                            AND object_id = {$wpdb->prefix}posts.ID
                                          ) = " . count($v['terms']) . "  ";
                }
                $where_room .= " ) ";
            }
        }
    }
    if (!empty($ts_search_args['only_featured_location']) and !empty($ts_search_args['featured_location'])) {
        $featured = $ts_search_args['featured_location'];
        if ($ts_search_args['only_featured_location'] == 'yes' and is_array($featured)) {
            if (is_array($featured) && count($featured)) {
                $where .= " AND (";
                $where_tmp = "";
                foreach ($featured as $item) {
                    if (empty($where_tmp)) {
                        $where_tmp .= " tb2.multi_location LIKE '%_{$item}_%'";
                    } else {
                        $where_tmp .= " OR tb2.multi_location LIKE '%_{$item}_%'";
                    }
                }
                $featured = implode(',', $featured);
                $where_tmp .= " OR tb2.id_location IN ({$featured})";
                $where .= $where_tmp . ")";
            }
        }
    }
    return $where;
}


function alter_search_query() {
    add_action( 'pre_get_posts', 'change_search_hotel_arg' );
    add_action( 'posts_fields', '_change_posts_fields' );
    add_filter( 'posts_where', '_get_where_query' );
    add_filter( 'posts_join', '_get_join_query' );
    add_filter( 'posts_orderby', '_get_order_by_query' );
    add_filter( 'posts_groupby', '_change_posts_groupby' );
}
function remove_alter_search_query() {
    remove_action( 'pre_get_posts', 'change_search_hotel_arg' );
    remove_action( 'posts_fields', '_change_posts_fields' );
    remove_filter( 'posts_where', '_get_where_query' );
    remove_filter( 'posts_join', '_get_join_query' );
    remove_filter( 'posts_orderby', '_get_order_by_query' );
    remove_filter( 'posts_groupby', '_change_posts_groupby' );
}


function _change_posts_fields($fields) {
    global $wpdb;
    $disable_avai_check = get_option('disable_availability_check');
    if (!$disable_avai_check == 'on') {
        $fields .= ', min(CAST(CASE WHEN IFNULL(tb.adult_price, 0) = 0 THEN tb.price ELSE tb.adult_price END AS DECIMAL) ) as ts_price';
    } else {
        if (is_show_min_price()) {
            $fields .= ', min(CAST(tb2.min_price as DECIMAL)) as ts_price';
        } else {
            $fields .= ', min(CAST(tb2.price_avg as DECIMAL)) as ts_price';
        }
    }
    return $fields;
}
function _change_posts_groupby($groupby) {
	global $wpdb;
	//if ( !$groupby or strpos( $wpdb->posts . '.ID', $groupby ) === false ) {
	$groupby = $wpdb->posts . '.ID ';
	if (isset($_REQUEST['price_range']) && !empty($_REQUEST['price_range'])) {
		$groupby .= " HAVING ";
		$price = get('price_range', '0;0');
		$priceobj = explode(';', $price);
		// convert to default money
		$priceobj[0] = TravelHelper::convert_money_to_default($priceobj[0]);
		$priceobj[1] = TravelHelper::convert_money_to_default($priceobj[1]);
		$groupby .= $wpdb->prepare(" ts_price >= %f ", $priceobj[0]);
		if (isset($priceobj[1])) {
			$groupby .= $wpdb->prepare(" AND ts_price <= %f ", $priceobj[1]);
		}
	}
	// }
	return $groupby;
}
function change_search_hotel_arg($query) {
    // if (is_admin() and empty($_REQUEST['is_search_map']) and empty($_REQUEST['is_search_page'])) return $query;
    /**
     * Global Search Args used in Element list and map display
     * @since 1.0
     */
    global $ts_search_args;
    if (!$ts_search_args) $ts_search_args = $_REQUEST;
    $post_type      = get_query_var('post_type');
    $posts_per_page = 12;
    if ($post_type == 'ts_hotel') {
        $query->set('author', '');
        if (get('item_name')) {
            $query->set('s', get('item_name'));
        }
        if ((empty($_REQUEST['is_search_map']) && empty($query->query['is_ts_location_list_hotel'])) or !empty($_REQUEST['is_search_page'])) {
            $query->set('posts_per_page', $posts_per_page);
        }
        $has_tax_in_element = [];
        if (is_array($ts_search_args)) {
            foreach ($ts_search_args as $key => $val) {
                if (strpos($key, 'taxonomies--') === 0 && !empty($val)) {
                    $has_tax_in_element[$key] = $val;
                }
            }
        }
        if (!empty($has_tax_in_element)) {
            $tax_query = [];
            foreach ($has_tax_in_element as $tax => $value) {
                $tax_name = str_replace('taxonomies--', '', $tax);
                if (!empty($value)) {
                    $value = explode(',', $value);
                    $tax_query[] = [
                        'taxonomy' => $tax_name,
                        'terms' => $value,
                        'operator' => 'IN',
                    ];
                }
            }
            if (!empty($tax_query)) {
                $type_filter_option_attribute = 'and';
                array_push($tax_query,array('relation' => $type_filter_option_attribute));
                $query->set('tax_query', $tax_query);
            }
        }
        $tax = request('taxonomy');
        if (!empty($tax) and is_array($tax)) {
            $tax_query = [];
            foreach ($tax as $key => $value) {
                if ($value) {
                    $value = explode(',', $value);
                    if (!empty($value) and is_array($value)) {
                        foreach ($value as $k => $v) {
                            if (!empty($v)) {
                                $v = post_origin($v, $key);
                                $ids[] = $v;
                            }
                        }
                    }
                    if (!empty($ids)) {
                        $tax_query[] = [
                            'taxonomy' => $key,
                            'terms' => $ids,
                            //'COMPARE'=>"IN",
                            'operator' => 'IN',
                        ];
                    }
                    $ids = [];
                }
            }
            $query->set('tax_query', $tax_query);
        }
        /**
         * Post In and Post Order By from Element
         * @since  1.2.5
         * @author quandq
         */
        if (!empty($ts_search_args['ts_number_ht'])) {
            $query->set('posts_per_page', $ts_search_args['ts_number_ht']);
        }
        if (!empty($ts_search_args['ts_ids'])) {
            $query->set('post__in', explode(',', $ts_search_args['ts_ids']));
            $query->set('orderby', 'post__in');
        }
        if (!empty($ts_search_args['posts_per_page'])) {
            $query->set('posts_per_page', $ts_search_args['posts_per_page']);
        }
        if (!empty($ts_search_args['ts_orderby']) and $ts_orderby = $ts_search_args['ts_orderby']) {
            if ($ts_orderby == 'sale') {
                $query->set('meta_key', 'total_sale_number');
                $query->set('orderby', 'meta_value_num');
            }
            if ($ts_orderby == 'rate') {
                $query->set('meta_key', 'rate_review');
                $query->set('orderby', 'meta_value');
            }
            if ($ts_orderby == 'discount') {
                $query->set('meta_key', 'discount_rate');
                $query->set('orderby', 'meta_value_num');
            }
            if ($ts_orderby == 'featured') {
                $query->set('meta_key', 'is_featured');
                $query->set('orderby', 'meta_value');
                $query->set('order', 'DESC');
            }
        }
        if (!empty($ts_search_args['sort_taxonomy']) and $sort_taxonomy = $ts_search_args['sort_taxonomy']) {
            if (isset($ts_search_args["id_term_" . $sort_taxonomy])) {
                $id_term = $ts_search_args["id_term_" . $sort_taxonomy];
                $tax_query[] = [
                    [
                        'taxonomy' => $sort_taxonomy,
                        'field' => 'id',
                        'terms' => explode(',', $id_term),
                        'include_children' => false
                    ],
                ];
            }
        }
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }
}
function _get_order_by_query($orderby)  {
	if (strpos($orderby, "FIELD(") !== false && (strpos($orderby, "posts.ID") !== false)) {
		return $orderby;
	}
	if ($check = get('orderby')) {
		global $wpdb;
		$meta_key = "price_avg";
		switch ($check) {
			case "price_asc":
				$orderby = ' ts_price asc';
				break;
			case "price_desc":
				$orderby = ' ts_price desc';
				break;
			case "name_asc":
				$orderby = $wpdb->posts . '.post_title';
				break;
			case "name_desc":
				$orderby = $wpdb->posts . '.post_title desc';
				break;
			case "rand":
				$orderby = ' rand()';
				break;
			case "new":
				$orderby = $wpdb->posts . '.post_modified desc';
				break;
			default:
				$is_featured = 'off';
				if (!empty($is_featured) and $is_featured == 'on') {
					$orderby = 'tb2.is_featured desc';
				} else {
					$orderby = $wpdb->posts . '.post_modified desc';
				}
				break;
		}
	} else {
		global $wpdb;
		$is_featured = 'off';
		if (!empty($is_featured) and $is_featured == 'on') {
			$orderby = 'tb2.is_featured desc';
		} else {
			$orderby = $wpdb->posts . '.post_modified desc';
		}
	}
	return $orderby;
}


function is_show_min_price() {
	$show_min_or_avg = 'avg_price';
	if ( $show_min_or_avg == 'min_price' ) return true;
	return true;
}

function get_result_string() {
	global $wp_query, $ts_search_query;
	if ($ts_search_query) {
		$query = $ts_search_query;
	} else $query = $wp_query;
	$result_string = $p1 = $p2 = $p3 = $p4 = '';
	$location_id   = get('location_id', '');
	$get_post      = get_post($location_id);
	if (!empty($location_id) and isset($get_post)) {
		$p1 = sprintf(__('%s: ', 'trizen-helper'), get_the_title($location_id));
	} elseif (request('address')) {
		$p1 = sprintf(__('%s: ', 'trizen-helper'), request('address', ''));
	}
	if ($query->found_posts) {
		if ($query->found_posts > 1) {
			$p2 = sprintf(__('%s hotels found', 'trizen-helper'), $query->found_posts);
		} else {
			$p2 = sprintf(__('%s hotel found', 'trizen-helper'), $query->found_posts);
		}
	} else {
		$p2 = __('No hotel found', 'trizen-helper');
	}
	return esc_html($p1 . $p2);
}
function get_unavailability_hotel( $check_in, $check_out, $adult_number, $children_number, $number_room = 1 ) {
    $check_in  = strtotime( $check_in );
    $check_out = strtotime( $check_out );
    $r         = [];
    $list_hotel = TS_Hotel_Room_Availability::inst()
        ->select( 'post_id, parent_id' )
        ->where( "check_in >=", $check_in )
        ->where( "check_out <=", $check_out )
        ->where( "(status = 'unavailable' OR IFNULL(adult_number, 0) < {$adult_number} OR IFNULL(child_number, 0) < {$children_number} OR (CASE WHEN number > 0 THEN IFNULL(number, 0) - IFNULL(number_booked, 0) < {$number_room} END ) )", null, true )
        ->groupby( 'post_id' )
        ->get()->result();
    if ( !empty( $list_hotel ) ) {
        foreach ( $list_hotel as $k => $v ) {
            $hotel_id = $v[ 'parent_id' ];
            //if ( !empty( $hotel_id ) )
            $r[] = $hotel_id;
        }
    }
    $freqs = array_count_values($r);
    global $wpdb;
    $sql_count_room = "SELECT room_parent, count(room_parent) as number_room FROM {$wpdb->prefix}hotel_room as ht INNER JOIN {$wpdb->prefix}posts as p ON ht.post_id = p.ID WHERE p.post_status = 'publish' GROUP By room_parent";
    $count_room_by_hotel = $wpdb->get_results($sql_count_room, ARRAY_A);
    $rs = [];
    if(!empty($count_room_by_hotel)){
        foreach ($count_room_by_hotel as $kc => $vc){
            if(isset($freqs[$vc['room_parent']])) {
                if ($freqs[$vc['room_parent']] >= $vc['number_room'])
                    $rs[] = $vc['room_parent'];
            }
        }
    }
    return $rs;
}