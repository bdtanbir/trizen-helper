<?php
$cancel_order_id    = '';
$cancel_cancel_data = [];
if ( !class_exists( 'TSUser_f' ) ) {
    class TSUser_f {
        public static $msg = '';
        public static $msg_uptp = '';
        public static $validator;

        function init(){

        }

        static function _get_all_order_statuses() {
            $order_statuses = [
                'pending'    => __( 'Pending', 'trizen-helper' ),
                'complete'   => __( 'Completed', 'trizen-helper' ),
                'incomplete' => __( 'Incomplete', 'trizen-helper' ),
                'canceled'   => __( 'Cancelled', 'trizen-helper' ),
            ];
            if ( function_exists( 'wc_get_order_statuses' ) ) {
                $order_statuses_woo = wc_get_order_statuses();
                $order_statuses     = array_merge( $order_statuses, $order_statuses_woo );
            }
            return apply_filters( 'ts_order_statuses', $order_statuses );
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
