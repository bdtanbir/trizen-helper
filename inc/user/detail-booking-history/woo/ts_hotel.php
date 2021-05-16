<?php
$woo_order_id = $order_data['order_item_id'];
$order = wc_get_order( $order_id );
$room_id = wc_get_order_item_meta( $woo_order_id, '_ts_room_id', true );
$date_format = getDateFormat();
$price_by_per_person = get_post_meta( $room_id, 'price_by_per_person', true );
?>
<div class="st_tab st_tab_order tabbable">
    <ul class="nav nav-tabs tab_order">
        <li class="active">
            <?php
            $post_type = get_post_type( $service_id );
            $obj = get_post_type_object( $post_type ); ?>
            <a data-toggle="tab" href="#tab-booking-detail" aria-expanded="true"> <?php echo sprintf(esc_html__("%s Details",'trizen-helper'),$obj->labels->singular_name) ?></a>
        </li>
        <li class="">
            <a data-toggle="tab" href="#tab-customer-detail" aria-expanded="false"> <?php esc_html_e("Customer Details",'trizen-helper') ?></a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent973">
        <div id="tab-booking-detail" class="tab-pane fade active in">
            <div class="info">
                <div class="row">
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Booking ID",'trizen-helper') ?>:  </strong>
                            #<?php echo esc_html($order_id) ?>
                        </div>
                    </div>
                    <?php
                    $payment_gateway =  wc_get_payment_gateway_by_order( $order_id );
                    if(isset($payment_gateway) && !empty($payment_gateway)){ ?>
                        <div class="col-md-6">
                            <div class="item_booking_detail">
                                <strong><?php esc_html_e("Payment Method: ",'trizen-helper') ?> </strong>
                                <?php echo esc_html($payment_gateway->get_title());?>
                            </div>
                        </div>
                    <?php  } ?>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Order Date",'trizen-helper') ?>:  </strong>
                            <?php echo esc_html(date_i18n($date_format, strtotime($order_data['created']))) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Booking Status",'trizen-helper') ?>:  </strong>
                            <?php
                            $data_status =  STUser_f::_get_all_order_statuses();
                            $status = $order_data['status'];
                            $status_string = $data_status[$status];
                            if( isset( $order_data['cancel_refund_status'] ) && $order_data['cancel_refund_status'] == 'pending'){
                                $status_string = __('Cancelling', 'trizen-helper');
                            }
                            ?>
                            <span class=""> <?php  echo esc_html($status_string); ?></span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Hotel Name",'trizen-helper') ?>:  </strong>
                            <a href="<?php echo get_the_permalink($service_id) ?>"><?php echo get_the_title($service_id) ?></a>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Room",'trizen-helper') ?>:  </strong>
                            <?php echo get_the_title($room_id) ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Address: ",'trizen-helper') ?>:  </strong>
                            <?php  echo get_post_meta( $service_id, 'address', true); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Check In:",'trizen-helper') ?> </strong>
                            <?php
                            $check_in = date( $date_format, $order_data['check_in_timestamp'] );
                            echo esc_html($check_in);
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6 ">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Check Out:",'trizen-helper') ?> </strong>
                            <?php
                            $check_out = date( $date_format, $order_data['check_out_timestamp'] );
                            echo esc_html($check_out);
                            ?>
                        </div>
                    </div>

                    <div class="line col-md-12"></div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Room Number:",'trizen-helper') ?> </strong>
                            <?php echo wc_get_order_item_meta( $woo_order_id, '_ts_room_num_search', true ); ?>
                        </div>
                    </div>
                    <?php
                    if ( $price_by_per_person != 'on' ) : ?>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php esc_html_e("Room Price:",'trizen-helper') ?> </strong>
                                <?php echo wc_price( wc_get_order_item_meta( $woo_order_id, '_ts_item_price' , true) ,array( 'currency' => $order->get_currency())) ?>
                            </div>
                        </div>
                    <?php
                    endif; ?>
                    <?php if(!empty($discount = wc_get_order_item_meta($woo_order_id , '_ts_discount_rate' , true))) {?>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php esc_html_e("Discount Rate:",'trizen-helper') ?> </strong>
                                <?php echo esc_html($discount); ?> %
                            </div>
                        </div>
                    <?php } ?>
                    <?php
                    $class_price_type = ($price_by_per_person == 'on') ? 'col-md-6' : 'col-md-12';
                    ?>
                    <div class="<?php echo esc_attr($class_price_type) ?>">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("No. Adults :",'trizen-helper') ?> </strong>
                            <?php echo wc_get_order_item_meta( $woo_order_id, '_ts_adult_number', true ); ?>
                        </div>
                    </div>
                    <?php
                    if ( $price_by_per_person == 'on' ) : ?>
                        <div class="col-md-6">
                            <div class="item_booking_detail">
                                <strong><?php esc_html_e("Adult Price :",'trizen-helper') ?> </strong>
                                <?php $adult_price =  wc_get_order_item_meta( $woo_order_id, '_ts_adult_price', true ); ?>
                                <?php echo wc_price($adult_price,array( 'currency' => $order->get_currency())) ?>
                            </div>
                        </div>
                    <?php
                    endif; ?>
                    <div class="<?php echo esc_attr($class_price_type) ?>">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("No. Children :",'trizen-helper') ?> </strong>
                            <?php echo wc_get_order_item_meta( $woo_order_id, '_ts_child_number', true ); ?>
                        </div>
                    </div>
                    <?php
                    if ( $price_by_per_person == 'on' ) : ?>
                        <div class="col-md-6">
                            <div class="item_booking_detail">
                                <strong><?php esc_html_e("Child Price :",'trizen-helper') ?> </strong>
                                <?php $child_price =  wc_get_order_item_meta( $woo_order_id, '_ts_child_price', true ); ?>
                                <?php echo wc_price($child_price,array( 'currency' => $order->get_currency())) ?>
                            </div>
                        </div>
                    <?php
                    endif; ?>
