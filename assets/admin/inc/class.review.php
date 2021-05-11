<?php
if ( !class_exists( 'TSReview' ) ) {
    class TSReview
    {
        protected static $reviewStatsData = [];
        protected static $reviewData = [];
        protected static $rateData = [];
        protected static $countComments = [];
        function __construct()
        {
        }

        function init()
        {
        }


        static function get_review_stats( $post_id = false )
        {
            if ( !$post_id ) $post_id = get_the_ID();
            $post_type = get_post_type( $post_id );
            $key       = '';
            switch ( $post_type ) {
                case "ts_hotel":
                    $key = 'hotel_review_stats';
                    break;
                case "hotel_room":
                    $key = 'hotel_room_review_stats';
                    break;
                case "ts_rental":
                    $key = 'rental_review_stats';
                    break;
                case "ts_cars":
                    $key = 'car_review_stats';
                    break;
                case "ts_tours":
                    $key = 'tour_review_stats';
                    break;
                case "ts_activity":
                    $key = 'activity_review_stats';
                    break;
            }
            $list_star = trizen_get_option($key);
            return $list_star;
        }

        static function get_avg_rate( $post_id = false )
        {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            if ( $post_id ) {
                if ( array_key_exists( $post_id, self::$rateData ) ) return self::$rateData[ $post_id ];
                global $wpdb;
                $query = "SELECT ROUND( AVG(meta_value),1 ) as avg_rate from {$wpdb->comments} join {$wpdb->commentmeta} on {$wpdb->comments}.comment_ID={$wpdb->commentmeta}.comment_ID where 1=1";
                $query .= " and `comment_type`='ts_reviews' ";
                $query .= " and `comment_approved`=1 ";
                $query .= " and comment_post_ID='" . sanitize_title_for_query( $post_id ) . "'";
                $query .= "  and meta_key='comment_rate' ";
                $rate = (float)$wpdb->get_var( $query );
                self::$rateData[ $post_id ] = $rate;
                return $rate;
            }
            return 0;
        }


        static function get_review_summary( $post_id = false )
        {
            if ( !$post_id ) $post_id = get_the_ID();
            $stats = self::get_review_stats( $post_id );
            $results = [];
            if ( !empty( $stats ) ) {
                global $wpdb;
                foreach ( $stats as $stat ) {
                    $name = strtolower( $stat[ 'title' ] );
                    if ( isset( $stat[ 'name' ] ) && !empty( $stat[ 'name' ] ) ) {
                        $name = $stat[ 'name' ];
                        $name = trim( $name );
                    }
                    $name = sanitize_title($name);
                    $sql  = "SELECT
                                avg(mt.meta_value) AS total
                            FROM
                                {$wpdb->prefix}commentmeta AS mt
                            INNER JOIN {$wpdb->prefix}comments AS cm ON (
                                cm.comment_ID = mt.comment_id
                                AND mt.meta_key = 'ts_stat_{$name}'
                            )
                            WHERE
                                1 = 1
                            AND cm.comment_post_ID = {$post_id}";
                    $count     = $wpdb->get_var( $sql );
                    $results[] = [
                        'name'    => $stat[ 'title' ],
                        'summary' => round($count, 1),
                        'percent' => $count / 5 * 100
                    ];
                }
            }
            return $results;

        }



    }
    $a = new TSReview();
    $a->init();
}
