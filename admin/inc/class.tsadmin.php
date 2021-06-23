<?php
/**
 * @package    WordPress
 * @subpackage Trizen
 * @since      1.0
 * Class TSAdmin
 * Created by TechyDevs
 */

if (!class_exists('TSAdmin')) {
    class TSAdmin {

        protected static $_instance  = FALSE;
        static private $message      = "";
        static private $message_type = "";
        public $metabox;

        function __construct() {

        }

        static function get_history_bookings($type = "ts_hotel", $offset, $limit, $author = false) {
            global $wpdb;
            $where  = '';
            $join   = '';
            $select = '';
            if (isset($_GET['ts_date_start']) and $_GET['ts_date_start']) {
                if ($type == 'ts_cars') {
                    $date = ( date('m/d/Y', strtotime($_GET['ts_date_start'])) );
                    $where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in >= '{$date}'";
                } else {
                    $date = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
                    $where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) >= {$date}";
                }
            }

            if (isset($_GET['ts_date_end']) and $_GET['ts_date_end']) {
                if ($type == 'ts_cars') {
                    $date   = ( date('m/d/Y', strtotime($_GET['ts_date_end'])) );
                    $where .= " AND {$wpdb->prefix}ts_order_item_meta.check_in <= '{$date}'";
                } else {
                    $date = strtotime(date('Y-m-d', strtotime($_GET['ts_date_start'])));
                    $where .= " AND CAST({$wpdb->prefix}ts_order_item_meta.check_in_timestamp as UNSIGNED) <= {$date}";
                }
            }

            if ($c_name = get('ts_custommer_name')) {
                $join .= " INNER JOIN {$wpdb->prefix}postmeta as mt3 on mt3.post_id= {$wpdb->prefix}ts_order_item_meta.order_item_id";
                $where .= ' AND  mt3.meta_key=\'ts_first_name\'
                 ';
                $where .= ' AND mt3.meta_value like \'%' . esc_sql($c_name) . '%\'';
            }

            if ($author) {
                $author = " AND {$wpdb->prefix}ts_order_item_meta.user_id = " . $author;
            }

            $querystr = "
                SELECT SQL_CALC_FOUND_ROWS  {$wpdb->prefix}posts.* from {$wpdb->prefix}ts_order_item_meta
                {$join}
                INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}ts_order_item_meta.order_item_id
                WHERE 1=1 AND ts_booking_post_type = '{$type}' AND type='normal_booking' {$where}
                ORDER BY {$wpdb->prefix}ts_order_item_meta.id DESC
                LIMIT {$offset},{$limit}
                ";
            $pageposts = $wpdb->get_results($querystr, OBJECT);

            return ['total' => $wpdb->get_var("SELECT FOUND_ROWS();"), 'rows' => $pageposts];
        }
    }
}