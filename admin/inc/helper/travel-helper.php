<?php
global $st_all_table_loaded;
$st_all_table_loaded = [];
if ( !class_exists( 'TravelHelper' ) ) {

    class TravelHelper
    {
        public $template_dir = "template-parts";
        public $plugin_name = "";
        public static $ts_location = [];
        protected static $listFullNameLocations = [];


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


        // from 1.2.0
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
         * @since 1.2.0
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
