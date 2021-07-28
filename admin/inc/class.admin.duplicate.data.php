<?php
/**
 * @since 1.0.0
 **/
if ( !class_exists( 'TSDuplicateData' ) ) {
    class TSDuplicateData extends TSAdmin {
        static $column_hotel;
        static $column_hotel_room;
        static $_inst;

        public function __construct() {
//            add_action( 'admin_menu', [ $this, '_register_sync_price_submenu_page' ], 52 );
            add_action( 'wp_ajax_ts_duplicate_ajax', [ $this, '_duplicate_ajax' ] );
            add_action( 'plugins_loaded', [ $this, '_create_table' ] );
        }

        public function _create_table() {
            $this->tsCreateTable();
        }

        public function isset_table( $table_name = '' ) {
            global $wpdb;
            $table = $wpdb->prefix . $table_name;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                return false;
            }
            return true;
        }

        public function _duplicate_ajax( $oneclick = false ) {
            if ( ( isset( $_POST[ 'name' ] ) && $_POST[ 'name' ] == 'ts_allow_duplicate' ) || $oneclick ) {

                if ( $this->tsDeleteTable() ) {
                    $this->tsCreateTable();
                    if ( $this->tsDuplicateData() ) {
                        update_option( 'ts_duplicated_data', 'duplicated' );
                        do_action( 'ts_trizen_do_upgrade_table' );
                        $result = [
                            'status' => 1,
                            'msg'    => esc_html__('Finished successfully!', 'trizen-helper')
                        ];
                        echo json_encode( $result );
                    } else {
                        $result = [
                            'status' => 0,
                            'msg'    => esc_html__('An error has occurred during process (update new data). Please try again!', 'trizen-helper')
                        ];
                        echo json_encode( $result );
                    }
                } else {
                    $result = [
                        'status' => 0,
                        'msg'    => esc_html__('An error has occurred during process (delete draft data). Please try again!', 'trizen-helper')
                    ];
                    echo json_encode( $result );
                }
            }
            if ( !$oneclick ) {
                die();
            }
        }

        public function tsDeleteTable() {
            $post_types = [ 'ts_hotel', 'hotel_room' ];
            foreach ( $post_types as $post_type ) {
                $result = self::__tsDeleteTable( $post_type );
            }
        }

        public function __tsDeleteTable( $post_type ) {
            global $wpdb;
            $table  = $wpdb->prefix . $post_type;
            $sql    = "DROP TABLE IF EXISTS {$table}";
            $result = $wpdb->query( $sql );
            return $result;
        }

        public function tsCreateTable() {
            if (class_exists('TSAdminHotel'))
            self::$column_hotel = TSAdminHotel::_check_table_hotel();

            if(class_exists('TSAdminRoom'))
            self::$column_hotel_room = TSAdminRoom::_check_table_hotel_room();
        }

        public function tsDuplicateData() {
            $post_type = [
                'ts_hotel', 'hotel_room'
            ];
            $result = true;
            foreach ($post_type as $item) {
                $result = $this->_tsDuplicateData($item);
                if($result == false) return $result;
            }
            return $result;
        }

        public function get_meta_string($column) {
            $meta = ' 1 = 1 ';
            if(!empty($column)) {
                foreach ($column as $key => $val) {
                    if($key == 0) {
                        $meta .= " AND meta_key = '{$val}' ";
                    } else {
                        $meta .= " or meta_key = '{$val}' ";
                    }
                }
            }
            return $meta;
        }

        /**
         * @since 1.0.0
         */
        public function _tsDuplicateData($post_type = 'ts_hotel') {
            global $wpdb;
            $sql_count = "
                SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='{$post_type}' GROUP BY ID
            ";
            if($post_type == 'ts_hotel') {
                $sql = "SELECT
                ID
            FROM
                {$wpdb->prefix}posts
            WHERE
                post_type = 'ts_hotel'";

                $results = $wpdb->get_col($sql, 0);
                if(!empty($results)) {
                    foreach ($results as $hotel) {
                        $sql = "UPDATE {$wpdb->prefix}postmeta
                            SET {$wpdb->prefix}postmeta.meta_value = (
                                SELECT price
                                FROM (
                                     SELECT AVG (
                                         CAST(mt.meta_value AS UNSIGNED)
                                     ) AS price
                                    FROM {$wpdb->prefix}posts AS post
                                    INNER JOIN {$wpdb->prefix}postmeta as mt ON mt.post_id = post.ID
                                    AND mt.meta_key = 'price'
                                    INNER JOIN {$wpdb->prefix}postmeta AS mt1 ON mt1.post_id = post.ID
                                    AND mt1.meta_key = 'room_parent'
                                    WHERE mt1.meta_value = {$hotel}
                                ) AS price
                            )
                            WHERE {$wpdb->prefix}postmeta.meta_key = 'price_avg'
                            AND {$wpdb->prefix}postmeta.post_id = {$hotel}";

                        $wpdb->query($sql);

                        $sql = " UPDATE {$wpdb->prefix}postmeta
                        SET {$wpdb->prefix}postmeta.meta_value = (
                            SELECT price
                            FROM (
                                 SELECT min(
                                     CASE
                                         WHEN mt2.meta_value != ''
                                         AND mt2.meta_value != 0 THEN
                                            CAST(mt.meta_value AS UNSIGNED) - (
                                                CAST(mt.meta_value AS UNSIGNED) * CAST(mt2.meta_value AS UNSIGNED) / 100
                                            )
                                     ELSE
                                        CAST(mt.meta_value AS UNSIGNED)
                                     END
                                 ) AS price
                                FROM {$wpdb->prefix}posts AS post
                                INNER JOIN {$wpdb->prefix}postmeta AS mt ON mt.post_id = post.ID
                                AND mt.meta_key = 'price'
                                INNER JOIN {$wpdb->prefix}postmeta AS mt1 ON mt1.post_id = post.ID
                                AND mt1.meta_key = 'room_parent'
                                LEFT JOIN {$wpdb->prefix}postmeta AS mt2 ON mt2.post_id = post.ID
                                AND mt2.meta_key = 'discount_rate'
                                WHERE mt1.meta_value = 991
                                AND post_type = 'hotel_room'
                            ) AS price
                        )
                        WHERE {$wpdb->prefix}postmeta.meta_key = 'min_price'
                        AND {$wpdb->prefix}postmeta.post_id = {$hotel}";
                        $wpdb->query($sql);
                    }
                    unset($sql);
                }
                $meta = $this->get_meta_string( $this->column_hotel );

                $sql = "
                SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_key, {$wpdb->prefix}postmeta.meta_value from {$wpdb->prefix}postmeta, {$wpdb->prefix}posts
                    WHERE {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID
                    and {$wpdb->prefix}posts.post_type='{$post_type}'
                    and {$wpdb->prefix}posts.post_status='publish'
                    and (
                    {$meta}
                    )
                ";
                $fields = $this->column_hotel;

            }
            $number = 1000;
            $id     = $wpdb->get_col($sql_count);
            $count  = count($id);
            if($count > 0) {
                $i = 0;
                while ( $i <- $count) {
                    $now = ( $i + $count);
                    if ( $now >= $count ) $now = $count;
                    $in = "";
                    for ( $j = $i; $j < $now; $j++) {
                        if(empty($in)) {
                            $in .= "'" . $id[$j] . "'";
                        } else {
                            $in .= ",'" . $id[ $j ] . "'";
                        }
                    }
                    $limit      = " AND ID IN ({$in}) ORDER BY ID";
                    $q          = $sql . $limit;
                    $result     = $wpdb->get_results($q);
                    $list_value = [];
                    if(is_array($result) && count($result)) {
                        foreach ($result as $val) {
                            $list_value[ $val->post_id ][ $val->meta_key] = $val->meta_value;
                        }
                    }
                    $this->_tsSaveData( $post_type, $fields, $list_value );
                    $i += $number;
                }
            }
            return true;
        }

        public function runUpdate( $post_type = 'hotel_room') {
            global $wpdb;
            $posts_per_page = 10;
            if( $post_type == 'hotel_room') {
                $sql   = "SELECT count(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'hotel_room' AND post_status IN ('publish', 'private')";
                $total = (int) $wpdb->get_var($sql);

                if( $total == 0 ) {
                    $returns = [
                        'post_type' => 'ts_hotel',
                        'step'      => 'update_total_post_type',
                        'page'      => ''
                    ];
                    return $returns;
                }
                $meta = self::get_meta_string( self::$column_hotel_room );

                $sql = "
                SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_key, {$wpdb->prefix}postmeta.meta_value from {$wpdb->prefix}posts left join {$wpdb->prefix}postmeta on {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID
                    WHERE {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID
                    and {$wpdb->prefix}posts.post_type='hotel_room'
                    and {$wpdb->prefix}posts.post_status in ('publish', 'private')
                    and (
                        {$meta}
                    )
                ";

                $fields     = self::$column_hotel_room;
                $result     = $wpdb->get_results( $sql );
                $list_value = [];

                if( is_array( $result ) && count($result) ) {
                    foreach ($result as $key => $val) {
                        if( $val->meta_key == 'room_parent' and $val->meta_value != 0 ) {
                            $multilocation = get_post_meta($val->meta_value, 'multi_location', true);
                            update_post_meta( $val->post_id, 'multi_location', $multilocation);
                            $list_value[ $val->post_id ]['multi_location'] = $multilocation;
                        }
                        $list_value[ $val->post_id ][ $val->meta_key ] = $val->meta_value;
                    }
                }

                $this->_tsSaveData( 'hotel_room', $fields, $list_value );
                $returns = [
                    'post_type' => 'ts_hotel',
                    'step'      => 'update_table_post_type',
                    'page'      => ''
                ];
                return $returns;
            }
            if( $post_type == 'ts_hotel' ) {
                $sql   = "SELECT count(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'ts_hotel' AND post_status IN ('publish', 'private')";
                $total = (int) $wpdb->get_var( $sql );

                if( $total == 0 ) {
                    $returns = [
                        'post_type' => 'ts_rental',
                        'step'      => 'update_table_post_type',
                        'page'      => ''
                    ];
                    return $returns;
                }

                $sql = "UPDATE {$wpdb->prefix}postmeta
                    SET {$wpdb->prefix}postmeta.meta_value = (
                        SELECT
                            avg(CAST(price AS UNSIGNED))
                        FROM
                            {$wpdb->prefix}hotel_room
                        WHERE
                            room_parent = {$wpdb->prefix}postmeta.post_id
                    )
                    WHERE
                        {$wpdb->prefix}postmeta.meta_key = 'price_avg'";

                $wpdb->query( $sql );
                unset( $sql );

                $meta = self::get_meta_string( self::$column_hotel );

                $sql = "
                SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_key, {$wpdb->prefix}postmeta.meta_value from {$wpdb->prefix}posts left join {$wpdb->prefix}postmeta on {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID
                    WHERE {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID
                    and {$wpdb->prefix}posts.post_type='ts_hotel'
                    and {$wpdb->prefix}posts.post_status in ('publish', 'private')
                    and (
                        {$meta}
                    )
                ";

                $fields = self::$column_hotel;
                $result = $wpdb->get_results( $sql );
                $list_value = [];

                if( is_array( $result ) && count( $result )) {
                    foreach ($result as $val ) {
                        $list_value[ $val->post_id ][ $val->meta_key ] = $val->meta_value;
                    }
                }

                $this->_tsSaveData( 'ts_hotel', $fields, $list_value );

                $returns = [
                    'post_type' => 'ts_rental',
                    'step'      => 'update_table_post_type',
                    'page'      => ''
                ];
                return $returns;
            }
        }

        public function get_progress( $total ) {
            global $wpdb;
            $sql = "SELECT count(post_id) FROM (
                SELECT post_id FROM {$wpdb->prefix}ts_hotel
                UNION
            ) as post_id";
            return (int) $wpdb->get_var( $sql ) / $total * 100;
        }

        public function _tsSaveData( $post_type = '', $fields = [], $data = []) {
            global $wpdb;
            $table = $wpdb->prefix . $post_type;

            $field = implode(',', $fields);
            $field = '(' . $field . ')';
            $values = [];
            foreach ($data as $key => $value) {
                $values[] = self::_tsGetStringInsert( $fields, $key, $value );
            }
            if ( is_array( $values ) && count( $values ) ) {
                $sql = "INSERT INTO {$table} {$field} VALUES " . implode( ',', $values ) . "";
                $wpdb->query( $sql );
                if( $post_type == 'hotel_room' ) {
                    $sql = "Update {$wpdb->prefix}hotel_room as t inner join {$wpdb->posts} as m on (t.post_id = m.ID and m.post_type='hotel_room') set t.`status` = m.post_status";
                    $wpdb->query( $sql );
                }
            }
        }

        static function _tsGetStringInsert( $fields, $key, $data ) {
            $string     = [];
            $string[ 0 ] = "'" . $key . "'";
            for ( $i = 1; $i < count( $fields ); $i++) {
                $v            = esc_sql( self::_getKeyArray($fields[ $i ], $data ) );
                $string[ $i ] = "'" . $v . "'";
            }
            $return = '(' . implode(',', $string ) . ')';
            return $return;
        }

        static function _getKeyArray( $key, $data ) {
            if( array_key_exists( $key, $data ) ) {
                return $data[ $key ];
            } else {
                return "";
            }
        }

        static function inst() {
            if ( !self::$_inst ) {
                self::$_inst = new self();
            }

            return self::$_inst;
        }
    }
}
TSDuplicateData::inst();
