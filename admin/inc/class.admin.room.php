<?php

if ( !class_exists( 'TSAdminRoom' ) ) {

    class TSAdminRoom
    {

        protected static $_inst;
        static $_table_version = "1.0";
        static $booking_page;
        protected $post_type = 'hotel_room';
        protected static $_cachedAlCurrency = [];
        private static $_check_table_duplicate = [];
//        private static $_booking_primary_currency;

        protected $order_id=false;

        /**
         *
         *
         * @update 1.1.3
         * */
        function __construct()
        {
            global $wpdb;
            add_action('plugins_loaded', [$this, '_check_table_hotel_room']);

            add_filter('ts_change_column_ts_hotel_room', [$this, 'ts_change_column_ts_hotel_room_fnc']);

            add_action( 'wp_ajax_ts_get_availability_hotel_room', [ &$this, '_get_availability_hotel_room' ] );
            add_action( 'wp_ajax_nopriv_ts_get_availability_hotel_room', [ &$this, '_get_availability_hotel_room' ] );
            add_action( 'wp_ajax_ts_get_cancel_booking_step_1', [ $this, 'ts_get_cancel_booking_step_1' ] );
            add_action( 'wp_ajax_ts_get_cancel_booking_step_2', [ $this, 'ts_get_cancel_booking_step_2' ] );
            add_action( 'wp_ajax_ts_get_cancel_booking_step_3', [ $this, 'ts_get_cancel_booking_step_3' ] );
            add_action( 'wp_ajax_ts_add_custom_price', [ $this, '_add_custom_price' ] );
            add_action('admin_init', [$this, '_upgradeRoomTable135']);
            add_action( 'parse_query', [ $this, 'parse_query_hotel_room' ] );
            add_action( 'save_post', [ $this, '_update_list_location' ], 999999, 2 );
            add_action( 'added_post_meta', [ $this, 'hotel_update_min_price' ], 10, 4 );
        }

        public function _upgradeRoomTable135() {
            $updated = get_option('_upgradeRoomTable135', false);
            if (!$updated) {
                global $wpdb;
                $table = $wpdb->prefix . $this->post_type;
                $sql = "Update {$table} as t inner join {$wpdb->posts} as m on (t.post_id = m.ID and m.post_type='hotel_room') set t.`status` = m.post_status";
                $wpdb->query($sql);
                update_option('_upgradeRoomTable135', 'updated');
            }
        }

        function ts_change_column_ts_hotel_room_fnc($column) {
            $new_column = array_merge( $column, [
                'adult_price'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'child_price'          => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
            ]);
            return $new_column;
        }

        static function check_ver_working()
        {
            $dbhelper = new DatabaseHelper(self::$_table_version);

            return $dbhelper->check_ver_working('ts_hotel_room_table_version');
        }

        static function _check_table_hotel_room(){
            $dbhelper = new DatabaseHelper(self::$_table_version);
            $dbhelper->setTableName('hotel_room');
            $column = [
                'post_id'    => [
                    'type'   => 'INT',
                    'length' => 11,
                ],
                'room_parent' => [
                    'type'    => 'INT',
                    'length'  => 11,
                ],
                'multi_location' => [
                    'type' => 'text',
                ],
                'id_location' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'address' => [
                    'type' => 'text',
                ],
                'allow_full_day' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'price' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'number_room' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'discount_rate' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'adult_number' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'child_number' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'status' => [
                    'type'   => 'varchar',
                    'length' => 20
                ],
            ];
            $column = apply_filters('ts_change_column_ts_hotel_room', $column);
            $dbhelper->setDefaultColums($column);
            $dbhelper->check_meta_table_is_working('ts_hotel_room_table_version');

            return array_keys($column);
        }

        /**
         * @since 1.0
         **/
        static function is_booking_page() {
            if ( is_admin()
                and isset( $_GET[ 'post_type' ] )
                and $_GET[ 'post_type' ] == 'hotel_room'
                and isset( $_GET[ 'page' ] )
                and $_GET[ 'page' ] = 'ts_hotel_room_booking'
            ) return TRUE;

            return FALSE;
        }

        /**
         * @since 1.0
         **/
        function _delete_items() {
            if ( empty( $_POST ) or !check_admin_referer( 'shb_action', 'shb_field' ) ) {
                //// process form data, e.g. update fields
                return;
            }
            $ids = isset( $_POST[ 'post' ] ) ? $_POST[ 'post' ] : [];
            if ( !empty( $ids ) ) {
                foreach ( $ids as $id )
                    wp_delete_post( $id, TRUE );

            }
            set_message( __( "Delete item(s) success", 'trizen-helper' ), 'updated' );
        }

        /**
         * @since 1.0
         **/
        function _save_booking( $order_id ){
            if ( !check_admin_referer( 'shb_action', 'shb_field' ) ) die;
            if ( $this->_check_validate() ) {

                $item_data = [
                    'status' => $_POST[ 'status' ],
                ];

                foreach ( $item_data as $val => $value ) {
                    update_post_meta( $order_id, $val, $value );
                }

                /*$check_out_field = get_checkout_fields();
                if ( !empty( $check_out_field ) ) {
                    foreach ( $check_out_field as $field_name => $field_desc ) {
                        if($field_name != 'st_note'){
                            update_post_meta( $order_id, $field_name, STInput::post( $field_name ) );
                        }
                    }
                }*/

                /*if ( TSAdminRoom::checkTableDuplicate( 'hotel_room' ) ) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'ts_order_item_meta';
                    $where = [
                        'order_item_id' => $order_id
                    ];
                    $data  = [
                        'status' => $_POST[ 'status' ]
                    ];
                    $wpdb->update( $table, $data, $where );
                }

                do_action( 'update_booking_hotel_room', $order_id );

                send_mail_after_booking( $order_id, true );
                wp_safe_redirect( self::$booking_page );*/
            }
        }

        /**
         * @since 1.0
         **/
        public function _check_validate() {

            $ts_first_name = request( 'ts_first_name', '' );
            if ( empty( $ts_first_name ) ) {
                set_message( __( 'The firstname field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_last_name = request( 'ts_last_name', '' );
            if ( empty( $ts_last_name ) ) {
                set_message( __( 'The lastname field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_email = request( 'ts_email', '' );
            if ( empty( $ts_email ) ) {
                set_message( __( 'The email field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            if ( !filter_var( $ts_email, FILTER_VALIDATE_EMAIL ) ) {
                set_message( __( 'Invalid email format.', 'trizen-helper' ), 'danger' );

                return false;
            }

            $ts_phone = request( 'ts_phone', '' );
            if ( empty( $ts_phone ) ) {
                set_message( __( 'The phone field is not empty.', 'trizen-helper' ), 'danger' );

                return false;
            }

            return true;
        }

        /**
         * @since 1.0
         **/
        function _update_list_location( $id, $data ) {
            $location = request( 'multi_location', '' );
            if ( isset( $_REQUEST[ 'multi_location' ] ) ) {
                if ( is_array( $location ) && count( $location ) ) {
                    $location_str = '';
                    foreach ( $location as $item ) {
                        if ( empty( $location_str ) ) {
                            $location_str .= $item;
                        } else {
                            $location_str .= ',' . $item;
                        }
                    }
                } else {
                    $location_str = '';
                }
                update_post_meta( $id, 'multi_location', $location_str );
                update_post_meta( $id, 'id_location', '' );
            }

        }

        public function getMeta($key)
        {
            return get_post_meta($this->order_id,$key,true);
        }

        /**
         * @todo Get Type of Order: normal_booking or woocommerce
         * @return string
         */
        public function getType()
        {

        }


        /**
         * @todo Check if current order is using Woocommerce Checkout
         * @return bool
         */
        public function isWoocommerceCheckout(){
            return $this->getType()=='woocommerce'?true:false;
        }

        /**
         * @return WC_Order
         */
        public function getWoocommerceOrder(){
            global $wpdb;

            return new WC_Order($this->order_id);
        }

        /**
         * @todo Get total amount
         * @return float
         */
        public function getTotal(){
            if ($this->isWoocommerceCheckout()) {
                global $wpdb;
                $querystr = "SELECT meta_value FROM  " . $wpdb->prefix . "woocommerce_order_itemmeta
                            WHERE
                            1=1
                            AND order_item_id = '{$this->order_id}'
                            AND (
                                meta_key = '_line_total'
                                OR meta_key = '_line_tax'
                                OR meta_key = '_ts_booking_fee_price'
                            )
                            ";
                $price = $wpdb->get_results($querystr, OBJECT);
                $data_price = 0;
                if (!empty($price)) {
                    foreach ($price as $k => $v) {
                        $data_price += $v->meta_value;
                    }
                }
                return $data_price;
            } else {
                return $this->getMeta('total_price');
            }
        }

        public function getItems(){
            global $wpdb;
            if($this->isWoocommerceCheckout())
            {
                if($order=$this->getWoocommerceOrder())
                {
                    return $order->get_items();
                }
                return [];
            }

            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}ts_order_item_meta"));

        }

        static function get_current_currency( $need = false ){
            //Check session of user first
            if ( isset( $_SESSION[ 'currency' ][ 'name' ] ) ) {
                $name = $_SESSION[ 'currency' ][ 'name' ];
                if ( $session_currency = self::find_currency( $name ) ) {
                    if ( $need and isset( $session_currency[ $need ] ) ) return $session_currency[ $need ];
                    return $session_currency;
                }
            }
            return self::get_default_currency( $need );
        }


        static function convert_money_to_default( $money = false ) {
            if ( !is_numeric( $money ) ) $money = 0;
            if ( !$money ) $money = 0;
            $current_rate = self::get_current_currency( 'rate' );
            $current      = self::get_current_currency( 'name' );
            $default = self::get_default_currency( 'name' );
            if ( $current != $default )
                return $money / $current_rate;
            return $money;
        }


        /**
         * return Default Currency
         * */
        static function get_default_currency( $need = false ){
            //If user dont set the primary currency, we take the first of list all currency
            $all_currency = self::get_currency();


            if ( isset( $all_currency[ 0 ] ) ) {
                if ( $need and isset( $all_currency[ 0 ][ $need ] ) ) return $all_currency[ 0 ][ $need ];

                return $all_currency[ 0 ];
            }
        }


        /**
         * Return All Currencies
         * */
        static function get_currency( $theme_option = false ){
            $all = self::$_cachedAlCurrency;
            //return array for theme options choise
            /*if ( $theme_option ) {
                $choice = [];
                if ( !empty( $all ) and is_array( $all ) ) {
                    foreach ( $all as $key => $value ) {
                        $choice[] = [
                            'label' => $value[ 'title' ],
                            'value' => $value[ 'name' ]
                        ];
                    }
                }
                return $choice;
            }*/
            return $all;
        }


        /**
         * @todo Find currency by name, return false if not found
         * */
        static function find_currency( $currency_name, $compare_key = 'name' ) {
            $currency_name = esc_attr( $currency_name );

            $all_currency = self::$_cachedAlCurrency;
            if ( !empty( $all_currency ) ) {
                foreach ( $all_currency as $key ) {
                    if ( $key[ $compare_key ] == $currency_name ) {
                        return $key;
                    }
                }
            }

            return false;
        }

        static function comment_form($args = [], $post_id = null) {
            if (null === $post_id)
                $post_id = get_the_ID();

            // Exit the function when comments for the post are closed.
            if (!comments_open($post_id)) {
                /**
                 * Fires after the comment form if comments are closed.
                 *
                 * @since 3.0.0
                 */
                do_action('comment_form_comments_closed');

                return;
            }

            $commenter = wp_get_current_commenter();
            $user = wp_get_current_user();
            $user_identity = $user->exists() ? $user->display_name : '';

            $args = [];
            if (!isset($args['format']))
                $args['format'] = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';

            $req = get_option('require_name_email');
            $html_req = ($req ? " required='required'" : '');
            $html5 = 'html5' === $args['format'];
            $fields = [
                'author' => '<p class="comment-form-author">' . '<label for="author">' . __('Name', 'trizen-helper') . ($req ? ' <span class="required">*</span>' : '') . '</label> ' .
                    '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" maxlength="245"' . $html_req . ' /></p>',
                'email' => '<p class="comment-form-email"><label for="email">' . __('Email', 'trizen-helper') . ($req ? ' <span class="required">*</span>' : '') . '</label> ' .
                    '<input id="email" name="email" ' . ($html5 ? 'type="email"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_email']) . '" size="30" maxlength="100" aria-describedby="email-notes"' . $html_req . ' /></p>',
                'url' => '<p class="comment-form-url"><label for="url">' . __('Website', 'trizen-helper') . '</label> ' .
                    '<input id="url" name="url" ' . ($html5 ? 'type="url"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_url']) . '" size="30" maxlength="200" /></p>',
            ];

            if (has_action('set_comment_cookies', 'wp_set_comment_cookies') && get_option('show_comments_cookies_opt_in')) {
                $consent = empty($commenter['comment_author_email']) ? '' : ' checked="checked"';
                $fields['cookies'] = '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $consent . ' />' .
                    '<label for="wp-comment-cookies-consent">' . __('Save my name, email, and website in this browser for the next time I comment.', 'trizen-helper') . '</label></p>';

                // Ensure that the passed fields include cookies consent.
                if (isset($args['fields']) && !isset($args['fields']['cookies'])) {
                    $args['fields']['cookies'] = $fields['cookies'];
                }
            }

            $required_text = sprintf(' ' . __('Required fields are marked %s', 'trizen-helper'), '<span class="required">*</span>');

            /**
             * Filters the default comment form fields.
             *
             * @since 3.0.0
             *
             * @param array $fields The default comment fields.
             */
            $fields = apply_filters('comment_form_default_fields', $fields);
            $defaults = [
                'fields' => $fields,
                'comment_field' => '<p class="comment-form-comment"><label for="comment">' . _x('Comment', 'noun') . '</label> <textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea></p>',
                /** This filter is documented in wp-includes/link-template.php */
                'must_log_in' => '<p class="must-log-in">' . sprintf(
                    /* translators: %s: login URL */
                        __('You must be <a href="%s">logged in</a> to post a comment.', 'trizen-helper'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))
                    ) . '</p>',
                /** This filter is documented in wp-includes/link-template.php */
                'logged_in_as' => '<p class="logged-in-as">' . sprintf(
                    /* translators: 1: edit user link, 2: accessibility text, 3: user name, 4: logout URL */
                        __('<a class="st-link" href="%1$s" aria-label="%2$s">Logged in as %3$s</a>. <a class="st-link" href="%4$s">Log out?</a>','trizen-helper'), get_edit_user_link(),
                        /* translators: %s: user name */ esc_attr(sprintf(__('Logged in as %s. Edit your profile.', 'trizen-helper'), $user_identity)), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))
                    ) . '</p>',
                'comment_notes_before' => '<p class="comment-notes"><span id="email-notes">' . __('Your email address will not be published.', 'trizen-helper') . '</span>' . ($req ? $required_text : '') . '</p>',
                'comment_notes_after' => '',
                'action' => site_url('/wp-comments-post.php'),
                'id_form' => 'commentform',
                'id_submit' => 'submit',
                'class_form' => 'comment-form',
                'class_submit' => 'submit',
                'name_submit' => 'submit',
                'title_reply' => '',
                'title_reply_to' => __('Leave a Reply to %s', 'trizen-helper'),
                'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
                'title_reply_after' => '</h3>',
                'cancel_reply_before' => ' <small>',
                'cancel_reply_after' => '</small>',
                'cancel_reply_link' => __('Cancel reply', 'trizen-helper'),
                'label_submit' => __('Post Comment'),
                'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
                'submit_field' => '<p class="form-submit">%1$s %2$s</p>',
                'format' => 'xhtml',
            ];

            /**
             * Filters the comment form default arguments.
             *
             * Use {@see 'comment_form_default_fields'} to filter the comment fields.
             *
             * @since 3.0.0
             *
             * @param array $defaults The default comment form arguments.
             */
            $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

            // Ensure that the filtered args contain all required default values.
            $args = array_merge($defaults, $args);

            /**
             * Fires before the comment form.
             *
             * @since 3.0.0
             */
            //do_action( 'comment_form_before' );
            ?>
            <div id="respond" class="comment-respond" data-toggle-section="ts-review-form">
                <?php
                /* echo $args[ 'title_reply_before' ];
                  comment_form_title( $args[ 'title_reply' ], $args[ 'title_reply_to' ] );
                  echo $args[ 'cancel_reply_before' ];
                  cancel_comment_reply_link( $args[ 'cancel_reply_link' ] );
                  echo $args[ 'cancel_reply_after' ];
                  echo $args[ 'title_reply_after' ]; */

                if (get_option('comment_registration') && !is_user_logged_in()) :
                    echo '<span></span>';
                //echo $args[ 'must_log_in' ];
                //do_action( 'comment_form_must_log_in_after' );
                else :
                    ?>
                    <form action="<?php echo esc_url($args['action']); ?>" method="post"
                          id="<?php echo esc_attr($args['id_form']); ?>"
                          class="review-form"<?php echo ($html5) ? ' novalidate' : ''; ?>>
                        <?php
                        /* do_action( 'comment_form_top' );
                          if ( is_user_logged_in() ) :
                          echo apply_filters( 'comment_form_logged_in', $args[ 'logged_in_as' ], $commenter, $user_identity );

                          do_action( 'comment_form_logged_in_after', $commenter, $user_identity );
                          else :
                          echo $args[ 'comment_notes_before' ];
                          endif; */
                        if (get_post_type($post_id) == 'hotel_room') {
                            require_once TRIZEN_HELPER_PATH . 'admin/inc/hotel/room_review_form.php';
                        } elseif (get_post_type($post_id) == 'ts_hotel') {
                            require_once TRIZEN_HELPER_PATH . 'admin/inc/hotel/hotel_review_form.php';
                        }
                        ?>
                        <div class="text-center">
                            <input type="hidden" id="comment_post_ID" name="comment_post_ID"
                                   value="<?php echo esc_attr($post_id); ?>">
                            <input type="hidden" id="comment_parent" name="comment_parent" value="0">
                            <input id="submit" type="submit" name="submit"
                                   class="btn btn-green upper font-medium"
                                   value="<?php echo __('Leave a Review', 'trizen-helper') ?>">
                        </div>
                        <?php
                        do_action('comment_form', $post_id);
                        ?>
                    </form>
                <?php endif; ?>
            </div><!-- #respond -->
            <?php
            do_action('comment_form_after');
        }

        static function get_alt_image($image_id = null) {
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            if (!$alt) {
                $alt = get_bloginfo('description');
            }

            return $alt;
        }

        static function get_username($user_id) {
            $userdata = get_userdata($user_id);
            if (!$userdata) {
                return __('Customer', 'trizen-helper');
            }
            if ($userdata->display_name) {
                return $userdata->display_name;
            } elseif ($userdata->first_name || $userdata->last_name) {
                return $userdata->first_name . ' ' . $userdata->last_name;
            } else {
                return $userdata->user_login;
            }
        }


        static function _cancel_booking( $order_id )
        {
            /*$check_cancel_able = check_cancel_able( $order_id );
            if ( $check_cancel_able ) {
                global $wpdb;
                $user_id       = get_current_user_id();
                $order_item_id = $order_id;
                $check_order = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}ts_order_item_meta where user_id={$user_id} and  order_item_id = {$order_item_id} and `status`!='canceled' and `status`!='wc-canceled' LIMIT 0,1" );
                if ( $check_order ) {
                    $item_id = $check_order->ts_booking_id;
                    if ( $check_order->room_id ) $item_id = $check_order->room_id;
                    $cancel_percent = get_post_meta( $item_id, 'st_cancel_percent', true );
                    $query = "UPDATE {$wpdb->prefix}ts_order_item_meta set `status`='canceled' , cancel_percent={$cancel_percent} where order_item_id={$order_item_id}";
                    $wpdb->query( $query );
                    update_post_meta( $order_item_id, 'status', 'canceled' );
                    return true;
                }
            } else {
                return false;
            }*/
        }

//        public function ts_get_cancel_booking_step_1() {
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
////                $message = st()->load_template( 'user/cancel-booking/cancel', 'step-1', [ 'order_id' => $order_id ] );
//                $message = '';
//
//                $_SESSION[ 'cancel_data' ][ 'order_id' ]      = $order_id;
//                $_SESSION[ 'cancel_data' ][ 'order_encrypt' ] = $order_encrypt;
//                $status                                       = get_post_meta( $order_id, 'status', true );
//                $total_price                                  = (float) get_post_meta( $order_id, 'total_price', true);
//                $item_id                                      = (int) get_post_meta( $order_id, 'ts_booking_id', true);
//                $percent                                      = (int) get_post_meta( $item_id, 'ts_cancel_percent', true );
//                $refunded                                     = $total_price - ( $total_price * $percent / 100 );
//
//                $step = 'next-to-step-2';
//                if ( $status != 'complete' || ($percent == 100 && $refunded == 0 ) ) {
//                    $step = 'next-to-step-3';
//                }
//                echo json_encode( [
//                    'status'        => 1,
//                    'message'       => $message,
//                    'order_id'      => $order_id,
//                    'order_encrypt' => $order_encrypt,
//                    'step'          => $step
//                ] );
//                die;
//            }
//            echo json_encode( [
//                'status'        => 0,
//                'message'       => '<div class="text-danger">' . __( 'Have an error when get data. Try again!', 'trizen-helper' ) . '</div>',
//                'order_id'      => $order_id,
//                'order_encrypt' => $order_encrypt,
//                'step'          => ''
//            ] );
//            die;
//        }

//        public function ts_get_cancel_booking_step_2() {
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//            $why_cancel    = request( 'why_cancel', '' );
//            $detail        = request( 'detail', '' );
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
////                $message = st()->load_template( 'user/cancel-booking/cancel', 'step-2', [ 'order_id' => $order_id ] );
//                $message = '';
//                $_SESSION[ 'cancel_data' ][ 'why_cancel' ] = $why_cancel;
//                $_SESSION[ 'cancel_data' ][ 'detail' ]     = $detail;
//                echo json_encode( [
//                    'status'        => 1,
//                    'message'       => $message,
//                    'order_id'      => $order_id,
//                    'order_encrypt' => $order_encrypt,
//                    'step'          => 'next-to-step-3'
//                ] );
//                die;
//            }
//            echo json_encode( [
//                'status'        => 0,
//                'message'       => '<div class="text-danger">' . __( 'Have an error when get data. Try again!', 'trizen-helper' ) . '</div>',
//                'order_id'      => $order_id,
//                'order_encrypt' => $order_encrypt,
//                'step'          => ''
//            ] );
//            die;
//        }

//        public function ts_get_cancel_booking_step_3()
//        {
//            global $wpdb;
//            global $cancel_order_id, $cancel_cancel_data;
//
//            $order_id      = request( 'order_id', '' );
//            $order_encrypt = request( 'order_encrypt' );
//
//            if ( ts_compare_encrypt( $order_id, $order_encrypt ) ) {
//                $item_id        = (int)get_post_meta( $order_id, 'ts_booking_id', true );
//                $post_type      = get_post_meta( $order_id, 'ts_booking_post_type', true );
//                $post_author_id = get_post_field( 'post_author', $item_id );
//                if ( $post_type == 'ts_hotel' ) {
//                    $room_id        = (int)get_post_meta( $order_id, 'room_id', true );
//                    $post_author_id = get_post_field( 'post_author', $room_id );
//                }
//
//                $author_obj = get_userdata( $post_author_id );
//                $user_email = $author_obj->data->user_email;
//                $user_role  = array_shift( $author_obj->roles );
//
//                $total_price = (float)get_post_meta( $order_id, 'total_price', true );
//                $currency    = _get_currency_book_history( $order_id );
//
//                $percent = (int)get_post_meta( $item_id, 'ts_cancel_percent', true );
//                if ( $post_type == 'ts_hotel' && isset( $room_id ) ) {
//                    $percent = (int)get_post_meta( $room_id, 'ts_cancel_percent', true );
//                }
//
//                $refunded             = $total_price - ( $total_price * $percent / 100 );
//                $status               = get_post_meta( $order_id, 'status', true );
//                $cancel_refund_status = 'pending';
//
//                if ( $status != 'complete' ) {
//                    $refunded             = 0;
//                    $cancel_refund_status = 'complete';
//                }
//
//                $select_account = request( 'select_account', '' );
//
//                $refund_for_partner  = 'false';
//                $percent_for_partner = 'false';
//
//                $enable_email_cancel         = st()->get_option( 'enable_email_cancel', 'on' );
//                $enable_partner_email_cancel = st()->get_option( 'enable_partner_email_cancel', 'on' );
//                $enable_email_cancel_user    = st()->get_option( 'enable_email_cancel_success', 'on' );
//
//                if ( empty( $select_account ) ) {
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//                        //Update number_booked
//                        AvailabilityHelper::syncAvailabilityAfterCanceled( $order_id );
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'none', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        if ( $status == 'incomplete' ) {
//                            /*if ( $enable_email_cancel == 'on' ) {
//                                $this->_send_email_refund( $order_id, 'has-refund' );
//                            }
//                            if ( $enable_email_cancel_user == 'on' ) {
//                                $this->_send_email_refund( $order_id, 'success' );
//                            }
//
//                            if ( $enable_partner_email_cancel == 'on' ) {
//                                if ( $user_role == 'partner' ) {
//                                    $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                                }
//                            }*/
//                        }
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//                }
//                if ( $select_account == 'your_bank' ) {
//                    $account_name   = request( 'account_name', '' );
//                    $account_number = request( 'account_number', '' );
//                    $bank_name      = request( 'bank_name', '' );
//                    $swift_code     = request( 'swift_code', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => [
//                            'account_name'   => $account_name,
//                            'account_number' => $account_number,
//                            'bank_name'      => $bank_name,
//                            'swift_code'     => $swift_code
//                        ],
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'bank', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        /*if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }*/
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_paypal' ) {
//
//                    $paypal_email = STInput::request( 'paypal_email', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => [
//                            'paypal_email' => $paypal_email
//                        ],
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'paypal', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        /*if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }*/
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_stripe' ) {
//
//                    $transaction_id = STInput::request( 'transaction_id', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => false,
//                        'your_stripe'          => [
//                            'transaction_id' => $transaction_id
//                        ],
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'stripe', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }
//
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//                if ( $select_account == 'your_payfast' ) {
//
//                    $transaction_id = request( 'transaction_id', '' );
//
//                    $cancel_data = [
//                        'order_id'             => $order_id,
//                        'cancel_percent'       => $percent,
//                        'refunded'             => $refunded,
//                        'your_paypal'          => false,
//                        'your_bank'            => false,
//                        'your_stripe'          => false,
//                        'your_stripe'          => false,
//                        'your_payfast'         => [
//                            'transaction_id' => $transaction_id
//                        ],
//                        'currency'             => $currency,
//                        'why_cancel'           => $_SESSION[ 'cancel_data' ][ 'why_cancel' ],
//                        'detail'               => $_SESSION[ 'cancel_data' ][ 'detail' ],
//                        'status_before'        => $status,
//                        'cancel_refund_status' => $cancel_refund_status,
//                        'refund_for_partner'   => $refund_for_partner,
//                        'percent_for_partner'  => $percent_for_partner
//                    ];
//
//                    $cancel = self::_cancel_booking( $order_id );
//                    if ( $cancel ) {
//
//                        $query = "UPDATE {$wpdb->prefix}st_order_item_meta set cancel_refund='{$refunded}' , cancel_refund_status='{$cancel_refund_status}' where order_item_id={$order_id}";
//
//                        $wpdb->query( $query );
//
//                        update_post_meta( $order_id, 'cancel_data', $cancel_data );
//                        unset( $_SESSION[ 'cancel_data' ] );
//
//                        $message = st()->load_template( 'user/cancel-booking/success', 'payfast', [ 'cancel_data' => $cancel_data ] );
//
//                        $cancel_order_id    = $order_id;
//                        $cancel_cancel_data = $cancel_data;
//
//                        //3rd action
//                        do_action( 'st_booking_cancel_order_item', $order_id );
//
//                        if ( $enable_email_cancel == 'on' ) {
//                            $this->_send_email_refund( $order_id, 'has-refund' );
//                        }
//                        if ( $enable_partner_email_cancel == 'on' ) {
//                            if ( $user_role == 'partner' ) {
//                                $this->_send_email_refund_for_partner( $order_id, $user_email, '' );
//                            }
//                        }
//
//                        echo json_encode( [
//                            'status'  => 1,
//                            'message' => $message,
//                            'step'    => ''
//                        ] );
//                        die;
//                    }
//
//                }
//
//            }
//            echo json_encode( [
//                'status'  => 1,
//                'message' => '<div class="text-danger">' . __( 'You can not cancel this booking', 'trizen-helper' ) . '</div>',
//                'step'    => ''
//
//            ] );
//            die;
//        }


        public function _add_custom_price() {
            $check_in  = request('calendar_check_in', '');
            $check_out = request('calendar_check_out', '');
            if (empty($check_in) || empty($check_out)) {
                echo json_encode([
                    'type'    => 'error',
                    'status'  => 0,
                    'message' => __('The check in or check out field is not empty.', 'trizen-helper')
                ]);
                die();
            }
            $check_in  = strtotime($check_in);
            $check_out = strtotime($check_out);
            if ($check_in > $check_out) {
                echo json_encode([
                    'type'    => 'error',
                    'status'  => 0,
                    'message' => __('The check out is later than the check in field.', 'trizen-helper')
                ]);
                die();
            }
            $status = request('calendar_status', 'available');
            if ($status == 'available') {
                if ( request( 'price_by_per_person', false ) == 'true' ) {
                    if ( filter_var( $_POST[ 'calendar_adult_price' ], FILTER_VALIDATE_FLOAT ) === false ) {
                        echo json_encode( [
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __( 'The adult price field is not a number.', 'trizen-helper' )
                        ] );
                        die();
                    }
                    if ( filter_var( $_POST[ 'calendar_child_price' ], FILTER_VALIDATE_FLOAT ) === false ) {
                        echo json_encode( [
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __( 'The child price field is not a number.', 'trizen-helper' )
                        ] );
                        die();
                    }
                } else {
                    if (filter_var($_POST['calendar_price'], FILTER_VALIDATE_FLOAT) === false) {
                        echo json_encode([
                            'type'    => 'error',
                            'status'  => 0,
                            'message' => __('The price field is not a number.', 'trizen-helper')
                        ]);
                        die();
                    }
                }
            }
            $price       = floatval(request('calendar_price', 0));
            $post_id     = request('calendar_post_id', '');
            $post_id     = post_origin($post_id);
            $adult_price = floatval( request( 'calendar_adult_price', '' ) );
            $child_price = floatval( request( 'calendar_child_price', '' ) );
            $parent_id   = get_post_meta($post_id, 'room_parent', true);
            for ($i = $check_in; $i <= $check_out; $i = strtotime('+1 day', $i)) {
                $data = [
                    'post_id'     => $post_id,
                    'post_type'   => 'hotel_room',
                    'check_in'    => $i,
                    'check_out'   => $i,
                    'price'       => $price,
                    'status'      => $status,
                    'parent_id'   => $parent_id,
                    'is_base'     => 0,
                    'adult_price' => $adult_price,
                    'child_price' => $child_price,
                ];
                TS_Hotel_Room_Availability::inst()->insertOrUpdate($data);
            }
            echo json_encode([
                'type'    => 'success',
                'status'  => 1,
                'message' => __('Successfully', 'trizen-helper')
            ]);
            die();
        }

        /**
         * @since  1.1.8
         * @update 1.2.0
         * */
        static function checkTableDuplicate($post_types = []) {
            global $wpdb;

            if (is_array($post_types) && count($post_types)) {
                foreach ($post_types as $post_type) {
                    $table = $wpdb->prefix . $post_type;
                    if (empty(self::$_check_table_duplicate[$post_type])) {
                        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
                            return false;
                        } else {
                            self::$_check_table_duplicate[$post_type] = true;
                        }
                    }
                }
            } else {
                if (empty(self::$_check_table_duplicate[$post_types])) {
                    $table = $wpdb->prefix . $post_types;
                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
                        return false;
                    } else
                        self::$_check_table_duplicate[$post_types] = true;
                }
            }
            return true;
        }

        static function _get_min_max_date_ordered_new($room_id, $start, $end){
            if ( !TSAdminRoom::checkTableDuplicate( 'ts_hotel' ) ) return '';
            global $wpdb;
            $hotel_id = intval( get_post_meta( $room_id, 'room_parent', true ) );
            if ( !empty( $hotel_id ) ) {
                $key_post_type = "ts_hotel";
            } else {
                $key_post_type = "hotel_room";
            }
            $sql = "SELECT
				MIN(check_in_timestamp) as min_date,
				MAX(check_out_timestamp) as max_date
				FROM {$wpdb->prefix}ts_order_item_meta
				WHERE room_origin = '{$room_id}'
				AND ts_booking_post_type = '{$key_post_type}'
				AND check_in_timestamp >= {$start}
				AND check_out_timestamp <= {$end}
				AND status NOT IN ('trash', 'canceled')";

            $result = $wpdb->get_row( $sql, ARRAY_A );

            if ( is_array( $result ) && count( $result ) )
                return $result;

            return '';
        }

        static function _get_full_ordered_new($room_id, $start, $end){
            if ( !TSAdminRoom::checkTableDuplicate( 'ts_hotel' ) ) return '';

            $hotel_id = intval( get_post_meta( $room_id, 'room_parent', true ) );
            if ( !empty( $hotel_id ) ) {
                $key_post_type = "ts_hotel";
            } else {
                $key_post_type = "hotel_room";
            }

            global $wpdb;
            $sql    = "
				SELECT
				room_origin,
				check_in_timestamp,
				check_out_timestamp,
				room_num_search as number_room
				FROM {$wpdb->prefix}ts_order_item_meta
				WHERE room_origin = '{$room_id}'
				AND ts_booking_post_type = '{$key_post_type}'
				AND check_in_timestamp >= {$start}
				AND check_out_timestamp <= {$end}
				AND `status` NOT IN ('trash', 'canceled')";
            $result = $wpdb->get_results( $sql, ARRAY_A );
            if ( is_array( $result ) && count( $result ) ) {
                return $result;
            }

            return '';
        }

        static function _getDisableCustomDate($room_id, $month, $month2, $year, $year2, $date_format = false)
        {
            $date1 = $year . '-' . $month . '-01';
            $date2 = strtotime($year2 . '-' . $month2 . '-01');
            $date2 = date('Y-m-t', $date2);
            $date_time_format = TravelHelper::getDateFormat();
            if (!empty($date_format)) {
                $date_time_format = $date_format;
            }
            global $wpdb;
            $sql = "
                    SELECT
                        `check_in`,
                        `check_out`,
                        `number`,
                        `status`,
                        `priority`
                    FROM
                        {$wpdb->prefix}st_room_availability
                    WHERE
                        post_id = {$room_id}
                    AND (
                        (
                            '{$date1}' < DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND '{$date2}' > DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                        OR (
                            '{$date1}' BETWEEN DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                        OR (
                            '$date2' BETWEEN DATE_FORMAT(FROM_UNIXTIME(check_in), '%Y-%m-%d')
                            AND DATE_FORMAT(FROM_UNIXTIME(check_out), '%Y-%m-%d')
                        )
                    )";
            $results = $wpdb->get_results($sql);
            $default_state = get_post_meta($room_id, 'default_state', true);
            if (!$default_state) $default_state = 'available';
            $list_date = [];
            $start = strtotime($date1);
            $end = strtotime($date2);
            if (is_array($results) && count($results)) {
                for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                    $in_date = false;
                    foreach ($results as $key => $val) {
                        $status = $val->status;
                        if ($i == $val->check_in && $i == $val->check_out) {
                            if ($status == 'unavailable') {
                                $date = $i;
                            } else {
                                unset($date);
                            }
                            if (!$in_date) {
                                $in_date = true;
                            }
                        }
                    }
                    if ($in_date && isset($date)) {
                        $list_date[] = date($date_time_format, $date);
                        unset($date);
                    } else {
                        if (!$in_date && $default_state == 'not_available') {
                            $list_date[] = date($date_time_format, $i);
                            unset($in_date);
                        }
                    }
                }
            } else {
                if ($default_state == 'not_available') {
                    for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                        $list_date[] = date($date_time_format, $i);
                    }
                }
            }
            return $list_date;
        }

        public function _get_availability_hotel_room()
        {
            $list_date   = [];
            $room_id     = request( 'post_id', '' );
            $check_in    = request( 'start', '' );
            $check_out   = request( 'end', '' );
            $room_origin = post_origin( $room_id );
            $hotel_id    = intval( get_post_meta( $room_origin, 'room_parent', true ) );

            $discount_type=get_post_meta($room_id,'discount_type_no_day',true);
            $discount=get_post_meta($room_id,'discount_rate',true);
            $is_sale_schedule=false;
            $sale_price_from=false;
            $sale_price_to=false;
            $adult_number = request( 'adult_number', '' );
            $child_number = request( 'child_number', '' );

            //if empty hotel ->>>> room only
            if ( empty( $hotel_id ) ) {
                $hotel_id = $room_id;
            }

            $allow_full_day = get_post_meta( $hotel_id, 'allow_full_day', true );
            if ( !$allow_full_day || $allow_full_day == '' ) $allow_full_day = 'on';

            $year = date( 'Y', $check_in );
            if ( empty( $year ) ) $year = date( 'Y' );
            $year2 = date( 'Y', $check_out );
            if ( empty( $year2 ) ) $year2 = date( 'Y' );

            $month = date( 'm', $check_in );
            if ( empty( $month ) ) $month = date( 'm' );

            $month2 = date( 'm', $check_out );
            if ( empty( $month2 ) ) $month2 = date( 'm' );


            //$result = HotelHelper::_get_full_ordered( $room_origin, $month, $month2, $year, $year2 );
            $result =   TSAdminRoom::_get_full_ordered_new( $room_origin, $check_in, $check_out );

            $number_room = get_post_meta( $room_id, 'number_room', true );
            //$min_max     = HotelHelper::_get_min_max_date_ordered( $room_origin, $year, $year2 );
            $min_max     = TSAdminRoom::_get_min_max_date_ordered_new( $room_origin, $check_in, $check_out );

            $list_date_fist_half_day = [];
            $list_date_last_half_day = [];
            $array_fist_half_day = [];
            $array_last_half_day = [];

            if ( is_array( $min_max ) && count( $min_max ) && is_array( $result ) && count( $result ) ) {
                $disable = [];
                for ( $i = intval( $min_max[ 'min_date' ] ); $i <= intval( $min_max[ 'max_date' ] ); $i = strtotime( '+1 day', $i ) ) {
                    $num_room = 0;
                    $num_room_first_half_day = 0;
                    $num_room_last_half_day = 0;
                    foreach ( $result as $key => $date ) {
                        if ( $allow_full_day == 'on' ) {
                            if ( $i >= intval( $date[ 'check_in_timestamp' ] ) && $i <= intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room += $date[ 'number_room' ];
                            }
                        } else {
                            if ( $i > intval( $date[ 'check_in_timestamp' ] ) && $i < intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room += $date[ 'number_room' ];
                            }

                            if ( $i == intval( $date[ 'check_in_timestamp' ] ) ) {
                                $num_room_first_half_day += $date[ 'number_room' ];
                            }
                            if ( $i == intval( $date[ 'check_out_timestamp' ] ) ) {
                                $num_room_last_half_day += $date['number_room'];
                            }
                        }
                    }
                    $disable[ $i ] = $num_room;
                    $array_fist_half_day[ $i ] = $num_room_first_half_day;
                    $array_last_half_day[ $i ] = $num_room_last_half_day;
                }
                if ( count( $disable ) ) {
                    foreach ( $disable as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date[] = date( getDateFormat(), $key );
                    }
                }
                if ( count( $array_fist_half_day ) ) {
                    foreach ( $array_fist_half_day as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date_fist_half_day[] = date( getDateFormat(), $key );
                    }
                }
                if ( count( $array_last_half_day ) ) {
                    foreach ( $array_last_half_day as $key => $num_room ) {
                        if ( intval( $num_room ) >= $number_room )
                            $list_date_last_half_day[] = date( getDateFormat(), $key );
                    }
                }
            }

            $list_date_2 = TSAdminRoom::_getDisableCustomDate( $room_origin, $month, $month2, $year, $year2 );

            $date1  = strtotime( $year . '-' . $month . '-01' );
            $date2  = strtotime( $year2 . '-' . $month2 . '-01' );
            $date2  = strtotime( date( 'Y-m-t', $date2 ) );
            $today  = strtotime( date( 'Y-m-d' ) );
            $return = [];

            $booking_period = intval( get_post_meta( $hotel_id, 'hotel_booking_period', true ) );

            $room_available = TS_Hotel_Room_Availability::inst()
                ->where('check_in >=', $check_in)
                ->where('check_out <=', $check_out)
                ->where('post_id', $room_origin)
                ->where('status', 'available')
                ->get()->result();
            $data_price_room = [];
            if(!empty($room_available)){
                foreach ($room_available as $kk => $vv){
                    $data_price_room[$vv['check_in']] = ts_apply_discount($vv['price'],$discount_type,$discount);
                }
            }

            for ( $i = $date1; $i <= $date2; $i = strtotime( '+1 day', $i ) ) {
                $period = dateDiff( date( 'Y-m-d', $today ), date( 'Y-m-d', $i ) );
                $d      = date( getDateFormat(), $i );
                if ( in_array( $d, $list_date ) or ( in_array( $d, $list_date_fist_half_day ) and in_array( $d, $list_date_last_half_day ) ) ) {
                    $return[] = [
                        'start'  => date( 'Y-m-d', $i ),
                        'date'   => date( 'Y-m-d', $i ),
                        'day'    => date( 'd', $i ),
                        'status' => 'booked'
                    ];
                } else {
                    if ( $i < $today ) {
                        $return[] = [
                            'start'  => date( 'Y-m-d', $i ),
                            'date'   => date( 'Y-m-d', $i ),
                            'day'    => date( 'd', $i ),
                            'status' => 'past'
                        ];
                    } else {
                        if ( in_array( $d, $list_date_2 ) ) {
                            $return[] = [
                                'start'  => date( 'Y-m-d', $i ),
                                'date'   => date( 'Y-m-d', $i ),
                                'day'    => date( 'd', $i ),
                                'status' => 'disabled'
                            ];
                        } else {
                            if ( $period < $booking_period ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'disabled'
                                ];
                            } else if ( in_array( $d, $list_date_fist_half_day ) ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available_allow_fist',
                                    'price'  => (isset($data_price_room[$i]) ? format_money($data_price_room[$i]) : 0)
                                ];
                            } else if ( in_array( $d, $list_date_last_half_day ) ) {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available_allow_last',
                                    'price'  => (isset($data_price_room[$i]) ? format_money($data_price_room[$i]) : 0)
                                ];
                            } else {
                                $return[] = [
                                    'start'  => date( 'Y-m-d', $i ),
                                    'date'   => date( 'Y-m-d', $i ),
                                    'day'    => date( 'd', $i ),
                                    'status' => 'available',
                                    'price'  => (isset($data_price_room[$i]) ? format_money($data_price_room[$i]) : 0)
                                ];
                            }

                        }

                    }
                }
            }

            echo json_encode( $return );
            die;
        }

        public function __cronjob_fill_availability($offset=0, $limit=-1, $day=null) {
            global $wpdb;
            if(!$day){
                $today=new DateTime(date('Y-m-d'));
                $today->modify('+ 6 months');
                $day=$today->modify('+ 1 day');
            }

            $table='ts_room_availability';

            $rooms=new WP_Query(array(
                'posts_per_page'=>$limit,
                'post_type'=>'hotel_room',
                'offset' => $offset
            ));
            $insertBatch=[];
            $ids=[];

            while ($rooms->have_posts()) {
                $rooms->the_post();
                $price          = get_post_meta(get_the_ID(),'price',true);
                $parent         = get_post_meta(get_the_ID(),'room_parent',true);
                $status         = get_post_meta(get_the_ID(),'default_state',true);
                $number         = get_post_meta(get_the_ID(),'number_room',true);
                $allow_full_day = get_post_meta(get_the_ID(),'allow_full_day',true);
                $adult_number   = intval( get_post_meta( get_the_ID(), 'adult_number', true ) );
                $child_number   = intval( get_post_meta( get_the_ID(), 'children_number', true ) );
                $booking_period = intval(get_post_meta($parent, 'hotel_booking_period', true));
                if(empty($booking_period)) $booking_period = 0;
                if(!$allow_full_day) $allow_full_day='on';
                $adult_price = get_post_meta( get_the_ID(), 'adult_price', true );
                $child_price = get_post_meta( get_the_ID(), 'child_price', true );

                $insertBatch[]=$wpdb->prepare("(%d,%d,%d,%d,%s,%d,%s,%d,%s,%d,%d,%d,%d,%d,%d)",$day->getTimestamp(),$day->getTimestamp(),get_the_ID(),$parent,'hotel_room',$number,$status,$price,$allow_full_day,$adult_number,$child_number,1,$booking_period, $adult_price, $child_price);

                $ids[]=get_the_ID();
            }

            if(!empty($insertBatch)) {
                $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$table} (check_in,check_out,post_id,parent_id,post_type,`number`,`status`,price,	allow_full_day,adult_number,child_number,is_base,booking_period, adult_price, child_price) VALUES ".implode(",\r\n",$insertBatch));

                // add log
                //ST_Cronjob_Log_Model::inst()->log('room_fill_availability_'.$day->format('Y_m_d'),json_encode($ids));
            }

            wp_reset_postdata();
        }

        public static function fill_post_availability($post_id,$timestamp=null) {
            $data  = [];
            global $wpdb;
            $table = 'ts_room_availability';

            $price          = get_post_meta($post_id,'price',true);
            $parent         = get_post_meta($post_id,'room_parent',true);
            $status         = get_post_meta($post_id,'default_state',true);
            $number         = get_post_meta($post_id,'number_room',true);
            $allow_full_day = get_post_meta($post_id,'allow_full_day',true);
            if(!$allow_full_day) $allow_full_day='on';
            $rs = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin',$post_id)
                ->where('check_in_timestamp <=',$timestamp)
                ->where('check_out_timestamp >=',$timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')",false,true)
                ->get(1)->row();
            $number_end = TS_Order_Item_Model::inst()
                ->select('count(room_num_search) as number_booked')
                ->where('room_origin',$post_id)
                ->where('check_out_timestamp',$timestamp)
                ->where("STATUS NOT IN ('trash', 'canceled')",false,true)
                ->get(1)->row();
            $adult_number = intval( get_post_meta( get_the_ID(), 'adult_number', true ) );
            $child_number = intval( get_post_meta( get_the_ID(), 'child_number', true ) );
            $adult_price = get_post_meta( $post_id, 'adult_price', true );
            $child_price = get_post_meta( $post_id, 'child_price', true );

            $data['check_in']       = $timestamp;
            $data['check_out']      = $timestamp;
            $data['parent_id']      = $parent;
            $data['post_type']      = 'hotel_room';
            $data['number']         = $number;
            $data['status']         = $status;
            $data['price']          = $price;
            $data['allow_full_day'] = $allow_full_day;
            $data['number_booked']  = $rs['number_booked'];
            $data['number_end']     = $number_end['number_booked'];
            $data['adult_number']   = $adult_number;
            $data['child_number']   = $child_number;
            $data['adult_price']    = $adult_price;
            $data['child_price']    = $child_price;

//                $model=TS_Availability_Model::inst();
//
//                $data['id']=$model->insert($data);

            $insert = $wpdb->prepare("(%d,%d,%d,%d,%s,%d,%d,%d,%s,%d,%s,%d,%d,%d,%d)",$timestamp,$timestamp,$post_id,$parent,'hotel_room',$number,$rs['number_booked'],$number_end['number_booked'],$status,$price,$allow_full_day,$adult_number,$child_number, $adult_price, $child_price);

            $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$table} (check_in,check_out,post_id,parent_id,post_type,`number`,number_booked,number_end,`status`,price,allow_full_day,adult_number, child_number, adult_price, child_price) VALUES ".$insert);

            return $data;
        }

        public function parse_query_hotel_room( $query ) {
            global $pagenow;
            if ( isset( $_GET[ 'post_type' ] ) ) {
                $type = $_GET[ 'post_type' ];
                if ( 'hotel_room' == $type && is_admin() && $pagenow == 'edit.php' && isset( $_GET[ 'filter_st_hotel' ] ) && $_GET[ 'filter_st_hotel' ] != '' ) {
                    add_filter( 'posts_where', [ $this, 'posts_where_hotel_room' ] );
                    add_filter( 'posts_join', [ $this, 'posts_join_hotel_room' ] );
                }
            }

        }

        public function posts_where_hotel_room( $where ){
            global $wpdb;
            $hotel_name = $_GET[ 'filter_st_hotel' ];
            $where .= " AND mt2.meta_value in (select ID from {$wpdb->prefix}posts where post_title like '%{$hotel_name}%' and post_type = 'st_hotel' and post_status in ('publish', 'private') ) ";

            return $where;
        }

        public function posts_join_hotel_room( $join ) {
            global $wpdb;
            $join .= " inner join {$wpdb->prefix}postmeta as mt2 on mt2.post_id = {$wpdb->prefix}posts.ID and mt2.meta_key='room_parent' ";

            return $join;
        }

        public function __run_fill_old_order($key = '') {
            $ids = [];
            global $wpdb;
            $table = $wpdb->prefix . 'ts_availability';
            $model = TS_Order_Item_Model::inst();
            $orderItems = $model->where("ts_booking_post_type in ('ts_hotel','hotel_room')", false, true)
                ->where("STATUS NOT IN('canceled','trash')", false, true)->get()->result();
            if (!empty($orderItems)) {

                foreach ($orderItems as $data) {
                    if (!empty($data['room_origin'])) {
                        if (in_array($data['id'], $ids)) continue;
                        $ids[] = $data['id'];
                        $booked = !empty($data['room_num_search']) ? intval($data['room_num_search']) : 1;

                        $sql = $wpdb->prepare("UPDATE {$table} SET number_booked = IFNULL(number_booked, 0) + %d WHERE post_id = %d AND check_in = %s", $booked, $data['room_origin'], $data['check_in_timestamp']);
                        $wpdb->query($sql);
                        // Check allowed to set Number End
                        if (get_post_meta($data['ts_booking_id'], 'allow_full_day', true) != 'off') {
                            $sql = $wpdb->prepare("UPDATE {$table} SET number_end = IFNULL(number_end, 0) + %d WHERE post_id = %d AND check_in = %s", $booked, $data['room_origin'], $data['check_out_timestamp']);
                            $wpdb->query($sql);
                        }

                    }
                }
            }
        }

        static function isset_table( $table_name ) {
            global $wpdb;
            $table = $wpdb->prefix . $table_name;
            if ( !empty( self::$_check_table_duplicate[ $table_name ] ) ) return true;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                return false;
            }
            return true;
        }

        static function checkIssetPost($post_id = '', $post_type = '') {
            global $wpdb;
            if (intval($post_id) && !empty($post_type)) {
                $table = $wpdb->prefix . $post_type;
                $sql = "SELECT post_id FROM {$table} WHERE post_id = '{$post_id}'";

                $wpdb->query($sql);

                $num_rows = $wpdb->num_rows;

                return $num_rows;
            } else {
                return 0;
            }
        }

        static function updateDuplicate( $post_type = 'ts_hotel', $data = [], $where = [] ){
            global $wpdb;
            $table = $wpdb->prefix . $post_type;
            $wpdb->update( $table, $data, $where, $format = null, $where_format = null );
        }
        static function insertDuplicate( $post_type = 'ts_hotel', $data = [] ){
            global $wpdb;
            $table = $wpdb->prefix . $post_type;

            $wpdb->insert( $table, $data );
        }

        static function _update_avg_price($post_id = false) {
            if (!$post_id) {
                $post_id = get_the_ID();
            }
            $post_type = get_post_type($post_id);
            if ($post_type == 'ts_hotel') {
                $hotel_id = $post_id;
                $is_auto_caculate = get_post_meta($hotel_id, 'is_auto_caculate', true);
                if ($is_auto_caculate != 'off') {
                    $query = [
                        'post_type'      => 'hotel_room',
                        'posts_per_page' => -1,
                        'meta_key'       => 'room_parent',
                        'meta_value'     => $hotel_id,
                        'post_status'    => array( 'publish' )
                    ];
                    $traver = new WP_Query($query);
                    $price = 0;
                    while ($traver->have_posts()) {
                        $traver->the_post();
                        if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                            $item_price = (float)get_post_meta(get_the_ID(), 'adult_price', true);
                        } else {
                            $item_price = (float)get_post_meta(get_the_ID(), 'price', true);
                        }

                        $price += $item_price;
                    }
                    wp_reset_query();
                    wp_reset_postdata();
                    $avg_price = 0;
                    if ($traver->post_count) {
                        $avg_price = $price / $traver->post_count;
                    }
                    update_post_meta($hotel_id, 'trizen_hotel_regular_price', $avg_price);
                }
            }
            if ( $post_type == 'hotel_room' ) {
                $hotel_id = get_post_meta( $post_id, 'room_parent', true );
                if ( !empty( $hotel_id ) ) {
                    $is_auto_caculate = get_post_meta( $hotel_id, 'is_auto_caculate', true );
                    if ( $is_auto_caculate != 'off' ) {
                        $query  = [
                            'post_type'      => 'hotel_room',
                            'posts_per_page' => 999,
                            'meta_key'       => 'room_parent',
                            'meta_value'     => $hotel_id
                        ];
                        $traver = new WP_Query( $query );
                        $price  = 0;
                        while ( $traver->have_posts() ) {
                            $traver->the_post();
                            $discount   = get_post_meta( get_the_ID(), 'discount_rate', TRUE );
                            if ( get_post_meta( get_the_ID(), 'price_by_per_person', true ) == 'on' ) {
                                $adult_price = floatval( get_post_meta( get_the_ID(), 'adult_price', true ) );
                                $child_price = floatval( get_post_meta( get_the_ID(), 'child_price', true ) );
                                $item_price  = max( $adult_price, $child_price );
                            } else {
                                $item_price = get_post_meta( get_the_ID(), 'price', TRUE );
                            }
                            if ( $discount ) {
                                if ( $discount > 100 ) $discount = 100;
                                $item_price = $item_price - ( $item_price / 100 ) * $discount;
                            }
                            $price += $item_price;
                        }
                        wp_reset_query();
                        $avg_price = 0;
                        if ( $traver->post_count ) {
                            $avg_price = $price / $traver->post_count;
                        }
                        update_post_meta( $hotel_id, 'trizen_hotel_regular_price', $avg_price );
                    }
                }
            }
        }

        static function _update_min_price($post_id = false) {
            if (!$post_id) {
                $post_id = get_the_ID();
            }

            $post_type = get_post_type($post_id);
            if ($post_type == 'ts_hotel') {
                $hotel_id = $post_id;
                $query = [
                    'post_type'      => 'hotel_room',
                    'posts_per_page' => -1,
                    'meta_key'       => 'room_parent',
                    'meta_value'     => $hotel_id,
                    'post_status'    => array('publish')
                ];
                $traver = new WP_Query($query);

                $prices = [];
                while ($traver->have_posts()) {
                    $traver->the_post();
//                    $disable_avai_check = st()->get_option('disable_availability_check', 'off');
                    if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                        $query_price = TS_Hotel_Room_Availability::inst()
                            ->select("min(CAST(adult_price as DECIMAL)) as min_price")
                            ->where('status', 'available')
                            ->where('post_id', get_the_ID())
                            ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                            ->get()->result();

                        if (!empty($query_price)) {
                            $item_price = floatval($query_price[0]['min_price']);
                        } else {
                            $item_price = floatval(get_post_meta(get_the_ID(), 'price', true));
                        }
                    } else {
                        $query_price = TS_Hotel_Room_Availability::inst()
                            ->select("min(CAST(price as DECIMAL)) as min_price")
                            ->where('status', 'available')
                            ->where('post_id', get_the_ID())
                            ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                            ->get()->result();
                        if (!empty($query_price)) {
                            $item_price = $query_price[0]['min_price'];
                        } else {

                            $item_price = get_post_meta(get_the_ID(), 'price', true);
                        }
                    }
                    // if ($disable_avai_check == 'off') {
                    //     $item_price = get_post_meta(get_the_ID(), 'price', true);

                    // } else {
                    //     if (get_post_meta(get_the_ID(), 'price_by_per_person', true) == 'on') {
                    //         $query_price = TS_Hotel_Room_Availability::inst()
                    //             ->select("min(CAST(adult_price as DECIMAL)) as min_price")
                    //             ->where('status', 'available')
                    //             ->where('post_id', get_the_ID())
                    //             ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                    //             ->get()->result();

                    //         if (!empty($query_price)) {
                    //             $item_price = floatval($query_price[0]['min_price']);
                    //         } else {
                    //             $item_price = floatval(get_post_meta(get_the_ID(), 'price', true));
                    //         }
                    //     } else {
                    //         $query_price = TS_Hotel_Room_Availability::inst()
                    //             ->select("min(CAST(price as DECIMAL)) as min_price")
                    //             ->where('status', 'available')
                    //             ->where('post_id', get_the_ID())
                    //             ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", null, true)
                    //             ->get()->result();

                    //         if (!empty($query_price)) {
                    //             $item_price = $query_price[0]['min_price'];
                    //         } else {
                    //             $item_price = get_post_meta(get_the_ID(), 'price', true);
                    //         }
                    //     }
                    // }

                    $prices[] = $item_price;
                }
                // wp_reset_query();
                wp_reset_postdata();
                if (!empty($prices)) {
                    $min_price = min($prices);
                    update_post_meta($post_id, 'min_price', $min_price);
                } else {
                    update_post_meta($hotel_id, 'min_price', '0');
                }
            }
            if ( $post_type == 'hotel_room' ) {
                $hotel_id = get_post_meta( $post_id, 'room_parent', true );
                if ( !empty( $hotel_id ) ) {
                    $query  = [
                        'post_type'      => 'hotel_room',
                        'posts_per_page' => 999,
                        'meta_key'       => 'room_parent',
                        'meta_value'     => $hotel_id
                    ];
                    $traver = new WP_Query( $query );

                    $prices = [];
                    while ( $traver->have_posts() ) {
                        $traver->the_post();
                        $discount   = get_post_meta( get_the_ID(), 'discount_rate', TRUE );
                        if ( get_post_meta( get_the_ID(), 'price_by_per_person', true ) == 'on' ) {
                            $adult_price = floatval( get_post_meta( get_the_ID(), 'adult_price', true ) );
                            $child_price = floatval( get_post_meta( get_the_ID(), 'child_price', true ) );
                            $item_price = min( $adult_price, $child_price );
                        } else {
                            $item_price = get_post_meta( get_the_ID(), 'price', TRUE );
                        }
                        if ( $discount ) {
                            if ( $discount > 100 ) $discount = 100;
                            $item_price = $item_price - ( $item_price / 100 ) * $discount;
                        }
                        $prices[] = $item_price;
                    }
                    wp_reset_query();
                    if ( !empty( $prices ) ) {
                        $min_price = min( $prices );
                        update_post_meta( $hotel_id, 'min_price', $min_price );
                    }
                }
            }
        }

        static function _update_duplicate_data($id, $data) {
            if (!TSAdminRoom::checkTableDuplicate('ts_hotel')) return;
            if (get_post_type($id) == 'ts_hotel') {
                $num_rows       = TSAdminRoom::checkIssetPost($id, 'ts_hotel');
                $location_str   = get_post_meta($id, 'multi_location', true);
                $location_id    = ''; // location_id
                $address        = get_post_meta($id, 'address', true); // address
                $allow_full_day = get_post_meta($id, 'allow_full_day', true); // address

                $rate_review          = TSReview::get_avg_rate($id); // rate review
                $hotel_star           = get_post_meta($id, 'hotel_star', true); // hotel star
                $price_avg            = get_post_meta($id, 'trizen_hotel_regular_price', true); // price avg
                $min_price            = get_post_meta($id, 'min_price', true); // price avg
                $hotel_booking_period = get_post_meta($id, 'hotel_booking_period', true); // price avg
                $map_lat              = get_post_meta($id, 'map_lat', true); // map_lat
                $map_lng              = get_post_meta($id, 'map_lng', true); // map_lng

                if ($num_rows == 1) {
                    $data = [
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allow_full_day,
                        'rate_review'          => $rate_review,
                        'hotel_star'           => $hotel_star,
                        'price_avg'            => $price_avg,
                        'min_price'            => $min_price,
                        'hotel_booking_period' => $hotel_booking_period,
                        'map_lat'              => $map_lat,
                        'map_lng'              => $map_lng,
                    ];
                    $where = [
                        'post_id' => $id
                    ];
                    TSAdminRoom::updateDuplicate('ts_hotel', $data, $where);
                } elseif ($num_rows == 0) {
                    $data = [
                        'post_id'              => $id,
                        'multi_location'       => $location_str,
                        'id_location'          => $location_id,
                        'address'              => $address,
                        'allow_full_day'       => $allow_full_day,
                        'rate_review'          => $rate_review,
                        'hotel_star'           => $hotel_star,
                        'price_avg'            => $price_avg,
                        'min_price'            => $min_price,
                        'hotel_booking_period' => $hotel_booking_period,
                        'map_lat'              => $map_lat,
                        'map_lng'              => $map_lng,
                    ];
                    TSAdminRoom::insertDuplicate('ts_hotel', $data);
                }
            }

            // for room
            if ( get_post_type( $id ) == 'hotel_room' ) {
                $num_rows       = TSAdminRoom::checkIssetPost( $id, 'hotel_room' );
                $allow_full_day = get_post_meta( $id, 'allow_full_day', true ); // address
                $data           = [
                    'room_parent'    => get_post_meta( $id, 'room_parent', true ),
                    'multi_location' => get_post_meta( $id, 'multi_location', true ),
                    'id_location'    => get_post_meta( $id, 'id_location', true ),
                    'address'        => get_post_meta( $id, 'address', true ),
                    'allow_full_day' => $allow_full_day,
                    'price'          => get_post_meta( $id, 'price', true ),
                    'number_room'    => get_post_meta( $id, 'number_room', true ),
                    'discount_rate'  => get_post_meta( $id, 'discount_rate', true ),
                    'adult_number'   => get_post_meta( $id, 'adult_number', true),
                    'child_number'   => get_post_meta( $id, 'children_number', true),
                    'adult_price'    => get_post_meta( $id, 'adult_price', true ),
                    'child_price'    => get_post_meta( $id, 'child_price', true ),
                    'status'         => get_post_field('post_status', $id)
                ];
                if ( $num_rows == 1 ) {
                    $where = [
                        'post_id' => $id
                    ];
                    TSAdminRoom::updateDuplicate( 'hotel_room', $data, $where );
                } elseif ( $num_rows == 0 ) {
                    $data[ 'post_id' ] = $id;
                    TSAdminRoom::insertDuplicate( 'hotel_room', $data );
                }

                // Update Availability
                $model = Ts_Hotel_Room_Availability::inst();
                $model->where('post_id',$id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->update(array(
                        'parent_id'      => $data['room_parent'],
                        'allow_full_day' => $data['allow_full_day'],
                        'number'         => $data['number_room'],
                        'adult_number'   => $data['adult_number'],
                        'child_number'   => $data['child_number']
                    ));

                $model->where('post_id',$id)
                    ->where("check_in >= UNIX_TIMESTAMP(CURRENT_DATE)", true, false)
                    ->where('is_base', '1')
                    ->update(array(
                        'price'       => $data['price'],
                        'adult_price' => $data['adult_price'],
                        'child_price' => $data['child_price'],
                    ));
                $model->where('post_id', $id)->update(['parent_id' => get_post_meta( $id, 'room_parent', true )]);
            }
        }

        public static function __cronjob_update_min_avg_price($offset, $limit = 2) {
            global $wpdb;
            $list_hotel = new WP_Query(array(
                'posts_per_page' => $limit,
                'post_type'      => 'ts_hotel',
                'offset'         => $offset
            ));

            $hotel_ids=[];
            if ($list_hotel->have_posts()) {
                while ($list_hotel->have_posts()) {
                    $list_hotel->the_post();
                    $hotel_id = get_the_ID();
                    TSAdminRoom::_update_avg_price($hotel_id);
                    TSAdminRoom::_update_min_price($hotel_id);
                    TSAdminRoom::_update_duplicate_data($hotel_id, []);
                }
            }

            wp_reset_postdata();
        }

        function _do_update_hotel_min_price( $hotel_id, $current_meta_price = false, $room_id = false ) {
            if ( !$hotel_id ) return;
            $query = [
                'post_type'      => 'hotel_room',
                'posts_per_page' => 100,
                'meta_key'       => 'room_parent',
                'meta_value'     => $hotel_id
            ];
            if ( $room_id ) {
                $query[ 'posts_not_in' ] = [ $room_id ];
            }
            $q = new WP_Query( $query );
            $min_price = 0;
            $i         = 1;
            while ( $q->have_posts() ) {
                $q->the_post();
                if ( get_post_meta( get_the_ID(), 'price_by_per_person', true ) == 'on' ) {
                    $adult_price = floatval( get_post_meta( get_the_ID(), 'adult_price', true ) );
                    $child_price = floatval( get_post_meta( get_the_ID(), 'child_price', true ) );
                    $price = min($adult_price, $child_price);
                } else {
                    $price = get_post_meta( get_the_ID(), 'price', true );
                }
                if ( $i == 1 ) {
                    $min_price = $price;
                } else {
                    if ( $price < $min_price ) {
                        $min_price = $price;
                    }
                }
                $i++;
            }

            wp_reset_query();

            if ( $current_meta_price !== FALSE ) {
                if ( $current_meta_price < $min_price ) {
                    $min_price = $current_meta_price;
                }
            }
            update_post_meta( $hotel_id, 'min_price', $min_price );
        }

        function hotel_update_min_price( $meta_id, $object_id, $meta_key, $meta_value ) {
            $post_type = get_post_type( $object_id );
            if ( wp_is_post_revision( $object_id ) )
                return;
            if ( $post_type == 'hotel_room' ) {
                //Update old room and new room
                if ( $meta_key == 'room_parent' ) {
                    $old = get_post_meta( $object_id, $meta_key, true );
                    if ( $old != $meta_value ) {
                        $this->_do_update_hotel_min_price( $old, false, $object_id );
                        $this->_do_update_hotel_min_price( $meta_value );
                    } else {
                        $this->_do_update_hotel_min_price( $meta_value );
                    }
                }
            }
        }

        function meta_updated_update_min_price( $meta_id, $object_id, $meta_key, $meta_value ){
            if ( $meta_key == 'price' ) {
                $hotel_id = get_post_meta( $object_id, 'room_parent', true );
                $this->_do_update_hotel_min_price( $hotel_id );
            }
        }

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }
    TSAdminRoom::inst();
}

