<?php
$cancel_order_id    = '';
$cancel_cancel_data = [];
if ( !class_exists( 'TSUser_f' ) ) {
    class TSUser_f {
        public static $msg = '';
        public static $msg_uptp = '';
        public static $validator;

        function init(){
            add_action( 'wp_ajax_ts_get_info_booking_history', [ $this, '_ts_get_info_booking_history' ] );
            add_action( 'wp_ajax_nopriv_ts_get_info_booking_history', [ $this, '_ts_get_info_booking_history' ] );

        }
        function _ts_get_info_booking_history() {
            $order_id   = request( 'order_id' );
            $service_id = request( 'service_id' );
            $res        = [ 'status' => 0, 'msg' => "" ];
            $my_user      = wp_get_current_user();
            $user_partner = 0;
            $user_book    = 0;
            global $wpdb;
            $sql        = "SELECT * FROM {$wpdb->prefix}ts_order_item_meta WHERE order_item_id = " . $order_id . " or wc_order_id = " . $order_id;
            $rs         = $wpdb->get_row( $sql, ARRAY_A );
            $order_data = $rs;
            if ( !empty( $rs[ 'id' ] ) ) {
                $user_book = $rs[ 'user_id' ];
            }
            if ( !empty( $rs[ 'partner_id' ] ) ) {
                $user_partner = $rs[ 'partner_id' ];
            }
            $is_checked = true;
            if ( !is_user_logged_in() ) {
                $is_checked = false;
            }
            if ( $user_book != $my_user->ID ) {
                $is_checked = false;
            }
            if ( $user_partner == $my_user->ID ) {
                $is_checked = true;
            }
            if ( current_user_can( 'manage_options' ) ) {
                $is_checked = true;
            }
            // if ( $is_checked and !empty( $rs ) ) {
            $html      = '';
            $post_type = $rs[ 'ts_booking_post_type' ];
            $order_id  = $rs[ 'wc_order_id' ];
            if ( $order_data[ 'type' ] == "normal_booking" ) {
//                $html = st()->load_template( 'user/detail-booking-history/' . $post_type, false, [ 'order_id' => $order_id, 'service_id' => $service_id, 'order_data' => $order_data ] );
                $html = '
                    if option
                
                ';
            } else {
//                $html = st()->load_template( 'user/detail-booking-history/woo/' . $post_type, false, [ 'order_id' => $order_id, 'service_id' => $service_id, 'order_data' => $order_data ] );
                $html = 'else option';
            }
            $res[ 'status' ] = 1;
            $res[ 'html' ]   = $html;
            // } else {
            //     $res[ 'msg' ] = '<p class="text-center">' . esc_html__( "Load failed...", 'trizen-helper' ) . '</p>';
            // }
            echo json_encode( $res );
            die();
        }



        static function get_icon_wishlist() {
            $current_user = wp_get_current_user();
            $data_list    = get_user_meta( $current_user->ID, 'ts_wishlist', true );
            $data_list    = json_decode( $data_list );

            if ( $data_list != '' and is_array( $data_list ) ) {
                $check = false;
                foreach ( $data_list as $k => $v ) {
                    if ( $v->id == get_the_ID() and $v->type == get_post_type( get_the_ID() ) ) {
                        $check = true;
                    }
                }
                if ( $check == true ) {
                    return [
                        'original-title' =>'remove_to_wishlist',
                        'icon'           => '<i class="la la-heart"></i>',
                        'status' => true
                    ];
                } else {
                    return [
                        'original-title' => 'add_to_wishlist',
                        'icon'           => '<i class="la la-heart-o"></i>',
                        'status' => false
                    ];
                }
            } else {
                return [
                    'original-title' => 'add_to_wishlist',
                    'icon'           => '<i class="la la-heart-o"></i>',
                    'status' => false
                ];
            }
        }
    }
    $user = new TSUser_f();
    $user->init();
}
