<?php




if (!function_exists('ts_convert_destination_car_transfer')) {

    function ts_convert_destination_car_transfer() {
        $return = [];
        $return[] = [
            'label' => __('---- Select ----', 'trizen-helper'),
            'value' => ''
        ];
        $list_location = [];
        $locations = TravelHelper::treeLocationHtml();
        if (!empty($locations)) {
            foreach ($locations as $k => $v) {
                $list_location[] = [
                    'id'      => $v['ID'],
                    'name'    => $v['post_title'],
                    'address' => '',
                    'type'    => 'location',
                    'level'   => $v['level'] / 20
                ];
            }
        }
        foreach ($list_location as $location) {
            $char = '';
            if ($location['level'] > 1) {
                for ($i = 0; $i < $location['level']; $i++) {
                    $char .= '-';
                }
            }
            $return[] = [
                'label' => $char . $location['name'],
                'value' => $location['id']
            ];
        }
        $transfers = TravelHelper::transferDestination();
        foreach ($transfers as $transfer) {
            $name = ($transfer['type'] == 'hotel') ? __('Hotel: ', 'trizen-helper') : __('Airport: ', 'trizen-helper');
            $return[] = [
                'label' => $name . $transfer['name'],
                'value' => $transfer['id']
            ];
        }

        $arr = array();
        //$transfers = TravelHelper::transferDestination();

        if (!empty($return)) {
            foreach ($return as $k => $v) {
                $arr[$v['value']] = ucfirst($v['label']);
            }
        }

        return $arr;
    }

}

