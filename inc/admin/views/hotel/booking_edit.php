<?php/** * @package WordPress * @subpackage Trizen * @since 1.0 * * Admin hotel booking edit * * Created by ShineTheme */$item_id       = isset($_GET['order_item_id']) ? $_GET['order_item_id'] : false;$order_item_id = get_post_meta($item_id,'item_id',true);$section       = isset($_GET['section']) ? $_GET['section'] : false;if(!isset($page_title)) {    $page_title = esc_html__('Edit Hotel Booking','trizen-helper');}$currency = get_post_meta($item_id, 'currency', true);?><div class="wrap">     <h2><?php echo esc_html($page_title); ?></h2>    <?php message() ?>    <div id="post-body" class="columns-2">        <div id="post-body-content">            <div class="postbox-container">                <form method="post" action="" id="form-booking-admin">                    <?php wp_nonce_field('shb_action','shb_field') ?>                    <div id="poststuff">                        <div class="postbox">                            <div class="handlediv" title="<?php esc_attr_e('Click to toggle','trizen-helper'); ?>"><br></div>                            <h3 class="hndle ui-sortable-handle">                                <span><?php esc_html_e('Order Information','trizen-helper'); ?></span>                            </h3>                            <div class="inside">                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Booker ID','trizen-helper'); ?><span class="require"><?php esc_attr_e(' (*)', 'trizen-helper'); ?></span>                                    </label>                                    <div class="controls">                                        <?php                                        $id_user = '';                                        $pl_name = '';                                        if($item_id){                                            $id_user = get_post_meta($item_id,'id_user',true);                                            if($id_user){                                                $user = get_userdata($id_user);                                                if($user){                                                    $pl_name = $user->ID.' - '.$user->user_email;                                                }                                            }                                        }                                        ?>                                        <input readonly type="text" name="id_user" value="<?php echo esc_attr($pl_name); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <?php ob_start(); ?>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer First Name','trizen-helper'); ?><span class="require"><?php esc_html_e(' (*)', 'trizen-helper'); ?></span>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_first_name = isset($_POST['ts_first_name']) ? $_POST['ts_first_name'] : get_post_meta($item_id,'ts_first_name',true);                                        ?>                                        <input type="text" name="ts_first_name" value="<?php echo esc_attr($ts_first_name); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer Last Name','trizen-helper'); ?><span class="require"><?php esc_html_e(' (*)', 'trizen-helper'); ?></span>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_last_name = isset($_POST['ts_last_name']) ? $_POST['ts_last_name'] : get_post_meta($item_id,'ts_last_name',true);                                        ?>                                        <input type="text" name="ts_last_name" value="<?php echo esc_attr($ts_last_name); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer Email', 'trizen-helper'); ?><span class="require"><?php esc_html_e(' (*)', 'trizen-helper'); ?></span>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_email = isset($_POST['ts_email']) ? $_POST['ts_email'] : get_post_meta($item_id,'ts_email',true);                                        ?>                                        <input type="text" name="ts_email" value="<?php echo esc_attr($ts_email); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer Phone','trizen-helper'); ?><span class="require"><?php esc_html_e(' (*)', 'trizen-helper'); ?></span>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_phone = isset($_POST['ts_phone']) ? $_POST['ts_phone'] : get_post_meta($item_id,'ts_phone',true);                                        ?>                                        <input type="text" name="ts_phone" value="<?php echo esc_attr($ts_phone); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer Address line 1','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_address = isset($_POST['ts_address']) ? $_POST['ts_address'] : get_post_meta($item_id,'ts_address',true);                                        ?>                                        <input type="text" name="ts_address" value="<?php echo esc_attr($ts_address); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer Address line 2','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_address2 = isset($_POST['ts_address2']) ? $_POST['ts_address2'] : get_post_meta($item_id,'ts_address2',true);                                        ?>                                        <input type="text" name="ts_address2" value="<?php echo esc_attr($ts_address2); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Customer City','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_city = isset($_POST['ts_city']) ? $_POST['ts_city'] : get_post_meta($item_id,'ts_city',true);                                        ?>                                        <input type="text" name="ts_city" value="<?php echo esc_attr($ts_city); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('State/Province/Region','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_province = isset($_POST['ts_province']) ? $_POST['ts_province'] : get_post_meta($item_id,'ts_province',true);                                        ?>                                        <input type="text" name="ts_province" value="<?php echo esc_attr($ts_province); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('ZIP code/Postal code','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $ts_zip_code = isset($_POST['ts_zip_code']) ? $_POST['ts_zip_code'] : get_post_meta($item_id,'ts_zip_code',true);                                        ?>                                        <input type="text" name="ts_zip_code" value="<?php echo esc_attr($ts_zip_code); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Country','trizen-helper'); ?></label>                                    <div class="controls">                                        <?php                                        $ts_country = isset($_POST['ts_country']) ? $_POST['ts_country'] : get_post_meta($item_id,'ts_country',true);                                        ?>                                        <input type="text" name="ts_country" value="<?php echo esc_attr($ts_country); ?>" class="form-control form-control-admin">                                    </div>                                </div>                                <?php                                $custommer = @ob_get_clean();                                echo apply_filters( 'st_customer_infomation_edit_order', $custommer,$item_id );                                ?>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Hotel','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php $hotel_id = intval(get_post_meta($item_id,'item_id',true)); ?>                                        <strong><?php echo get_the_title($hotel_id); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Room','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php $room_id = intval(get_post_meta($item_id, 'room_id', true)); ?>                                        <strong><?php echo get_the_title($room_id); ?></strong>                                    </div>                                </div>                                <?php                                if ( get_post_meta( $room_id, 'price_by_per_person', true ) == 'on' ) : ?>                                    <div class="form-row">                                        <label class="form-label" for="">                                            <?php esc_html_e('Adult Price','trizen-helper'); ?>                                        </label>                                        <div class="controls">                                            <?php                                            $item_price = floatval(get_post_meta($item_id, 'adult_price',true));                                            $currency = get_post_meta($item_id, 'currency', true);                                            ?>                                            <strong><?php echo TravelHelper::format_money_from_db($item_price, $currency); ?></strong>                                        </div>                                    </div>                                    <div class="form-row">                                        <label class="form-label" for="">                                            <?php esc_html_e('Child Price','trizen-helper'); ?>                                        </label>                                        <div class="controls">                                            <?php                                            $item_price = floatval(get_post_meta($item_id,'child_price',true));                                            $currency   = get_post_meta($item_id, 'currency', true);                                            ?>                                            <strong><?php echo TravelHelper::format_money_from_db($item_price, $currency); ?></strong>                                        </div>                                    </div>                                <?php                                else : ?>                                    <div class="form-row">                                        <label class="form-label" for="">                                            <?php esc_html_e('Price','trizen-helper'); ?>                                        </label>                                        <div class="controls">                                            <?php                                            $item_price = floatval(get_post_meta($item_id,'item_price',true));                                            $currency   = get_post_meta($item_id, 'currency', true);                                            ?>                                            <strong><?php echo TravelHelper::format_money_from_db($item_price, $currency); ?></strong>                                        </div>                                    </div>                                <?php                                endif; ?>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('No. Adults','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php $adult_number = get_post_meta($item_id,'adult_number',true);?>                                        <strong><?php echo esc_html($adult_number); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('No. Children','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php $child_number = get_post_meta($item_id,'child_number',true); ?>                                        <strong><?php echo esc_html($child_number); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php esc_html_e('Number Room','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $item_number = get_post_meta($item_id,'item_number',true);                                        ?>                                        <strong><?php echo esc_html($item_number); ?></strong>                                    </div>                                </div>                                <?php ts_admin_print_order_item_guest_name([                                    'guest_name'=>get_post_meta($item_id,'guest_name',true),                                    'guest_title'=>get_post_meta($item_id,'guest_title',true),                                ]); ?>                                <div class="form-row">                                    <label class="form-label" for="check_in">                                        <?php esc_html_e('Check in','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $check_in = get_post_meta($item_id,'check_in',true);                                        if(!empty($check_in)){                                            $check_in = date('m/d/Y',strtotime($check_in));                                        }                                        ?>                                        <strong><?php echo esc_html($check_in); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="check_out">                                        <?php esc_html_e('Check out','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $check_out = isset($_POST['check_out']) ? $_POST['check_out'] : get_post_meta($item_id,'check_out',true);                                        if(!empty($check_out)){                                            $check_out = date('m/d/Y',strtotime($check_out));                                        }                                        ?>                                        <strong><?php echo esc_html($check_out); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="extra">                                        <?php esc_html_e('Extra','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $extra_price = get_post_meta($room_id, 'extra_price', true);                                        $extras      = get_post_meta($item_id, 'extras', true);                                        $data_item   = array(); $data_number = array();                                        if(isset($extras['value']) && is_array($extras['value']) && count($extras['value'])){                                            foreach($extras['value'] as $name => $number){                                                $data_item[] = $name;                                                $data_number[$name] = $extras['value'][$name];                                            }                                        }                                        ?>                                        <?php if(is_array($extra_price) && count($extra_price)): ?>                                            <table class="table" style="table-layout: fixed;" width="200">                                                <?php foreach($extra_price as $key => $val): ?>                                                    <tr>                                                        <td width="80%">                                                            <label for="<?php echo esc_attr($val['extra_name']); ?>" class="ml20">                                                                <strong><?php echo esc_html($val['title']); ?></strong></label>                                                        </td>                                                        <td width="20%">                                                            <strong><?php echo esc_attr($data_number[$val['extra_name']]); ?></strong>                                                        </td>                                                    </tr>                                                <?php endforeach; ?>                                            </table>                                        <?php endif; ?>                                        <div id="extra-price-wrapper">                                       </div>                                        <span class="spinner extra_price"></span>                                    </div>                                </div>                                <?php                                $st_note = get_post_meta( $item_id, 'ts_note', true );                                if(!empty($st_note)){                                    ?>                                    <div class="form-row">                                       <label class="form-label"                                               for="st_note"><?php _e( 'Special Requirements', 'trizen-helper'); ?></label>                                        <div class="controls">                                            <?php echo esc_html( $st_note ); ?>                                        </div>                                    </div>                                <?php } ?>                                <?php                                if(!empty($booking_fee_price = get_post_meta($item_id, 'booking_fee_price', true))){                                    ?>\                                    <div class="form-row">                                        <label class="form-label" for="">                                            <?php get_post_meta( 'Fee', 'trizen-helper'); ?></label>                                        <div class="controls">                                            <strong><?php echo TravelHelper::format_money_from_db($booking_fee_price ,$currency); ?></strong>                                        </div>                                    </div>                                <?php } ?>                                <div class="form-row">                                    <label class="form-label" for="">                                        <?php get_post_meta('Total','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <?php                                        $data_prices = ( get_post_meta( $item_id, 'data_prices', true ) );                                        ?>                                        <strong><?php echo TravelHelper::format_money_from_db( $data_prices['price_with_tax'], $currency ); ?></strong>                                    </div>                                </div>                                <div class="form-row">                                    <label class="form-label" for="status">                                        <?php esc_html_e('Status','trizen-helper'); ?>                                    </label>                                    <div class="controls">                                        <select data-block="" class="" name="status">                                            <?php $status=get_post_meta($item_id,'status',true); ?>                                            <option value="pending" <?php selected($status,'pending'); ?> >                                                <?php esc_html_e('Pending','trizen-helper'); ?>                                            </option>                                            <option value="incomplete" <?php selected($status,'incomplete'); ?> >                                                <?php esc_html_e('Incomplete','trizen-helper'); ?>                                            </option>                                            <option value="complete" <?php selected($status,'complete'); ?> >                                                <?php esc_html_e('Complete','trizen-helper'); ?>                                            </option>                                            <option value="canceled" <?php selected($status,'canceled'); ?> >                                                <?php esc_html_e('Canceled','trizen-helper'); ?>                                            </option>                                        </select>                                    </div>                                </div>                                <div class="form-row">                                    <div class="controls">                                        <input type="submit" name="submit" value="<?php esc_attr_e('Save','trizen-helper'); ?>" class="button button-primary ">                                    </div>                                </div>                            </div>                        </div>                    </div>                </form>            </div>        </div>    </div></div>