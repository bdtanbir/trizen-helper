<?php
global $st_all_table_loaded;
$st_all_table_loaded = [];
if ( !class_exists( 'TravelHelper' ) ) {

    class TravelHelper
    {
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
    }
}
