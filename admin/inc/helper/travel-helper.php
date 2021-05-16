<?php
global $st_all_table_loaded;
$st_all_table_loaded = [];
if ( !class_exists( 'TravelHelper' ) ) {

    class TravelHelper
    {


        static function init()
        {

            add_action( 'init', [ __CLASS__, 'change_current_currency' ] );
        }

        static function change_current_currency( $currency_name = false ){
            if ( !isset( $_SESSION[ 'change_currencyds' ] ) ) {
                $_SESSION[ 'change_currencyds' ] = '';
            }

            if ( isset( $_GET[ 'currency' ] ) and $_GET[ 'currency' ] and $new_currency = TSAdminRoom::find_currency( $_GET[ 'currency' ] ) ) {
                $_SESSION[ 'currency' ]          = $new_currency;
                $_SESSION[ 'change_currencyds' ] = 'ok';
            }

            if ( $currency_name and $new_currency = TSAdminRoom::find_currency( $currency_name ) ) {
                $_SESSION[ 'currency' ] = $new_currency;
            }
        }

    }
}
