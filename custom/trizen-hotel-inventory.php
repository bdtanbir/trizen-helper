<?php
global $post;
$post_id = $post->ID;

if(empty($post_id)){
	return;
}
$args = [
	'post_type'      => 'hotel_room',
	'posts_per_page' => -1,
	'meta_query'     => [
		[
			'key'     => 'trizen_hotel_room_select',
			'value'   => $post_id,
			'compare' => '='
		]
	]
];
$rooms = [];
$query = new WP_Query($args);
while ($query->have_posts()) : $query->the_post();
$rooms[] = [
	'id'    => get_the_ID(),
	'name'  => get_the_title(),
];
endwhile; wp_reset_postdata();
wp_enqueue_script('bulk-calendar');
wp_enqueue_script('trizen-hotel-inventory');

?>
<div class="calendar-wrapper-inventory">
	<div class="form-settings">
		<h1 class="title">
			Inventory
		</h1>
		<div class="ts-inventory-form">
			<div class="left">
                <span class="label">
				<strong>View by period:</strong>
                </span>
                <input
                        type="text"
                        name="ts-inventory-start"
                        class="ts-inventory-start disabled"
                        value=""
                        autocomplete="off"
                        placeholder="Start Date" />
                <input
                        type="text"
                        name="ts-inventory-end"
                        class="ts-inventory-end disabled"
                        value=""
                        autocomplete="off"
                        placeholder="End date" />
                <button class="st-inventory-goto trizen-btn">
                    View
                </button>
            </div>
			<button
				type="button"
				id="calendar-bulk-edit"
				class="option-tree-ui-button button trizen-btn">
				Bulk Edit
			</button>
		</div>
		<div class="gantt wpbooking-gantt st-inventory" data-id="<?php echo esc_attr($post_id); ?>" data-rooms="<?php echo esc_attr(json_encode($rooms)); ?>"></div>
		<div class="ts-inventory-color">
			<div class="inventory-color-item">
				<span class="available"></span> Available
			</div>
			<div class="inventory-color-item">
				<span class="unavailable"></span> Unavailable
			</div>
			<div class="inventory-color-item">
				<span class="out_stock"></span> Out of Stock
			</div>
		</div>

        <input type="hidden" value="Edit number of room" id="inventory-text-edit-room" />

        <div class="panel-room-number-wrapper">
            <div class="panel-room">
                <input type="number" name="input-room-number" class="input-price" value="" placeholder="" />
                <input type="hidden" name="input-room-id" class="input-room-id" value="" placeholder="" min="0">
                <a href="javascript: void(0);" class="trizen-btn btn-add-number-room" style="margin-left: 10px;">
                    Update <i class="fas fa-spin fa-spinner loading-icon"></i>
                </a>
                <span class="close">
                    <i class="fas fa-times"></i>
                </span>
                <div class="message-box"></div>
            </div>
        </div>

        <!-- Bulk Edit -->
        <div id="form-bulk-edit" class="fixed">
            <div class="form-container">
                <div class="overlay">
                    <div class="spinner is-active"></div>
                </div>
                <div class="form-title form-bulk-header">
                    <h3 class="clearfix">
                        Select a Room
                        <select name="post-id" class="ml20 post-bulk">
                            <option value="">
                                --- Room ---
                            </option>
                            <?php foreach ($rooms as $room) {
                                echo '<option value="' . esc_attr($room['id']) . '">' . esc_html($room['name']) . '</option>';
                            } ?>
                        </select>
                        <button id="calendar-bulk-close" class="calendar-bulk-room-close trizen-btn" type="button">
                            Close
                        </button>
                    </h3>
                </div>
                <div class="form-content clearfix d-flex">
                    <div class="form-group">
                        <div class="form-title">
                            <h4>
                                <input type="checkbox" class="check-all" data-name="day-of-week" /> Days Of Week
                            </h4>
                        </div>
                        <div class="form-content">
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Sunday" /> Sunday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Monday" /> Monday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Tuesday" /> Tuesday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Wednesday" /> Wednesday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Thursday" /> Thursday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Friday" /> Friday
                            </label>
                            <label class="block">
                                <input type="checkbox" name="day-of-week[]" value="Saturday" /> Saturday
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-title">
                            <h4>
                                <input type="checkbox" class="check-all" data-name="day-of-month" /> Days Of Month
                            </h4>
                        </div>
                        <div class="form-content">
                            <?php for ($i = 1; $i <= 31; $i ++):
                                if($i == 1) {
                                    echo '<div>';
                                }
                                ?>
                                    <label style="width: 40px;">
                                        <input type="checkbox" name="day-of-month[]" value="<?php echo esc_attr($i); ?>" > <?php echo esc_html($i); ?>
                                    </label>
	                            <?php
	                            if( $i != 1 && $i % 5 == 0 ) echo '</div><div>';
	                            if( $i == 31 ) echo '</div>';
                            endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-title">
                            <h4>
                                <input type="checkbox" class="check-all" data-name="months" /> Months(*)
                            </h4>
                        </div>
                        <div class="form-content">
                            <?php
                                $months = array(
                                    'January'   => 'January',
                                    'February'  => 'February',
                                    'March'  => 'March',
                                    'April'  => 'April',
                                    'May'  => 'May',
                                    'June'  => 'June',
                                    'July'  => 'July',
                                    'August'  => 'August',
                                    'September'  => 'September',
                                    'October'  => 'October',
                                    'November'  => 'November',
                                    'December'  => 'December',
                                );
                                $i = 0;
                                foreach ( $months as $key => $month ) {
                                    if($i == 0) {
                                        echo '<div>';
                                    }
                                    ?>
                                        <label style="width: 100px;">
                                            <input type="checkbox" name="months[]" value="<?php echo esc_attr($key); ?>" style="margin-right: 5px;" /><?php echo esc_html($month); ?>
                                        </label>
                                    <?php
                                    if( $i != 0 && ($i + 1) % 2 == 0 ) echo '</div><div>';
                                    if( $i + 1 == count($months)) echo '</div>';
                                    $i++;
                                }
                            ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-title">
                            <h4>
                                <input type="checkbox" class="check-all" data-name="years" /> Years(*)
                            </h4>
                        </div>
                        <div class="form-content">
                            <?php
                                $year = date('Y');
                                $j = $year -1;
                                for ($i = $year; $i <= $year + 2; $i ++) {
                                    if( $i == $year ) {
                                        echo '<div>';
                                    }
                                    ?>
                                    <label style="width: 100px;">
                                        <input type="checkbox" name="years[]" value="<?php echo esc_attr($i); ?>" style="margin-right: 5px;" /><?php echo esc_html($i); ?>
                                    </label>
                                    <?php
                                    if( $i != $year && ($i == $j + 2 ) ) { echo '</div><div>'; $j = $i; }
                                    if( $i == $year + 2) echo '</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="clear"></div>

                <div class="form-content form-bulk-price-op flex clearfix">
                    <label class="block">
                        Price: <input type="text" value="" name="price-bulk" id="price-bulk" placeholder="Price">
                    </label>

                    <label class="block">
	                    <?php esc_html_e('Status:', 'trizen-helper'); ?>
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </label>
                    <input type="hidden" class="type-bulk" name="type-bulk" value="accommodation">
                    <span class="clear"></span>
                    <div class="form-message" style="margin-top: 20px;"></div>
                </div>

                <div class="form-footer">
                    <button id="calendar-bulk-save" class="trizen-btn" type="button">
                        Save
                    </button>
                </div>
            </div>
        </div>
	</div>
</div>




