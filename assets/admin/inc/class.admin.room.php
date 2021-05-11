<?php

if ( !class_exists( 'TSAdminRoom' ) ) {

    class TSAdminRoom
    {

        protected static $_inst;
        static $_table_version = "1.3.6";
        static $booking_page;
        protected $post_type = 'hotel_room';
        protected static $_cachedAlCurrency = [];
//        private static $_booking_primary_currency;

        protected $order_id=false;

        /**
         *
         *
         * @update 1.1.3
         * */
        function __construct()
        {
            add_action('plugins_loaded', [__CLASS__, '_check_table_hotel_room']);

            add_filter('ts_change_column_ts_hotel_room', [$this, 'ts_change_column_ts_hotel_room_fnc']);
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

        static function _check_table_hotel_room()
        {
            var_dump('database ');
            $dbhelper = new DatabaseHelper(self::$_table_version);
            $dbhelper->setTableName('hotel_room');
            $column = [
                'post_id' => [
                    'type' => 'INT',
                    'length' => 11,
                ],
                'room_parent' => [
                    'type' => 'INT',
                    'length' => 11,
                ],
                /*'multi_location' => [
                    'type' => 'text',
                ],*/
                'id_location' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'address' => [
                    'type' => 'text',
                ],
                /*'allow_full_day' => [
                    'type' => 'varchar',
                    'length' => 255
                ],*/
                'price' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'number_room' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'discount_rate' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'adult_number' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'child_number' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'status' => [
                    'type' => 'varchar',
                    'length' => 20
                ],
            ];

            $column = apply_filters('ts_change_column_ts_hotel_room', $column);

            $dbhelper->setDefaultColums($column);
            $dbhelper->check_meta_table_is_working('ts_hotel_room_table_version');

            return array_keys($column);
        }


        public function getMeta($key)
        {
            return get_post_meta($this->order_id,$key,true);
        }

        /**
         * @todo Get Type of Order: normal_booking or woocommerce
         *
         * @return string
         */
        public function getType()
        {

        }


        /**
         * @todo Check if current order is using Woocommerce Checkout
         *
         * @return bool
         */
        public function isWoocommerceCheckout()
        {
            return $this->getType()=='woocommerce'?true:false;
        }

        /**
         * @return WC_Order
         */
        public function getWoocommerceOrder()
        {
            global $wpdb;

            return new WC_Order($this->order_id);
        }

        /**
         * @todo Get total amount
         *
         * @return float
         */
        public function getTotal()
        {
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

        public function getItems()
        {
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
         *
         *
         * */
        static function get_currency( $theme_option = false ){
            $all = self::$_cachedAlCurrency;
            //return array for theme options choise
            if ( $theme_option ) {
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
            }
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
                            require_once TRIZEN_HELPER_PATH . 'assets/admin/inc/hotel/room_review_form.php';
                        } elseif (get_post_type($post_id) == 'ts_hotel') {
                            require_once TRIZEN_HELPER_PATH . 'assets/admin/inc/hotel/hotel_review_form.php';
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

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }
    TSAdminRoom::inst();
}