<!--                    --><?php //if(!empty(ts_print_order_item_guest_name(json_decode($order_data['raw_data'],true)))){?>
<!--                        <div class="col-md-12">-->
<!--                            <div class="item_booking_detail">-->
<!--                                --><?php //st_print_order_item_guest_name(json_decode($order_data['raw_data'],true)) ?>
<!--                            </div>-->
<!--                        </div>-->
<!--                        --><?php
//                    }
//                    $extra_price = wc_get_order_item_meta( $woo_order_id, '_ts_extra_price', true );
//                    $extras      = wc_get_order_item_meta( $woo_order_id, '_ts_extras', true );
//                    $data_extra = [];
//                    if ( isset( $extras[ 'value' ] ) && is_array( $extras[ 'value' ] ) && count( $extras[ 'value' ] ) ) {
//                        foreach ( $extras[ 'value' ] as $name => $number ) {
//                            if(!empty($extras[ 'value' ][ $name ])){
//                                $data_extra[ $name ] = array(
//                                    'title'=>$extras[ 'title' ][ $name ],
//                                    'price'=>$extras[ 'price' ][ $name ],
//                                    'value'=>$extras[ 'value' ][ $name ],
//                                );
//                            }
//                        }
//                    }
                    ?>
                    <div class="col-md-6 <?php if(empty($extra_price)) echo "hide"; ?>">
                        <div class="item_booking_detail">
                            <strong><?php esc_html_e("Extra Price:",'trizen-helper') ?> </strong>
                            <?php echo wc_price($extra_price,array( 'currency' => $order->get_currency())) ?>
                            <?php if ( is_array( $data_extra ) && count( $extras ) ){ ?>
                                <table class="table mt10 mb10" style="table-layout: fixed;" width="200">
                                    <tr>
                                        <td>
                                            <label>
                                                <strong><?php esc_html_e("Name Extra",'trizen-helper') ?></strong>
                                            </label>
                                        </td>
                                        <td width="40%">
                                            <strong><?php esc_html_e("Price",'trizen-helper') ?></strong>
                                        </td>
                                    </tr>
                                    <?php foreach ( $data_extra as $key => $val ):
                                        $price = $val[ 'value' ] * $val[ 'price' ];
                                        ?>
                                        <tr>
                                            <td>
                                                <label>
                                                    <?php echo esc_html($val[ 'title' ]); ?>
                                                </label>
                                            </td>
                                            <td width="40%">
                                                <?php echo wc_price($price,array( 'currency' => $order->get_currency())) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php }else{ echo 0 ;} ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div id="tab-customer-detail" class="tab-pane fade">
            <div class="container-customer">
                <div class="info">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('First name ' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_first_name', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Last name ' , ST_TEXTDOMAIN) ; ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_last_name', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Email ' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_email', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Phone ' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_phone', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Address Line 1' , ST_TEXTDOMAIN ) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_address_1', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Address Line 2' , ST_TEXTDOMAIN ) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_address_2', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('City' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_city', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('State/Province/Region' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_state', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('ZIP code/Postal code' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_postcode', true) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong><?php echo __('Country' , ST_TEXTDOMAIN) ;  ?></strong>:
                                <?php echo get_post_meta($order_id, '_billing_country', true) ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?php do_action("ts_after_body_order_information_table",$order_data['order_item_id']); ?>
    <button data-dismiss="modal" class="btn btn-default" type="button"><?php esc_html_e("Close",'trizen-helper') ?></button>
</div>
