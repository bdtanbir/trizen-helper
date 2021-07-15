<?php
/**

 * @package WordPress
 * @subpackage Trizen
 * @since 1.0
 * Admin hotel booking index
 * Created by TechyDevs
 *
 */

$page   = isset($_GET['paged']) ? $_GET['paged'] : 1;

$limit  = 20;
$offset = ($page-1)*$limit;
$data   = TSAdmin::get_history_bookings('ts_hotel', $offset, $limit);
$posts  = $data['rows'];

$total  = ceil($data['total'] / $limit);

global $wp_query;
$paging = array();
$paging['base']    = admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking%_%');
$paging['format']  = '&paged=%#%';
$paging['total']   = $total;
$paging['current'] = $page;
?>


<div class="wrap"><div id="icon-tools" class="icon32"></div>

    <h2><?php esc_html_e('Hotel Bookings', 'trizen-helper'); ?></h2>';
    <?php
    message();
    ?>

    <form id="posts-filter" action="<?php echo admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking')?>" method="get">
        <input type="hidden" name="post_type" value="ts_hotel">
        <input type="hidden" name="page" value="ts_hotel_booking">
        <div class="wp-filter st-wp-filter">
            <div class="filter-items">
                <div class="alignleft actions">

                    <input type="text" class="ts_custommer_name"   name="ts_custommer_name" placeholder="<?php esc_attr_e('Filter by customer name','trizen-helper');  ?>" value="<?php echo get('ts_custommer_name') ?>"/>

                    <input type="text" class="ts_datepicker" format="mm/dd/yyyy"  name="ts_date_start" placeholder="<?php esc_attr_e('Filter by Date from','trizen-helper');  ?>" value="<?php echo get('ts_date_start') ?>"/>

                    <input type="text" class="ts_datepicker" name="ts_date_end" placeholder="<?php esc_attr_e('Filter by Date to','trizen-helper'); ?>" value="<?php echo get('ts_date_end'); ?>"/>

                    <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter','trizen-helper'); ?>">

                </div>
            </div>
        </div>

    </form>

    <form id="posts-filter" action="<?php echo admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking')?>" method="post">

        <?php wp_nonce_field('shb_action','shb_field')?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">

                <label for="bulk-action-selector-top" class="screen-reader-text">
                    <?php esc_html_e('Select bulk action','trizen-helper'); ?>
                </label>

                <select name="st_action" id="bulk-action-selector-top">

                    <option value="-1" selected="selected">
                        <?php esc_html_e('Bulk Actions','trizen-helper'); ?>
                    </option>
                    <option value="delete">
                        <?php esc_html_e('Delete Permanently','trizen-helper'); ?>
                    </option>

                </select>
                <input type="submit" name="" id="doaction" class="button action" value="<?php esc_attr_e('Apply','trizen-helper'); ?>">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php echo sprintf(_n('%s item','%s items',$data['total']),$data['total'], 'trizen-helper');  ?>
                </span>
                <?php echo paginate_links($paging)?>
            </div>
        </div>

        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">
                            <?php esc_html_e('Select All','trizen-helper'); ?></label>
                        <input type="checkbox" id="cb-select-all-1">
                    </th>

                    <th class="manage-column">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Customer','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column" width="10%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Check In','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column" width="10%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Check Out','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Hotel - Room Name','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column " width="7%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Room(s)','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column" width="7%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Price','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column"  width="10%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Created Date','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column " width="10%">
                        <a href="#">
                            <span>
                                <?php esc_html_e('Status','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th class="manage-column " width="10%">
                        <a href="#">
                            <span>
                                <?php _e('Payment Method','trizen-helper'); ?>
                            </span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                </tr>
            </thead>
            <tbody>

            <?php
            $i = 0;
            if( !empty( $posts ) ) {
                foreach( $posts as $key => $value ) {
                    $i++;
                    $post_id = $value->ID;
                    $item_id = get_post_meta($post_id, 'item_id', true);

                    ?>
                    <tr class="<?php if ($i % 2 == 0) esc_html_e('alternate', 'trizen-helper'); ?>">
                        <th scope="row" class="check-column">
                            <input id="cb-select-39" type="checkbox" name="post[]" value="<?php echo esc_attr( $post_id)?>">
                            <div class="locked-indicator"></div>
                        </th>

                        <td class="post-title page-title column-title">
                            <strong>
                                <a class="row-title"
                                       href="<?php echo admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking&section=edit_order_item&order_item_id=' . $post_id); ?>"
                                       title="">
                                    <?php
                                    if ($post_id) {
                                        $name = get_post_meta($post_id, 'ts_first_name', true);
                                        if (!$name) {
                                            $name = get_post_meta($post_id, 'ts_name', true);
                                        }
                                        if (!$name) {
                                            $name = get_post_meta($post_id, 'ts_email', true);
                                        }
                                        echo esc_html( $name);
                                    }
                                    ?>
                                </a>
                            </strong>

                            <div class="row-actions">
                                <a href="<?php echo admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking&section=edit_order_item&order_item_id=' . $post_id); ?>">
                                    <?php esc_html_e('Edit','trizen-helper'); ?>
                                </a> <?php esc_html_e('|', 'trizen-helper'); ?>
                                <a href="<?php echo admin_url('edit.php?post_type=ts_hotel&page=ts_hotel_booking&section=resend_email&order_item_id=' . $post_id); ?>">
                                    <?php esc_html_e('Resend Email','trizen-helper'); ?>
                                </a>
                                <?php do_action('ts_after_order_page_admin_information_table',$post_id) ?>
                            </div>

                        </td>

                        <td class="post-title page-title column-title">
                            <?php $date = get_post_meta($post_id, 'check_in', true); if($date) echo date('m/d/Y',strtotime($date)); ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php $date= get_post_meta($post_id, 'check_out', true); if($date) echo date('m/d/Y',strtotime($date)); ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php
                            if ($item_id) {
                                if ($item_id) {
                                    echo "<a href='" . get_edit_post_link($item_id) . "' target='_blank'>" . get_the_title($item_id) . "</a>";
                                }
                                $room_id = get_post_meta($post_id,'room_id',true);
                                if($room_id){
                                    echo " - <a href='" . get_edit_post_link($room_id) . "' target='_blank'>" . get_the_title($room_id) . "</a>";
                                }
                            }
                            ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php echo get_post_meta($post_id, 'item_number', true) ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php
                            $price = get_post_meta($post_id,'total_price',true);
                            $currency = _get_currency_book_history($post_id);
                            echo format_money_raw($price, $currency);
                            ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php echo date(get_option('date_format'),strtotime($value->post_date)) ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php echo get_post_meta($post_id, 'status', true) ?>
                        </td>

                        <td class="post-title page-title column-title">
                            <?php
//                            echo STPaymentGateways::get_gatewayname(get_post_meta($post_id,'payment_method',true));
//                            do_action('st_traveler_after_name_payment_method',$post_id);
                            esc_html_e('Payment Gate Ways', 'trizen-helper');
                            ?>
                        </td>

                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php echo sprintf(_n('%s item','%s items',$data['total']),$data['total'], 'trizen-helper');  ?>
                </span>
                <?php echo paginate_links($paging)?>
            </div>
        </div>

        <?php wp_reset_query();?>

    </form>

</div>



