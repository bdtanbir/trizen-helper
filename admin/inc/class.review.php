<?php
if ( !class_exists( 'TSReview' ) ) {
    class TSReview {
        protected static $reviewStarsData = [];
        protected static $reviewData = [];
        protected static $rateData = [];
        protected static $countComments = [];
        function __construct()
        {
        }

        function init() {
            add_action( 'comment_post', [ $this, 'save_comment_meta_data' ] );

            add_action( 'wp_ajax_like_review', [ $this, 'like_review'] );
            add_action( 'wp_ajax_nopriv_like_review', [ $this, 'like_review'] );
        }

        function save_comment_meta_data( $comment_id ) {
            $array = get_comment( $comment_id, ARRAY_A );
            /*if ( ( isset( $_POST[ 'comment_title' ] ) ) && ( $_POST[ 'comment_title' ] != '' ) ) {
                $title = wp_filter_nohtml_kses( $_POST[ 'comment_title' ] );
                add_comment_meta( $comment_id, 'comment_title', $title );
            }*/
            if ( ( isset( $_POST[ 'comment_rate' ] ) ) && ( $_POST[ 'comment_rate' ] != '' ) ) {
                $rate = wp_filter_nohtml_kses( $_POST[ 'comment_rate' ] );
                if ( $rate > 5 ) {
                    //Max rate is 5
                    $rate = 5;
                }
                add_comment_meta( $comment_id, ' q', $rate );
            }
            $all_postype       = TravelHelper::booking_post_type();
            $current_post_type = get_post_type( get_comment( $comment_id )->comment_post_ID );
            global $wpdb;
            $array['comment_type']       = 'ts_reviews';
            $array['comment_approved']   = 1;
//            $array[ 'comment_approved' ] = 0;
            if ( is_super_admin() ) {
                $array[ 'comment_approved' ] = 1;
            }
            wp_update_comment($array);
            $comemntObj = get_comment( $comment_id );
            $post_id    = $comemntObj->comment_post_ID;
            $avg        = TSReview::get_avg_rate( $post_id );
            update_post_meta( $post_id, 'rate_review', $avg );
        }

        static function count_comment( $post_id = false, $comment_type = false ) {
            if ( $post_id ) {
                global $wpdb;
                $query = 'SELECT COUNT(comment_ID) FROM ' . $wpdb->comments . ' WHERE 1=1 ';
                if ( $post_id ) {
                    $query .= ' AND comment_post_ID="' . sanitize_title_for_query( $post_id ) . '"';
                }
                if ( $comment_type ) {
                    $query .= ' AND comment_type="' . sanitize_title_for_query( $comment_type ) . '"';
                }
                $count = $wpdb->get_var( $query );
                return $count;
            }
        }

        static function data_comment_author_page( $post_id = false, $comment_type = false ) {
            if ( !empty( $post_id ) ) {
                global $wpdb;
                $query = 'SELECT comment_ID FROM ' . $wpdb->comments . ' WHERE 1=1 ';
                if ( $post_id ) {
                    $query .= ' AND comment_post_ID IN (' . implode( ',', $post_id ) . ')';
                }
                if ( $comment_type ) {
                    $query .= ' AND comment_type="' . sanitize_title_for_query( $comment_type ) . '"';
                }
                $data = $wpdb->get_results( $query, ARRAY_A );
                return $data;
            }
        }

        static function count_review( $post_id = false ) {
            return self::count_comment( $post_id, "st_reviews" );
        }

        static function get_avg_rate( $post_id = false ) {
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

        static function get_percent_recommend( $post_id = false, $min_rate = 3 ){
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            if ( $post_id ) {
                $total = get_comments_number( $post_id );
                global $wpdb;
                $query = "SELECT count({$wpdb->comments}.comment_ID) as total from {$wpdb->comments} join {$wpdb->commentmeta} on {$wpdb->comments}.comment_ID={$wpdb->commentmeta}.comment_ID where 1=1";
                $query .= " and `comment_type`='ts_reviews' ";
                $query .= " and comment_post_ID='" . sanitize_title_for_query( $post_id ) . "'";
                $query .= " and `comment_approved`=1 ";
                $query .= " and meta_value>='" . sanitize_title_for_query( $min_rate ) . "'";
                $query .= "  and meta_key='comment_rate' ";
                $count = $wpdb->get_var( $query );
                if ( !$total ) return 0;
                $percent = round( ( $count / $total ) * 100 );
                if ( $percent > 100 )
                    $percent = 100;
                return $percent;
            }
        }

        static function count_review_by_rate( $post_id = false, $rate = '' ){
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            if ( array_key_exists( $post_id . '_' . $rate, self::$reviewData ) ) return self::$reviewData[ $post_id . '_' . $rate ];
            if ( $post_id ) {
                global $wpdb;
                $query = "SELECT count({$wpdb->comments}.comment_ID) as total from {$wpdb->comments} join {$wpdb->commentmeta} on {$wpdb->comments}.comment_ID={$wpdb->commentmeta}.comment_ID where 1=1";
                $query .= " and `comment_type`='ts_reviews'";
                $query .= " and comment_post_ID='" . sanitize_title_for_query( $post_id ) . "'";
                $query .= " and meta_value>='" . sanitize_title_for_query( $rate ) . "'";
                $query .= " and meta_value<'" . sanitize_title_for_query( $rate + 1 ) . "'";
                $query .= " and `comment_approved`=1 ";
                $query .= "  and meta_key='comment_rate' ";
                $count = $wpdb->get_var( $query );
                self::$reviewData[ $post_id ] = $count;
                return $count;
            }
        }

        static function get_review_stars( $post_id = false ) {
            if ( !$post_id ) $post_id = get_the_ID();
            $post_type = get_post_type( $post_id );
            $key       = '';
            switch ( $post_type ) {
                case "ts_hotel":
                    $key = 'hotel_review_stars';
                    break;
                case "ts_rental":
                    $key = 'rental_review_stars';
                    break;
                case "ts_cars":
                    $key = 'car_review_stars';
                    break;
                case "ts_tours":
                    $key = 'tour_review_stars';
                    break;
                case "ts_activity":
                    $key = 'activity_review_stars';
                    break;
            }
            $list_star = get_option( $key );
            return $list_star;
        }

        /**
         * @since  1.0.0
         * @update count all guest comment  and user comment
         */
        static function count_all_comment( $post_id = false ) {
            if ( !$post_id ) $post_id = get_the_ID();
            if ( array_key_exists( $post_id, self::$countComments ) ) return self::$countComments[ $post_id ];
            global $wpdb;
            $query = "SELECT count({$wpdb->comments}.comment_ID) as total from {$wpdb->comments}  where 1=1";
            $query .= " and `comment_type`='ts_reviews'";
            $query .= " and comment_post_ID='" . sanitize_title_for_query( $post_id ) . "'";
            $query .= " and comment_approved=1";
            $count                           = $wpdb->get_var( $query );
            self::$countComments[ $post_id ] = $count;
            return $count;
        }

        static function get_avg_star( $post_id = false, $star = false ) {
            if ( !$post_id ) {
                $post_id = get_the_ID();
            }
            $key = $post_id . '_' . $star;
            if ( array_key_exists( $key, self::$reviewStarsData ) ) return self::$reviewStarsData[ $key ];
            if ( $post_id and $star ) {
                $star = sanitize_title( $star );
                global $wpdb;
                $query = "SELECT avg({$wpdb->commentmeta}.meta_value) as avg_rate from {$wpdb->comments} join {$wpdb->commentmeta} on {$wpdb->comments}.comment_ID={$wpdb->commentmeta}.comment_ID where 1=1";
                $query .= " and `comment_type`='ts_reviews'";
                $query .= " and comment_post_ID='" . sanitize_title_for_query( $post_id ) . "'";
                $query .= " and `comment_approved`=1 ";
                $query .= " and meta_key='ts_star_$star' ";
                $count = $wpdb->get_var( $query );
                self::$reviewStarsData[ $key ] = $count;
                return $count;
            }
        }

        static function get_rate_review_text($review, $count = null) {
            $string = esc_html__('Excellent', 'trizen-helper');
            if ($review > 4) {
                $string = esc_html__('Excellent', 'trizen-helper');
            } elseif ($review > 3) {
                $string = esc_html__('Very Good', 'trizen-helper');
            } elseif ($review > 2) {
                $string = esc_html__('Average', 'trizen-helper');
            } elseif ($review > 1) {
                $string = esc_html__('Poor', 'trizen-helper');
            } elseif ($review == 1) {
                $string = esc_html__('Terrible', 'trizen-helper');
            } else {
                $string = esc_html__('Not Rated', 'trizen-helper');
            }
            if ($count !== null) {
                if ($count <= 0) {
                    $string = esc_html__('Not Rated', 'trizen-helper');
                }
            }
            return $string;
        }

        static function get_review_summary( $post_id = false ) {
            if ( !$post_id ) $post_id = get_the_ID();
            $stars = self::get_review_stars( $post_id );
            $results = [];
            if ( !empty( $stars ) ) {
                global $wpdb;
                foreach ( $stars as $star ) {
                    $name = strtolower( $star );
                    /*if ( isset( $star ) && !empty( $star ) ) {
                        $name = $star;
                        $name = trim( $name );
                    }*/
                    $name = sanitize_title($name);
                    $sql  = "SELECT
                                avg(mt.meta_value) AS total
                            FROM
                                {$wpdb->prefix}commentmeta AS mt
                            INNER JOIN {$wpdb->prefix}comments AS cm ON (
                                cm.comment_ID = mt.comment_id
                                AND mt.meta_key = 'ts_star_{$name}'
                            )
                            WHERE
                                1 = 1
                            AND cm.comment_post_ID = {$post_id}";
                    $count     = $wpdb->get_var( $sql );
                    $results[] = [
                        'name'    => $star,
                        'summary' => round($count, 1),
                        'percent' => $count / 5 * 100
                    ];
                }
            }
            return $results;

        }

        function check_like( $comment_id ) { // test if user liked before
            if ( is_user_logged_in() ) { // user is logged in
                $user_id     = get_current_user_id(); // current user
                $meta_USERS  = get_comment_meta( $comment_id, "_user_liked" ); // user ids from comment meta
                $liked_USERS = ""; // set up array variable
                if ( count( $meta_USERS ) != 0 ) { // meta exists, set up values
                    $liked_USERS = $meta_USERS[ 0 ];
                }
                if ( !is_array( $liked_USERS ) ) // make array just in case
                    $liked_USERS = [];
                if ( in_array( $user_id, $liked_USERS ) ) { // True if User ID in array
                    return true;
                }
                return false;
            } else { // user is anonymous, use IP address for voting
                $meta_IPS = get_comment_meta( $comment_id, "_user_IP" ); // get previously voted IP address
                $ip       = ip_address();
                $liked_IPS = ""; // set up array variable
                if ( count( $meta_IPS ) != 0 ) { // meta exists, set up values
                    $liked_IPS = $meta_IPS[ 0 ];
                }
                if ( !is_array( $liked_IPS ) ) // make array just in case
                    $liked_IPS = [];
                if ( in_array( $ip, $liked_IPS ) ) { // True is IP in array
                    return true;
                }
                return false;
            }
        }

        function find_by( $comment_id = false, $key = 'comment_ID' ){
            if ( $comment_id and $key ) {
                global $wpdb;
                $query = "SELECT count({$wpdb->comments}.comment_ID) as total from {$wpdb->comments} WHERE 1=1 ";
                $query .= " and {$key}='{$comment_id}'";
                $count = $wpdb->get_var( $query );
                return $count;
            }
        }

        function like_review(){
            $comment_id = post( 'comment_ID' );
            if ( $this->find_by( $comment_id ) ) {
                $comment_like_count = get_comment_meta( $comment_id, "_comment_like_count", true ); // comment like count
                $data = [
                    'like_status' => true,
                    'message'     => __( 'You like this', 'trizen-helper' ),
                    'like_count'  => $comment_like_count
                ];
                //For logged user
                if ( is_user_logged_in() ) {
                    $user_id       = get_current_user_id(); // current user
                    $meta_COMMENTS = get_user_option( "_liked_comments", $user_id ); // comments ids from user meta
                    $meta_USERS    = get_comment_meta( $comment_id, "_user_liked" ); // user ids from comment meta

                    $liked_COMMENTS = NULL; // setup array variable
                    $liked_USERS    = NULL; // setup array variable

                    if ( count( $meta_COMMENTS ) != 0 ) { // meta exists, set up values
                        $liked_COMMENTS = $meta_COMMENTS;
                    }

                    if ( !is_array( $liked_COMMENTS ) ) // make array just in case
                        $liked_COMMENTS = [];

                    if ( count( $meta_USERS ) != 0 ) { // meta exists, set up values
                        $liked_USERS = $meta_USERS[ 0 ];
                    }
                    if ( !is_array( $liked_USERS ) ) // make array just in case
                        $liked_USERS = [];

                    $liked_COMMENTS[ 'comment-' . $comment_id ] = $comment_id; // Add comment id to user meta array
                    $liked_USERS[ 'user-' . $user_id ]          = $user_id; // add user id to comment meta array
                    $user_likes                                 = count( $liked_COMMENTS ); // count user likes

                    if ( !$this->check_like( $comment_id ) ) { // like the comment

                        update_comment_meta( $comment_id, "_user_liked", $liked_USERS ); // Add user ID to comment meta
                        update_comment_meta( $comment_id, "_comment_like_count", ++$comment_like_count ); // +1 count comment meta
                        update_user_option( $user_id, "_liked_comments", $liked_COMMENTS ); // Add comment ID to user meta
                        update_user_option( $user_id, "_user_like_count", $user_likes ); // +1 count user meta

                        $data[ 'like_count' ] = $comment_like_count;

                    } else { // unlike the comment
                        $pid_key = array_search( $comment_id, $liked_COMMENTS ); // find the key
                        $uid_key = array_search( $user_id, $liked_USERS ); // find the key
                        unset( $liked_COMMENTS[ $pid_key ] ); // remove from array
                        unset( $liked_USERS[ $uid_key ] ); // remove from array
                        $user_likes = count( $liked_COMMENTS ); // recount user likes
                        update_comment_meta( $comment_id, "_user_liked", $liked_USERS ); // Remove user ID from comment meta
                        update_comment_meta( $comment_id, "_comment_like_count", --$comment_like_count ); // -1 count comment meta
                        update_user_option( $user_id, "_liked_comments", $liked_COMMENTS ); // Remove comment ID from user meta
                        update_user_option( $user_id, "_user_like_count", $user_likes ); // -1 count user meta

                        $data[ 'like_status' ] = false;
                        $data[ 'like_count' ]  = $comment_like_count;
                        $data[ 'message' ]     = false;
                    }
                } else {
                    // user is not logged in (anonymous)
                    $ip        = ip_address(); // user IP address
                    $meta_IPS  = get_comment_meta( $comment_id, "_user_IP" ); // stored IP addresses
                    $liked_IPS = NULL; // set up array variable

                    if ( count( $meta_IPS ) != 0 ) { // meta exists, set up values
                        $liked_IPS = $meta_IPS[ 0 ];
                    }
                    if ( !is_array( $liked_IPS ) ) // make array just in case
                        $liked_IPS = [];

                    if ( !in_array( $ip, $liked_IPS ) ) // if IP not in array
                        $liked_IPS[ 'ip-' . $ip ] = $ip; // add IP to array

                    if ( !$this->check_like( $comment_id ) ) { // like the comment
                        update_comment_meta( $comment_id, "_user_IP", $liked_IPS ); // Add user IP to comment meta
                        update_comment_meta( $comment_id, "_comment_like_count", ++$comment_like_count ); // +1 count comment meta
                        $data[ 'like_count' ] = $comment_like_count;
                    } else { // unlike the comment
                        $ip_key = array_search( $ip, $liked_IPS ); // find the key
                        unset( $liked_IPS[ $ip_key ] ); // remove from array
                        update_comment_meta( $comment_id, "_user_IP", $liked_IPS ); // Remove user IP from comment meta
                        update_comment_meta( $comment_id, "_comment_like_count", --$comment_like_count ); // -1 count comment meta

                        $data[ 'like_status' ] = false;
                        $data[ 'like_count' ]  = $comment_like_count;
                        $data[ 'message' ]     = false;
                    }
                }

                echo json_encode( [
                    'status' => 1,
                    'data'   => $data
                ] );

            } else {
                echo json_encode( [
                    'status' => 0,
                    'error'  => [
                        'error_code'    => 'comment_not_exists',
                        'error_message' => __( 'Review does not exists', 'trizen-helper' )
                    ]
                ] );
            }

            exit();


        }

    }
    $a = new TSReview();
    $a->init();
}
