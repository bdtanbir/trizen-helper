<?php
global $st_all_table_loaded;
$st_all_table_loaded = [];
if ( !class_exists( 'TravelHelper' ) ) {

    class TravelHelper
    {
        public $plugin_name = "";
        public static $ts_location = [];
        protected static $listFullNameLocations = [];
        protected static $_cachedAlCurrency = [];
        private static $_booking_primary_currency;
        static $booking_type = array();
        static $_is_inited = false;
        private static $_check_table_duplicate = [];
        protected $order_id=false;


        static function init()
        {

            add_action( 'init', [ __CLASS__, 'change_current_currency' ] );
        }

        static function change_current_currency( $currency_name = false ){
            if ( !isset( $_SESSION[ 'change_currencyds' ] ) ) {
                $_SESSION[ 'change_currencyds' ] = '';
            }

            if ( isset( $_GET[ 'currency' ] ) and $_GET[ 'currency' ] and $new_currency = TravelHelper::find_currency( $_GET[ 'currency' ] ) ) {
                $_SESSION[ 'currency' ]          = $new_currency;
                $_SESSION[ 'change_currencyds' ] = 'ok';
            }

            if ( $currency_name and $new_currency = TravelHelper::find_currency( $currency_name ) ) {
                $_SESSION[ 'currency' ] = $new_currency;
            }
        }

        static function convert_money_to_default($money = false) {
            if (!is_numeric($money))
                $money = 0;
            if (!$money)
                $money = 0;
            $current_rate = self::get_current_currency('rate');
            $current      = self::get_current_currency('name');
            $default      = self::get_default_currency('name');
            if ($current != $default)
                return $money / $current_rate;
            return $money;
        }

        static function deleteDuplicateData( $post_id, $table ) {
            global $wpdb;
            $sql = "DELETE FROM {$table} WHERE post_id = '{$post_id}'";
            $rs = $wpdb->query( $sql );
            return $rs;
        }

        static function getListFullNameLocation( $post_type = '' ) {
            if ( array_key_exists( 'full_name_' . $post_type, self::$listFullNameLocations ) ) return self::$listFullNameLocations[ 'full_name_' . $post_type ];
            global $wpdb;
            $language = "'" . 'en' . "'";
            if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                $language = "'" . ICL_LANGUAGE_CODE . "'";
            }
            $where = '';
            if ( !empty( $post_type ) ) {
                $where = " AND (node.location_id IN (SELECT
                    location_from
                FROM
                    {$wpdb->prefix}ts_location_relationships
                WHERE
                    post_type = '{$post_type}'
                GROUP BY
                    location_from) OR node.location_id IN (SELECT
                    location_to
                FROM
                    {$wpdb->prefix}ts_location_relationships
                WHERE
                    post_type = '{$post_type}'
                GROUP BY
                    location_to)) ";
            }
            $sql = "SELECT
                node.id as post_id,
                node.location_id AS ID,
                node.`name` AS post_title,
                node.location_country AS Country,
                node.fullname,
                node.left_key,
                node.right_key,
                node.parent_id,
                (COUNT(parent.fullname) - 1) AS lv
            FROM
                {$wpdb->prefix}ts_location_nested AS node,
                {$wpdb->prefix}ts_location_nested AS parent
            WHERE
                node.id <> 1 and node.`language` = {$language} AND
                node.left_key BETWEEN parent.left_key
            AND parent.right_key
            {$where}
            AND node.`status` IN ('publish', 'private')
            GROUP BY
                node.fullname
            ORDER BY
                node.left_key";
            $results                                                  = $wpdb->get_results( $sql );
            self::$listFullNameLocations[ 'full_name_' . $post_type ] = $results;
            return $results;
        }

        static function booking_post_type() {
            return self::$booking_type;
        }

        static function booking_type() {
            return self::$booking_type;
        }

        static function recursiveKeySort( &$by_ref_array ) {
            ksort( $by_ref_array );
            foreach ( $by_ref_array as $key => $value ) {
                if ( isset( $value[ 'children' ] ) ) {
                    self::recursiveKeySort( $value[ 'children' ] );
                    $by_ref_array[ $key ][ 'children' ] = $value[ 'children' ];
                }
                $by_ref_array[ $key ] = $value;
            }

            return $by_ref_array;
        }
        static function buildTree( array &$elements, $parentId = 1 ){
            $branch = [];
            foreach ( $elements as $element ) {
                if ( $element[ 'parent_id' ] == $parentId ) {
                    $children = self::buildTree( $elements, $element[ 'post_id' ] );
                    if ( $children ) {
                        $element[ 'children' ] = $children;
                    }
                    $branch[ $element[ 'post_title' ] ] = $element;
                    unset( $elements[ $element[ 'post_id' ] ] );
                }
            }
            return $branch;
        }
        static function buildTreeHasSort( $lists ) {
            $arr = [];
            if ( !empty( $lists ) ) {
                $lists_temp = $lists;
                unset( $lists );
                foreach ( $lists_temp as $k => $v ) {
                    $lists[ $v->post_title ] = (array)$v;
                }
                $lists = self::buildTree( $lists );
                self::recursiveKeySort( $lists );

                return $lists;
            } else {
                return $arr;
            }
        }

        /**
         * @since   1.0
         **/
        static function treeLocationHtml( $post_type = '' ){
            $lists = self::getListFullNameLocation( $post_type );
            $ns    = new Nested_set();
            global $wpdb;
            $ns->setControlParams( $wpdb->prefix . 'ts_location_nested' );
            if ( empty( $lists ) ) {
                return '';
            }
            $tree = [];
            foreach ( $lists as $key => $location ) {
                // $parent_name = self::getFirstParent( array('left_key' => $location->left_key, 'right_key' => $location->right_key) );
                $tree[] = [
                    'ID'          => (int)$location->ID,
                    'post_title'  => $location->post_title,
                    'fullname'    => $location->fullname,
                    'level'       => (int)$ns->getNodeLevel( [ 'left_key' => $location->left_key, 'right_key' => $location->right_key ] ) * 20,
                    'parent_name' => $location->post_title, /*(empty( $parent_name ) )? strtolower($location->post_title) : strtolower($parent_name)*/
                    'post_id'     => $location->post_id,
                    'parent_id'   => $location->parent_id,
                ];
            }
            return $tree;
        }

        static function woocommerce_default_currency_smbl() {
            $currency = get_woocommerce_currency_symbol();
            return $currency;
        }

        static function buildTreeOptionLocation($locations, $location_id) {
            if (is_array($locations) && count($locations)):
                foreach ($locations as $key => $value):
                    $level = 20;
                    if ($value['lv'] == 2) {
                        $level = $value['lv'] * 10;
                    }
                    if ($value['lv'] > 2) {
                        $level = $value['lv'] * 10 + (($value['lv'] - 2) * 10);
                    }
                    $class_f = '';
                    if ($value['lv'] == 1)
                        $class_f = 'parent_li';
                    ?>
                    <li style="padding-left: <?php echo esc_attr($level) . 'px;'; ?>" <?php selected($value['ID'], $location_id); ?>
                        data-country="<?php echo esc_attr($value['Country']); ?>"
                        data-value="<?php echo esc_attr($value['ID']); ?>" class="item <?php echo esc_attr($class_f); ?>">
                        <?php
                        if ($value['lv'] == 2) {
                            echo '<i class="la la-map-marker"></i>';
                            echo '<span class="lv2">' . esc_html($value['post_title']) . '</span>';
                        } else {
                            if ($value['lv'] == 1) {
                                echo '<span class="parent">' . esc_html($value['post_title']) . '</span>';
                            } else {
                                echo '<span class="child">' . esc_html($value['post_title']) . '</span>';
                            }
                        }
                        ?>
                    </li>
                    <?php
                    if (isset($value['children'])) {
                        if (is_array($value['children'])) {
                            self::buildTreeOptionLocation($value['children'], $location_id);
                        }
                    }
                endforeach;
            endif;
        }

        static function primary_lang(){
            $lang = '';
            if (self::is_wpml()) {
                global $sitepress;
                $lang = $sitepress->get_default_language();
            }
            return $lang;
        }

        static function post_translated($post_id, $post_type = 'post', $lang = '') {
            if (TravelHelper::is_wpml() && has_filter('wpml_object_id')) {
                if (empty($lang)) {
                    $lang = TravelHelper::current_lang();
                }
                return apply_filters('wpml_object_id', $post_id, $post_type, true, $lang);
            } else {
                return $post_id;
            }
        }

        static function edit_join_wpml( $join, $post_type ) {
            if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                global $wpdb;
                $and = "";
                if ( is_array( $post_type ) ) {
                    foreach ( $post_type as $k => $v ) {
                        $and .= "t.element_type = 'post_{$v}' OR ";
                    }
                    $and = substr( $and, 0, -3 );
                } else {
                    $and = "t.element_type = 'post_{$post_type}'";
                }

                $join .= "
                join {$wpdb->prefix}icl_translations as  t ON {$wpdb->posts}.ID = t.element_id AND {$and}
                JOIN {$wpdb->prefix}icl_languages as  l ON t.language_code = l. CODE AND l.active = 1 ";
            }

            return $join;
        }

        static function edit_where_wpml($where, $post_type = null) {
            if (defined('ICL_LANGUAGE_CODE')) {
                global $wpdb;
                $current_language = TravelHelper::current_lang();
                $where .= " AND t.language_code = '{$current_language}' ";
            }

            return $where;
        }
        static function is_wpml(){
            if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                return true;
            }
            return false;
        }

        static function current_lang(){
            $lang = '';
            if ( self::is_wpml() ) {
                global $sitepress;
                $lang = $sitepress->get_current_language();
            }

            return $lang;
        }


        // from 1.0
        static function get_all_post_type(){
            $post_type = [];
            if ( ts_check_service_available( 'ts_hotel' ) ) {
                $post_type[] = "ts_hotel";
                $post_type[] = "hotel_room";
            }
            if ( ts_check_service_available( 'ts_tours' ) ) {
                $post_type[] = "ts_tours";
            }
            if ( ts_check_service_available( 'ts_rental' ) ) {
                $post_type[] = "ts_rental";
            }
            if ( ts_check_service_available( 'ts_cars' ) ) {
                $post_type[] = "ts_cars";
            }
            if ( ts_check_service_available( 'ts_activity' ) ) {
                $post_type[] = "ts_activity";
            }
            if ( ts_check_service_available( 'ts_flight' ) ) {
                $post_type[] = "ts_flight";
            }
            return $post_type;
        }


        /**
         * @since 1.0
         **/
        static function _get_location_country( $optiontree = true ) {
            $countries    = [
                ''    => '---- Select ----',
                'AF'  => 'Afghanistan',
                'AX'  => 'Aland Islands',
                'AL'  => 'Albania',
                'DZ'  => 'Algeria',
                'AS'  => 'American Samoa',
                'AD'  => 'Andorra',
                'AO'  => 'Angola',
                'AI'  => 'Anguilla',
                'AQ'  => 'Antarctica',
                'AG'  => 'Antigua And Barbuda',
                'AR'  => 'Argentina',
                'AM'  => 'Armenia',
                'AW'  => 'Aruba',
                'AU'  => 'Australia',
                'AT'  => 'Austria',
                'AZ'  => 'Azerbaijan',
                'BS'  => 'Bahamas',
                'BH'  => 'Bahrain',
                'BD'  => 'Bangladesh',
                'BB'  => 'Barbados',
                'BY'  => 'Belarus',
                'BE'  => 'Belgium',
                'BZ'  => 'Belize',
                'BJ'  => 'Benin',
                'BM'  => 'Bermuda',
                'BT'  => 'Bhutan',
                'BO'  => 'Bolivia',
                'BA'  => 'Bosnia And Herzegovina',
                'BW'  => 'Botswana',
                'BV'  => 'Bouvet Island',
                'BR'  => 'Brazil',
                'IO'  => 'British Indian Ocean Territory',
                'BN'  => 'Brunei Darussalam',
                'BG'  => 'Bulgaria',
                'BF'  => 'Burkina Faso',
                'BI'  => 'Burundi',
                'KH'  => 'Cambodia',
                'CM'  => 'Cameroon',
                'CA'  => 'Canada',
                'CV'  => 'Cape Verde',
                'KY'  => 'Cayman Islands',
                'CF'  => 'Central African Republic',
                'TD'  => 'Chad',
                'CL'  => 'Chile',
                'CN'  => 'China',
                'CX'  => 'Christmas Island',
                'CC'  => 'Cocos (Keeling) Islands',
                'CO'  => 'Colombia',
                'KM'  => 'Comoros',
                'CG'  => 'Congo',
                'CD'  => 'Congo, Democratic Republic',
                'CK'  => 'Cook Islands',
                'CR'  => 'Costa Rica',
                'CI'  => 'Cote D\'Ivoire',
                'HR'  => 'Croatia',
                'CU'  => 'Cuba',
                'CY'  => 'Cyprus',
                'CZ'  => 'Czech Republic',
                'DK'  => 'Denmark',
                'DJ'  => 'Djibouti',
                'DM'  => 'Dominica',
                'DO'  => 'Dominican Republic',
                'EC'  => 'Ecuador',
                'EG'  => 'Egypt',
                'SV'  => 'El Salvador',
                'GQ'  => 'Equatorial Guinea',
                'ER'  => 'Eritrea',
                'EE'  => 'Estonia',
                'ET'  => 'Ethiopia',
                'FK'  => 'Falkland Islands (Malvinas)',
                'FO'  => 'Faroe Islands',
                'FJ'  => 'Fiji',
                'FI'  => 'Finland',
                'FR'  => 'France',
                'GF'  => 'French Guiana',
                'PF'  => 'French Polynesia',
                'TF'  => 'French Southern Territories',
                'GA'  => 'Gabon',
                'GM'  => 'Gambia',
                'GE'  => 'Georgia',
                'DE'  => 'Germany',
                'GH'  => 'Ghana',
                'GI'  => 'Gibraltar',
                'GR'  => 'Greece',
                'GL'  => 'Greenland',
                'GD'  => 'Grenada',
                'GP'  => 'Guadeloupe',
                'GU'  => 'Guam',
                'GT'  => 'Guatemala',
                'GG'  => 'Guernsey',
                'GN'  => 'Guinea',
                'GW'  => 'Guinea-Bissau',
                'GY'  => 'Guyana',
                'HT'  => 'Haiti',
                'HM'  => 'Heard Island & Mcdonald Islands',
                'VA'  => 'Holy See (Vatican City State)',
                'HN'  => 'Honduras',
                'HK'  => 'Hong Kong',
                'HU'  => 'Hungary',
                'IS'  => 'Iceland',
                'IN'  => 'India',
                'ID'  => 'Indonesia',
                'IR'  => 'Iran, Islamic Republic Of',
                'IQ'  => 'Iraq',
                'IE'  => 'Ireland',
                'IM'  => 'Isle Of Man',
                'IL'  => 'Israel',
                'IT'  => 'Italy',
                'JM'  => 'Jamaica',
                'JP'  => 'Japan',
                'JE'  => 'Jersey',
                'JO'  => 'Jordan',
                'KZ'  => 'Kazakhstan',
                'KE'  => 'Kenya',
                'KI'  => 'Kiribati',
                'KR'  => 'Korea',
                'KW'  => 'Kuwait',
                'KG'  => 'Kyrgyzstan',
                'LA'  => 'Lao People\'s Democratic Republic',
                'LV'  => 'Latvia',
                'LB'  => 'Lebanon',
                'LS'  => 'Lesotho',
                'LR'  => 'Liberia',
                'LY'  => 'Libyan Arab Jamahiriya',
                'LI'  => 'Liechtenstein',
                'LT'  => 'Lithuania',
                'LU'  => 'Luxembourg',
                'MO'  => 'Macao',
                'MK'  => 'Macedonia',
                'MG'  => 'Madagascar',
                'MW'  => 'Malawi',
                'MY'  => 'Malaysia',
                'MV'  => 'Maldives',
                'ML'  => 'Mali',
                'MT'  => 'Malta',
                'MH'  => 'Marshall Islands',
                'MQ'  => 'Martinique',
                'MR'  => 'Mauritania',
                'MU'  => 'Mauritius',
                'YT'  => 'Mayotte',
                'MX'  => 'Mexico',
                'FM'  => 'Micronesia, Federated States Of',
                'MD'  => 'Moldova',
                'MC'  => 'Monaco',
                'MN'  => 'Mongolia',
                'ME'  => 'Montenegro',
                'MS'  => 'Montserrat',
                'MA'  => 'Morocco',
                'MZ'  => 'Mozambique',
                'MM'  => 'Myanmar',
                'NA'  => 'Namibia',
                'NR'  => 'Nauru',
                'NP'  => 'Nepal',
                'NL'  => 'Netherlands',
                'AN'  => 'Netherlands Antilles',
                'NC'  => 'New Caledonia',
                'NZ'  => 'New Zealand',
                'NI'  => 'Nicaragua',
                'NE'  => 'Niger',
                'NG'  => 'Nigeria',
                'NU'  => 'Niue',
                'NF'  => 'Norfolk Island',
                'MP'  => 'Northern Mariana Islands',
                'NO'  => 'Norway',
                'OM'  => 'Oman',
                'PK'  => 'Pakistan',
                'PW'  => 'Palau',
                'PS'  => 'Palestinian Territory, Occupied',
                'PA'  => 'Panama',
                'PG'  => 'Papua New Guinea',
                'PY'  => 'Paraguay',
                'PE'  => 'Peru',
                'PH'  => 'Philippines',
                'PN'  => 'Pitcairn',
                'PL'  => 'Poland',
                'PT'  => 'Portugal',
                'PR'  => 'Puerto Rico',
                'QA'  => 'Qatar',
                'RE'  => 'Reunion',
                'RO'  => 'Romania',
                'RU'  => 'Russian Federation',
                'RW'  => 'Rwanda',
                'BL'  => 'Saint Barthelemy',
                'SH'  => 'Saint Helena',
                'KN'  => 'Saint Kitts And Nevis',
                'LC'  => 'Saint Lucia',
                'MF'  => 'Saint Martin',
                'PM'  => 'Saint Pierre And Miquelon',
                'VC'  => 'Saint Vincent And Grenadines',
                'WS'  => 'Samoa',
                'SM'  => 'San Marino',
                'ST'  => 'Sao Tome And Principe',
                'SA'  => 'Saudi Arabia',
                'SN'  => 'Senegal',
                'RS'  => 'Serbia',
                'SC'  => 'Seychelles',
                'SL'  => 'Sierra Leone',
                'SG'  => 'Singapore',
                'SK'  => 'Slovakia',
                'SI'  => 'Slovenia',
                'SB'  => 'Solomon Islands',
                'SO'  => 'Somalia',
                'ZA'  => 'South Africa',
                'GS'  => 'South Georgia And Sandwich Isl.',
                'ES'  => 'Spain',
                'LK'  => 'Sri Lanka',
                'SD'  => 'Sudan',
                'SR'  => 'Suriname',
                'SJ'  => 'Svalbard And Jan Mayen',
                'SZ'  => 'Swaziland',
                'SE'  => 'Sweden',
                'CH'  => 'Switzerland',
                'SY'  => 'Syrian Arab Republic',
                'TW'  => 'Taiwan',
                'TJ'  => 'Tajikistan',
                'TZ'  => 'Tanzania',
                'TH'  => 'Thailand',
                'TL'  => 'Timor-Leste',
                'TG'  => 'Togo',
                'TK'  => 'Tokelau',
                'TO'  => 'Tonga',
                'TT'  => 'Trinidad And Tobago',
                'TN'  => 'Tunisia',
                'TR'  => 'Turkey',
                'TM'  => 'Turkmenistan',
                'TC'  => 'Turks And Caicos Islands',
                'TV'  => 'Tuvalu',
                'UG'  => 'Uganda',
                'UA'  => 'Ukraine',
                'AE'  => 'United Arab Emirates',
                'GB'  => 'United Kingdom',
                'US'  => 'United States',
                'UM'  => 'United States Outlying Islands',
                'UY'  => 'Uruguay',
                'UZ'  => 'Uzbekistan',
                'VU'  => 'Vanuatu',
                'VE'  => 'Venezuela',
                'VN'  => 'Viet Nam',
                'VG'  => 'Virgin Islands, British',
                'VI'  => 'Virgin Islands, U.S.',
                'WF'  => 'Wallis And Futuna',
                'EH'  => 'Western Sahara',
                'YE'  => 'Yemen',
                'ZM'  => 'Zambia',
                'ZW'  => 'Zimbabwe',
                'MMK' => 'Myanmar Kyats'
            ];
            $list_country = [];
            foreach ( $countries as $key => $val ) {
                $list_country [] = [
                    'value' => $key,
                    'label' => $val
                ];
            }
            if ( $optiontree ) {
                return $list_country;
            } else {
                return $countries;
            }
        }


        /**
         * @since 1.0
         **/
        static function _ts_get_where_location( $location_id, $post_type, $where ) {
            global $wpdb;
            if ( (int)$location_id > 0 && is_array( $post_type ) ) {
                $ns = new Nested_set();
                $ns->setControlParams( $wpdb->prefix . 'ts_location_nested' );
                $post_type_in = "";
                foreach ( $post_type as $item ) {
                    $post_type_in .= "'" . $item . "',";
                }
                $post_type_in = substr( $post_type_in, 0, -1 );
                $locations = [];
                if ( is_array( $location_id ) ) {
                    foreach ( $location_id as $location ) {
                        $node = $ns->getNodeWhere( "location_id = " . (int)$location );
                        if ( !empty( $node ) ) {
                            $leftval     = (int)$node[ 'left_key' ];
                            $rightval    = (int)$node[ 'right_key' ];
                            $node_childs = $ns->getNodesWhere( "left_key >= " . $leftval . " AND right_key <= " . $rightval );
                            if ( !empty( $node_childs ) ) {
                                foreach ( $node_childs as $item ) {
                                    $locations[] = (int)$item[ 'location_id' ];
                                }
                            } else {
                                $locations[] = (int)$node[ 'location_id' ];
                            }
                        }
                    }
                } elseif ( count( explode( ',', $location_id ) ) > 1 ) {
                    $location_tmp = explode( ',', $location_id );
                    foreach ( $location_tmp as $k => $v ) {
                        $node = $ns->getNodeWhere( "location_id = " . $v );
                        if ( !empty( $node ) ) {
                            $leftval     = (int)$node[ 'left_key' ];
                            $rightval    = (int)$node[ 'right_key' ];
                            $node_childs = $ns->getNodesWhere( "left_key >= " . $leftval . " AND right_key <= " . $rightval );
                            if ( !empty( $node_childs ) ) {
                                foreach ( $node_childs as $item ) {
                                    $locations[] = (int)$item[ 'location_id' ];
                                }
                            } else {
                                $locations[] = (int)$node[ 'location_id' ];
                            }
                        }

                    }
                } else {
                    $node = $ns->getNodeWhere( "location_id = " . $location_id );
                    if ( !empty( $node ) ) {
                        $leftval     = (int)$node[ 'left_key' ];
                        $rightval    = (int)$node[ 'right_key' ];
                        $node_childs = $ns->getNodesWhere( "left_key >= " . $leftval . " AND right_key <= " . $rightval );
                        if ( !empty( $node_childs ) ) {
                            foreach ( $node_childs as $item ) {
                                $locations[] = (int)$item[ 'location_id' ];
                            }
                        } else {
                            $locations[] = (int)$node[ 'location_id' ];
                        }
                    }
                }


                $where_location = " 1=1 ";
                if ( !empty( $locations ) ) {
                    $where_location .= " AND location_from IN (";
                    $string         = "";
                    foreach ( $locations as $location ) {

                        $string .= "'" . $location . "',";
                    }
                    $string         = substr( $string, 0, -1 );
                    $where_location .= $string . ")";
                } else {
                    $where_location .= " AND location_from IN ('{$location_id}') ";
                }

                if ( !empty( $post_type_in ) ) {
                    $where_location .= " AND post_type IN ({$post_type_in})";
                }

                $where .= " AND {$wpdb->prefix}posts.ID IN (SELECT post_id FROM {$wpdb->prefix}ts_location_relationships WHERE " . $where_location . ")";

            }
            return $where;
        }

        private static function _get_location_weather( $post_id = false ) {
            /*if ( !$post_id ) $post_id = get_the_ID();
            $lat = get_post_meta( $post_id, 'lat', true );
            $lng = get_post_meta( $post_id, 'lng', true );
            if ( $lat and $lng ) {
                $url = "http://api.openweathermap.org/data/2.5/weather?APPID=" . st()->get_option( 'weather_api_key', 'a82498aa9918914fa4ac5ba584a7e623' ) . "&lat=" . $lat . '&lon=' . $lng;
            } else {
                $url = "http://api.openweathermap.org/data/2.5/weather?APPID=" . st()->get_option( 'weather_api_key', 'a82498aa9918914fa4ac5ba584a7e623' ) . "&q=" . get_the_title( $post_id );
            }

            // fix multilanguage whene translate new location
            $post_data = get_post( $post_id, ARRAY_A );
            $slug      = $post_data[ 'post_name' ];
            $cache = get_transient( 'ts_weather_location_' . $slug );
            $dataWeather = null;

            if ( $cache === false ) {
                $raw_geocode = wp_remote_get( $url );

                $body = wp_remote_retrieve_body( $raw_geocode );
                $body = json_decode( $body );
                if ( isset( $body->main->temp ) )
                    set_transient( 'ts_weather_location_' . $post_id, $body, 60 * 60 * 1 );
                $dataWeather = $body;
            } else {
                $dataWeather = $cache;
            }

            return $dataWeather;*/
        }

        static function get_location_temp( $post_id = false ) {
            /*if ( !$post_id ) $post_id = get_the_ID();
            $lat = get_post_meta( $post_id, 'lat', true );
            $lng = get_post_meta( $post_id, 'lng', true );
            if ( !$lat and !$lng ) return false;
            $dataWeather = self::_get_location_weather( $post_id );

            $c = 0;
            $f = 0;
            if ( isset( $dataWeather->main->temp ) ) {
                $k           = $dataWeather->main->temp;
                $temp_format = st()->get_option( 'st_weather_temp_unit', 'c' );
                $c           = self::_change_temp( $k, $temp_format );
                $f           = self::_change_temp( $k, 'f' );
            }
            $icon = '';
            if ( !empty( $dataWeather->weather[ 0 ]->icon ) ) {
                $icon = self::get_weather_icons( $dataWeather->weather[ 0 ]->icon );
            }

            return [
                'temp'   => $c,
                'temp_k' => $f,
                'icon'   => $icon
            ];*/
        }



        /**
         * @since  1.0
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

//        function plugin_dir($url = false) {
//            return ABSPATH . 'wp-content/plugins/' . $this->plugin_name . '/' . $url;
//        }
//        function load_template($slug, $name = false, $data = array()) {
//            if (is_array($data))
//                extract($data);
//
//            if ($name) {
//                $slug = $slug . '-' . $name;
//            }
//            //Find template in folder st_templates/
//            $template = locate_template($this->template_dir . '/' . $slug . '.php');
//
//
//            if (!$template) {
//                //If not, find it in plugins folder
//                $template = $this->plugin_dir() . '/' . $slug . '.php';
//            }
//
//            /*if (st()->get_option('st_theme_style', '') == 'modern') {
//                $_template = locate_template($this->template_dir . '/layouts/modern/' . $slug . '.php');
//                if (is_file($_template)) {
//                    $template = $_template;
//                }
//            }*/
//            //If file not found
//            if (is_file($template)) {
//                ob_start();
//
//                include $template;
//
//                $data = @ob_get_clean();
//
//                return $data;
//            }
//        }


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

        static function get_alt_image($image_id = null) {
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            if (!$alt) {
                $alt = get_bloginfo('description');
            }
            return $alt;
        }

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

        static function get_currency( $theme_option = false ) {
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

        static function booking_primary_currency() {
            return self::$_booking_primary_currency;
        }
        static function get_default_currency( $need = false ){
            $primary = self::booking_primary_currency();
            $primary_obj = self::find_currency( $primary );
            if ( $primary_obj ) {
                if ( $need and isset( $primary_obj[ $need ] ) ) return $primary_obj[ $need ];
                return $primary_obj;
            } else {
                //If user dont set the primary currency, we take the first of list all currency
                $all_currency = self::get_currency();
                if ( isset( $all_currency[ 0 ] ) ) {
                    if ( $need and isset( $all_currency[ 0 ][ $need ] ) ) return $all_currency[ 0 ][ $need ];
                    return $all_currency[ 0 ];
                }
            }
        }

        static function format_money_from_db($money = '', $currency = false) {
            extract(wp_parse_args($currency, TravelHelper::get_current_currency()));
            if ($money == 0) {
                return __("Free", 'trizen-helper');
            }

            $money = convert_money($money, $rate);

            if (!empty($booking_currency_precision)) {
                $money = round($money, $booking_currency_precision);
            }

            $thousand_separator = self::get_current_currency('thousand_separator', ',');
            $decimal_separator = self::get_current_currency('decimal_separator', '.');
            $money = number_format((float) $money, (int) $booking_currency_precision, $decimal_separator, $thousand_separator);

            switch ($booking_currency_pos) {
                case "right":
                    $money_string = $money . $symbol;
                    break;
                case "left_space":
                    $money_string = $symbol . " " . $money;
                    break;

                case "right_space":
                    $money_string = $money . " " . $symbol;
                    break;
                case "left":
                default:
                    $money_string = $symbol . $money;
                    break;
            }
            return $money_string;
        }

        static function get_current_currency( $need = false ) {
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
        static function format_money( $money = false, $need_convert = true, $precision = 0 ) {
            $money              = (float)$money;
            /*$symbol             = TravelHelper::get_current_currency( 'symbol' );
            $precision          = TravelHelper::get_current_currency( 'booking_currency_precision', 2 );
            $thousand_separator = TravelHelper::get_current_currency( 'thousand_separator', ',' );
            $decimal_separator  = TravelHelper::get_current_currency( 'decimal_separator', '.' );*/
            $symbol             = get_woocommerce_currency_symbol();
//            $symbol             = self::get_current_currency( 'symbol' );
            $precision          = TravelHelper::get_current_currency( 'booking_currency_precision', 2 );
            $thousand_separator = TravelHelper::get_current_currency( 'thousand_separator', ',' );
            $decimal_separator  = TravelHelper::get_current_currency( 'decimal_separator', '.' );
            if ( $money == 0 ) {
                return __( "Free", 'trizen-helper' );
            }

            if ( $need_convert ) {
                $money = convert_money( $money );
            }

            /*if ( is_array( $precision ) ) {
                $precision = st()->get_option( 'booking_currency_precision', 2 );
            }*/

            if ( $precision ) {
                $money = round( $money, 2 );
            }

            $template = 'left';

            if ( !$template ) {
                $template = 'left';
            }

            if ( is_array( $decimal_separator ) ) {
                $decimal_separator =  '.';
            }
            if ( is_array( $thousand_separator ) ) {
                $thousand_separator = ',';
            }
            $money = number_format( (float)$money, (int)$precision, $decimal_separator, $thousand_separator );

            switch ( $template ) {
                case "right":
                    $money_string = $money . $symbol;
                    break;
                case "left_space":
                    $money_string = $symbol . " " . $money;
                    break;
                case "right_space":
                    $money_string = $money . " " . $symbol;
                    break;
                case "left":
                default:
                    $money_string = $symbol . $money;
                    break;
            }
            return $money_string;
        }

        static function paging_room( $query = false ) {
            global $wp_query, $ts_search_query;
            if ( $ts_search_query ) {
                $query = $ts_search_query;
            } else $query = $wp_query;
            if ( $query->max_num_pages < 2 ) {
                return;
            }
            $paged          = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
            $max            = $query->found_posts;
            $posts_per_page = $query->query[ 'posts_per_page' ];
            $number = ceil( $max / $posts_per_page );
            $html = ' <ul class="pagination paged_room">';
            if ( $paged > 1 ) {
                $html .= ' <li><a class="pagination paged_item_room" data-page="' . ( $paged - 1 ) . '">' . __( 'Previous', 'trizen-helper' ) . '</a></li>';
            }
            for ( $i = 1; $i <= $number; $i++ ) {
                if ( $i == $paged ) {
                    $html .= ' <li><a class="pagination paged_item_room current" data-page="' . $i . '">' . $i . '</a></li>';
                } else {
                    $html .= '<li><a class="pagination paged_item_room" data-page="' . $i . '">' . $i . '</a></li>';
                }
            }
            if ( $paged < $i - 1 ) {
                $html .= ' <li><a class="pagination paged_item_room" data-page="' . ( $paged + 1 ) . '">' . __( 'Next', 'trizen-helper' ) . '</a></li>';
            }
            $html . '</ul>';
            return $html;
        }

        static function date_diff($date_1 , $date_2 , $differenceFormat = '%a' ){
            $datetime1 = new DateTime();
            $datetime1->setTimestamp((float)$date_1);
            $datetime2 = new DateTime();
            $datetime2->setTimestamp((float)$date_2);
            $interval = date_diff($datetime1, $datetime2);
            return $interval->format($differenceFormat);
        }

        static function paging( $query = false, $wrapper = true ) {
            global $wp_query, $ts_search_query;
            if ( $ts_search_query ) {
                $query = $ts_search_query;
            } else $query = $wp_query;

            // Don't print empty markup if there's only one page.
            if ( $query->max_num_pages < 2 ) {
                return;
            }

            if ( get_query_var( 'paged' ) ) {
                $paged = get_query_var( 'paged' );
            } else if ( get_query_var( 'page' ) ) {
                $paged = get_query_var( 'page' );
            } else {
                $paged = 1;
            }
            $pagenum_link = html_entity_decode( get_pagenum_link() );
            $query_args   = [];
            $url_parts    = explode( '?', $pagenum_link );
            if ( isset( $url_parts[ 1 ] ) ) {
                wp_parse_str( $url_parts[ 1 ], $query_args );
            }
            $pagenum_link = esc_url( remove_query_arg( array_keys( $query_args ), $pagenum_link ) );
            $pagenum_link = trailingslashit( $pagenum_link ) . '%_%';
            $format = $GLOBALS[ 'wp_rewrite' ]->using_index_permalinks() && !strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
            $format .= $GLOBALS[ 'wp_rewrite' ]->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';

            $arg   = [
                'base'      => $pagenum_link,
                'format'    => $format,
                'total'     => $query->max_num_pages,
                'current'   => $paged,
                'mid_size'  => 1,
                // 'add_args' => array_map( 'urlencode', $query_args ),
                'add_args'  => $query_args,
                'prev_text' => __( 'Previous Page', 'trizen-helper' ),
                'next_text' => __( 'Next Page', 'trizen-helper' ),
                'type'      => 'list'
            ];
            /*$style = st()->get_option( 'pag_style', true );
            if ( $style == 'st_tour_ver' ) {
                $arg[ 'prev_text' ] = "<i class='fa fa-angle-left'></i>";
                $arg[ 'next_text' ] = "<i class='fa fa-angle-right'></i>";
            }*/
            // Set up paginated links.
            $links = paginate_links( $arg );

            if ( $links ) :
                if ( $wrapper )
                    $links = str_replace( 'page-numbers', 'col-xs-12 pagination', balanceTags( $links ) );
                $links = str_replace( '<span', '<a', $links );
                $links = str_replace( '</span>', '</a>', $links );
                ?>
                <?php
                echo ($wrapper) ? "<div class='col-xs-12'>" : '';
                echo balanceTags( $links ); // do not esc_html
                echo ($wrapper) ? "</div>" : '';
            endif;
        }


        static function comment_form($args = [], $post_id = null) {
            if (null === $post_id)
                $post_id = get_the_ID();
            // Exit the function when comments for the post are closed.
            if (!comments_open($post_id)) {
                /**
                 * Fires after the comment form if comments are closed.
                 * @since 1.0.0
                 */
                do_action('comment_form_comments_closed');
                return;
            }

            $commenter     = wp_get_current_commenter();
            $user          = wp_get_current_user();
            $user_identity = $user->exists() ? $user->display_name : '';

            $args = [];
            if (!isset($args['format']))
                $args['format'] = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';

            $req      = get_option('require_name_email');
            $html_req = ($req ? " required='required'" : '');
            $html5    = 'html5' === $args['format'];
            $fields   = [
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
             * @since 1.0.0
             * @param array $fields The default comment fields.
             */
            $fields   = apply_filters('comment_form_default_fields', $fields);
            $defaults = [
                'fields'        => $fields,
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
                'action'              => site_url('/wp-comments-post.php'),
                'id_form'             => 'commentform',
                'id_submit'           => 'submit',
                'class_form'          => 'comment-form',
                'class_submit'        => 'submit',
                'name_submit'         => 'submit',
                'title_reply'         => '',
                'title_reply_to'      => __('Leave a Reply to %s', 'trizen-helper'),
                'title_reply_before'  => '<h3 id="reply-title" class="comment-reply-title">',
                'title_reply_after'   => '</h3>',
                'cancel_reply_before' => ' <small>',
                'cancel_reply_after'  => '</small>',
                'cancel_reply_link'   => __('Cancel reply', 'trizen-helper'),
                'label_submit'        => __('Post Comment'),
                'submit_button'       => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
                'submit_field'        => '<p class="form-submit">%1$s %2$s</p>',
                'format'              => 'xhtml',
            ];

            /**
             * Filters the comment form default arguments.
             * Use {@see 'comment_form_default_fields'} to filter the comment fields.
             * @since 1.0.0
             * @param array $defaults The default comment form arguments.
             */
            $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

            // Ensure that the filtered args contain all required default values.
            $args = array_merge($defaults, $args);

            /**
             * Fires before the comment form.
             *
             * @since 1.0.0
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
                        <div class="text-left review-comment-btn-wrap">
                            <input type="hidden" id="comment_post_ID" name="comment_post_ID"
                                   value="<?php echo esc_attr($post_id); ?>">
                            <input type="hidden" id="comment_parent" name="comment_parent" value="0">
                            <input id="submit" type="submit" name="submit"
                                   class="btn btn-green upper font-medium review-comment-btn"
                                   value="<?php esc_attr_e('Leave a Review', 'trizen-helper') ?>">
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


        static function rate_to_string($star, $max = 5) {
            $html = '';
            if ($star > $max)
                $star = $max;
            $moc1 = (int) $star;
            for ($i = 1; $i <= $moc1; $i++) {
                $html .= '<li><i class="la  la-star"></i></li>';
            }
            $new = $max - $star;
            $du = round((float) $star - $moc1, 1);
            if ($du >= 0.2 and $du <= 0.9) {
                $html .= '<li><i class="la  la-star-half-o"></i></li>';
            } elseif ($du) {
                $html .= '<li><i class="la  la-star-o"></i></li>';
            }
            for ($i = 1; $i <= $new; $i++) {
                $html .= '<li><i class="la  la-star-o"></i></li>';
            }
            return apply_filters('ts_rate_to_string', $html);
        }

        /**
        * @since 1.0
        */
        static function transferDestination() {
            $return = [];
            /*$car_by_location = st()->get_option( 'car_transfer_by_location', 'off' );
            if ( $car_by_location == 'off' ) {
                $args = [
                    'post_type'      => 'st_hotel',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => [ 'publish', 'private' ]
                ];

                $query = new WP_Query( $args );
                while ( $query->have_posts() ): $query->the_post();
                    $return[] = [
                        'id'      => get_the_ID(),
                        'name'    => get_the_title(),
                        'address' => get_post_meta( get_the_ID(), 'address', true ),
                        'type'    => 'hotel'
                    ];
                endwhile;
                wp_reset_postdata();

                $terms = get_terms( [
                    'taxonomy'   => 'st_airport',
                    'hide_empty' => false,
                ] );

                if ( !is_wp_error( $terms ) ) {
                    $airport_ids = [];
                    foreach ( $terms as $term ) {
                        array_push( $airport_ids, $term->term_id );
                    }

                    if ( !empty( $airport_ids ) ) {
                        $term_data = ST_Flight_Location_Models::inst()->get_data( $airport_ids );
                        if ( $term_data ) {
                            foreach ( $terms as $term ) {
                                if ( array_key_exists( $term->term_id, $term_data ) ) {
                                    $return[] = [
                                        'id'      => $term->term_id,
                                        'name'    => $term->name,
                                        'address' => $term_data[ $term->term_id ][ 'map_address' ],
                                        'type'    => 'airport'
                                    ];
                                }
                            }
                        }
                    }
                }
            } else {*/
                $locations = TravelHelper::treeLocationHtml();
                if ( !empty( $locations ) ) {
                    foreach ( $locations as $k => $v ) {
                        $return[] = [
                            'id'      => $v[ 'ID' ],
                            'name'    => $v[ 'post_title' ],
                            'address' => '',
                            'type'    => 'location',
                            'level'   => $v[ 'level' ] / 20
                        ];
                    }
                }
//            }

            return $return;
        }
    }
}
