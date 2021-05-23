<?php
/**
 * @since 1.0
 **/

if ( !class_exists( 'TSLocationRelationships' ) ) {
    class TSLocationRelationships {
        public $table                             = 'ts_location_relationships';
        public $column                            = [];
        public $ts_upgrade_location_relationships = 0;
        public $allow_version                     = false;

        public function __construct() {
            add_action( 'save_post', [ $this, 'ts_update_location_relationships' ], 9999999 );
            add_action( 'delete_post', [ $this, 'ts_delete_location_relationships' ], 9999999 );
        }

        public function ts_update_location_relationships( $post_id ) {
            global $wpdb;
            $table          = $wpdb->prefix . $this->table;
            $multi_location = get_post_meta( $post_id, 'multi_location', true );
            $post_type      = get_post_type( $post_id );
            if(empty($multi_location)){
                $sql             = "DELETE FROM {$table} WHERE post_id = $post_id AND location_type = 'multi_location'";
                $wpdb->query( $sql );
            }
            if ( $post_type == "ts_hotel" ) {
                $list_room       = TSAdminHotel::_get_list_room_by_hotel( $post_id );
                $string_location = "";
                if ( !empty( $list_room ) ) {
                    foreach ( $list_room as $key => $val ) {
                        $multi_location_tmp = explode( ',', $multi_location );
                        if ( !empty( $multi_location_tmp ) and is_array( $multi_location_tmp ) ) {
                            foreach ( $multi_location_tmp as $location ) {
                                if ( !empty( $location ) ) {
                                    $location = (int) str_replace( '_', '', $location );
                                    $this->insert_location_relationships( $val->post_id, $location );
                                    $string_location .= "'" . $location . "',";
                                }
                            }
                        }
                        if ( !empty( $string_location ) ) {
                            $string_location = substr( $string_location, 0, -1 );
                            $sql             = "DELETE FROM {$table} WHERE post_id = {$val->post_id} AND location_from NOT IN ({$string_location}) AND location_type = 'multi_location'";
                            $wpdb->query( $sql );
                        }
                        update_post_meta( $val->post_id, 'multi_location', $multi_location );
                    }
                }
            }
            if ( $post_type == "hotel_room" ) {
                $hotel_id = get_post_meta( $post_id, 'room_parent', 'true' );
                if ( empty( $hotel_id ) ) {
                    $hotel_id = request( 'room_parent' );
                }
                $multi_location_hotel = get_post_meta( $hotel_id, 'multi_location', true );
                if ( !empty( $multi_location_hotel ) ) {
                    $string_location    = "";
                    $multi_location_tmp = explode( ',', $multi_location_hotel );
                    if ( !empty( $multi_location_tmp ) and is_array( $multi_location_tmp ) ) {
                        foreach ( $multi_location_tmp as $location ) {
                            if ( !empty( $location ) ) {
                                $location = (int) str_replace( '_', '', $location );
                                $this->insert_location_relationships( $post_id, $location );
                                $string_location .= "'" . $location . "',";
                            }
                        }
                    }
                    if ( !empty( $string_location ) ) {
                        $string_location = substr( $string_location, 0, -1 );
                        $sql             = "DELETE FROM {$table} WHERE post_id = {$post_id} AND location_from NOT IN ({$string_location}) AND location_type = 'multi_location'";
                        $wpdb->query( $sql );
                    }
                    update_post_meta( $post_id, 'multi_location', $multi_location_hotel );
                }
            }

            if ( !empty( $multi_location ) && !is_array( $multi_location ) ) {
                $multi_location = explode( ',', $multi_location );
            }
            $string_location = "";

            if ( !empty( $multi_location ) and is_array( $multi_location ) ) {
                foreach ( $multi_location as $location ) {
                    if ( !empty( $location ) ) {
                        $location = (int) str_replace( '_', '', $location );
                        $this->insert_location_relationships( $post_id, $location );
                        $string_location .= "'" . $location . "',";
                    }
                }
            }

            if ( !empty( $string_location ) ) {
                $string_location = substr( $string_location, 0, -1 );
                $sql = "DELETE FROM {$table} WHERE post_id = {$post_id} AND location_from NOT IN ({$string_location}) AND location_type = 'multi_location'";
                $wpdb->query( $sql );
            }
        }

        public function ts_delete_location_relationships( $post_id ) {
            global $wpdb;
            $table = $wpdb->prefix . $this->table;
            $where = [
                'post_id' => $post_id
            ];
            $wpdb->delete( $table, $where );
        }

        public function insert_location_relationships( $post_id = '', $location = '' ) {
            global $wpdb;
            $table = $wpdb->prefix . 'ts_location_relationships';
            $sql   = "SELECT ID FROM {$table} WHERE post_id = {$post_id} AND location_from = {$location} AND location_type = 'multi_location'";
            $row = $wpdb->get_var( $sql );
            if ( empty( $row ) ) {
                $data = [
                    'post_id'       => $post_id,
                    'location_from' => $location,
                    'location_to'   => 0,
                    'post_type'     => get_post_type( $post_id ),
                    'location_type' => 'multi_location'
                ];
                $wpdb->insert( $table, $data );
            }
        }

        public static function get_inst(){
            static $instance;
            if(is_null($instance)){
                $instance = new self();
            }

            return $instance;
        }
    }

    new TSLocationRelationships;
}