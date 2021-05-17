<?php
$order_id     = '';
$confirm_link = '';
if ( ! class_exists( 'TSCart' ) ) {

    class TSCart{
        static $coupon_error;


        static function init() {

        }

        static function get_checkout_fields() {
            //Logged in User Info
            global $firstname, $user_email;
            wp_get_current_user();
            $ts_phone    = false;
            $first_name  = false;
            $last_name   = false;
            $ts_address  = false;
            $ts_address2 = false;
            $ts_city     = false;
            $ts_province = false;
            $ts_zip_code = false;
            $ts_country  = false;
            if ( is_user_logged_in() ) {
                $user_id     = get_current_user_id();
                $ts_phone    = get_user_meta( $user_id, 'ts_phone', true );
                $first_name  = get_user_meta( $user_id, 'first_name', true );
                $last_name   = get_user_meta( $user_id, 'last_name', true );
                $ts_address  = get_user_meta( $user_id, 'ts_address', true );
                $ts_address2 = get_user_meta( $user_id, 'ts_address2', true );
                $ts_city     = get_user_meta( $user_id, 'ts_city', true );
                $ts_province = get_user_meta( $user_id, 'ts_province', true );
                $ts_zip_code = get_user_meta( $user_id, 'st_zip_code', true );
                $ts_country  = get_user_meta( $user_id, 'ts_country', true );
            }

            $terms_link           = '<a target="_blank" href="' . get_the_permalink( st()->get_option( 'page_terms_conditions' ) ) . '">' . st_get_language( 'terms_and_conditions' ) . '</a>';
            $checkout_form_fields = [
                'ts_first_name' => [
                    'label'    => 'first_name',
                    'icon'     => 'fa-user',
                    'value'    => post( 'ts_first_name', $first_name ),
                    'validate' => 'required|trim|strip_tags',
                ],
                'ts_last_name'  => [
                    'label'       => 'last_name',
                    'placeholder' => 'last_name',
                    'validate'    => 'required|trim|strip_tags',
                    'icon'        => 'fa-user',
                    'value'       => post( 'ts_last_name', $last_name )
                ],
                'ts_email'      => [
                    'label'       => 'Email',
                    'placeholder' => 'email_domain',
                    'type'        => 'text',
                    'validate'    => 'required|trim|strip_tags|valid_email',
                    'value'       => post( 'ts_email', $user_email ),
                    'icon'        => 'fa-envelope'

                ],
                'ts_phone'      => [
                    'label'       => 'Phone',
                    'placeholder' => 'Your_Phone',
                    'validate'    => 'required|trim|strip_tags',
                    'icon'        => 'fa-phone',
                    'value'       => post( 'ts_phone', $ts_phone ),

                ],
                'ts_address'    => [
                    'label'       => 'address_line_1',
                    'placeholder' => 'your_address_line_1',
                    'icon'        => 'fa-map-marker',
                    'value'       => post( 'ts_address', $ts_address ),
                ],
                'ts_address2'   => [
                    'label'       => 'address_line_2',
                    'placeholder' => 'your_address_line_2',
                    'icon'        => 'fa-map-marker',
                    'value'       => post( 'ts_address2', $ts_address2 ),
                ],
                'ts_city'       => [
                    'label'       => 'city',
                    'placeholder' => 'your_city',
                    'icon'        => 'fa-map-marker',
                    'value'       => post( 'ts_city', $ts_city ),

                ],
                'ts_province'   => [
                    'label'       => 'state_province_region',
                    'placeholder' => 'state_province_region',
                    'icon'        => 'fa-map-marker',
                    'value'       => post( 'ts_province', $ts_province ),
                ],
                'ts_zip_code'   => [
                    'label'       => 'zip_postal_code',
                    'placeholder' => 'zip_postal_code',
                    'icon'        => 'fa-map-marker',
                    'value'       => post( 'ts_zip_code', $ts_zip_code ),
                ],
                'ts_country'    => [
                    'label' => 'country',
                    'icon'  => 'fa-globe',
                    'value' => post( 'ts_country', $ts_country ),
                ],
                'ts_note'       => [
                    'label' => 'special_requirements',
                    'icon'  => false,
                    'type'  => 'textarea',
                    'size'  => 12,
                    'value' => post( 'ts_note' ),
                    'attrs' => [
                        'rows' => 6
                    ]
                ]

            ];


            $checkout_form_fields = apply_filters( 'ts_booking_form_fields', $checkout_form_fields );

            return $checkout_form_fields;
        }

    }
    TSCart::init();
}





