<?php
/**
 * @since 1.2.2
 **/
if ( !class_exists( 'TSUpgradeData' ) ) {
    class TSUpgradeData{
        public $table_nested               = 'ts_location_nested';
        public $table_relationships        = 'ts_location_relationships';
        public $column                     = [];
        public $st_upgrade_location_nested = 0;
        public $allow_version              = false;

        public function __construct() {
            add_action( 'plugins_loaded', [&$this, '_check_table_location_nested'], 50 );

            /**
             * @since 1.0.0
             **/
            add_action( 'plugins_loaded', [&$this, '_check_table_properties'], 50 );
            add_action( 'save_post', [ $this, '_save_properties' ], 10, 2 );

            add_action( 'plugins_loaded', [&$this, '_check_table_location_relationships'], 50 );
            add_action( 'admin_enqueue_scripts', [$this, '_add_scripts'] );
            if ( class_exists( 'STTravelCode' ) ) {
                add_action( 'admin_notices', [$this, '_add_notices'] );
            }
            add_action( 'wp_ajax_ts_get_data_location_nested', [$this, 'ts_get_data_location_nested'], 9999 );
            add_action( 'save_post', [$this, 'ts_update_location_nested'], 9999, 2 );
            add_action( 'delete_post', [$this, 'ts_delete_location_nested'], 50 );
        }

        /**
         * @since 1.3.1
         **/
        public function _save_properties( $post_id, $post_object ) {
            if ( !in_array( $post_object->post_type, [ 'ts_hotel', 'hotel_room' ] ) ) {
                return $post_id;
            }
            global $pagenow;

            /* don't save if $_POST is empty */
            if ( empty( $_POST ) || ( isset( $_POST[ 'vc_inline' ] ) && $_POST[ 'vc_inline' ] == true ) )
                return $post_id;

            /* don't save during quick edit */
            if ( $pagenow == 'admin-ajax.php' )
                return $post_id;

            /* don't save during autosave */
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            /* don't save if viewing a revision */
            if ( $post_object->post_type == 'revision' || $pagenow == 'revision.php' )
                return $post_id;

            $properties = get_post_meta( $post_id, 'properties_near_by', true );

            global $wpdb;
            $sql = "DELETE FROM {$wpdb->prefix}ts_properties WHERE post_id = {$post_id}";
            $wpdb->query( $sql );
            if ( !empty( $properties ) ) {
                foreach ( $properties as $key => $val ) {
                    $data = [
                        'id'          => null,
                        'post_id'     => $post_id,
                        'name'        => esc_sql( $val[ 'title' ] ),
                        'featured'    => esc_sql( $val[ 'featured_image' ] ),
                        'description' => esc_sql( $val[ 'description' ] ),
                        'icon'        => esc_sql( $val[ 'icon' ] ),
                        'lat'         => esc_sql( $val[ 'lat' ] ),
                        'lng'         => esc_sql( $val[ 'lng' ] ),
                    ];
                    $wpdb->insert( $wpdb->prefix . 'ts_properties', $data );
                }
            }
        }

        public function _add_scripts() {
            wp_register_style( 'admin-location-nested', get_template_directory_uri() . '/css/admin/admin.location-nested.css' );
            wp_register_script( 'admin-location-nested.js', get_template_directory_uri() . '/js/admin/location-nested.js', [
                'jquery'
            ], true );
        }

        public function _add_notices() {
            if(get('page','') != 'tgmpa-install-plugins'){
                $my_theme = wp_get_theme();
                $version  = $my_theme->get( 'Version' );
                $check    = get_option( 'ts_completed_update_location_nested', '' );
                $check2   = get_option( 'ts_completed_update_location_relationships', '' );
                $check3   = get_option( 'ts_duplicated_data', '' );
                if ( request( 'page', '' ) == 'ts-upgrade-data' ) {
                    $upgraded = false;
                    if ( $check && $check3 && $check2 ) {
                        $upgraded = true;
                    }
                    ?>
                        <div class="notice notice-warning">
                            <p><?php echo __('From the version <strong>1.0.2</strong>, it has few new data updates. Click "Update Now" button to perform it.', 'trizen-helper'); ?></p>
                            <?php
                                if( $upgraded ) { ?>
                                    <p><em>(You did it once. If you want to do ti again, click "Update Now" button.)</em></p>
                                <?php }
                            ?>
                            <button id="ts_update-glocation" class="button trizen-btn" type="submit">
                                <?php esc_html_e('Update Now', 'trizen-helper'); ?>
                            </button>
                        </div>

                        <div class="update glocation-wrapper">
                            <div class="update-glocation-content">
                                <div class="update-glocation-title clear">
                                    <h4 class="title">
                                        <?php esc_html_e('Trizen Upgrade Data', 'trizen-helper'); ?>
                                    </h4>

                                    <a href="#" class="update-glocation-close"></a>
                                </div>

                                <div class="update-glocation-description">
                                    <?php echo __('From version <strong>1.0.2</strong> upgrade we need some data to use from searching, updating prices, and some manipulation of data access is faster. Here are the items that need to be upgraded. Click "Upgrade" to begin implementation.', 'trizen-helper'); ?>
                                    <br>
                                    <h2>
                                        <?php esc_html_e('You will upgrade these options below:', 'trizen-helper'); ?>
                                    </h2>
                                </div>

                                <form action="" class="update-item-form">
                                    <div class="item step-1">
                                        <div class="info">
                                            <input type="checkbox" checked name="update_table_post_type" id="update_table_post_type" style="display: none;">
                                            <p><?php echo __('Update <strong>services</strong>', 'trizen-helper'); ?></p>
                                            <p class="status"></p>
                                        </div>
                                    </div>

                                    <div class="item step-2">
                                        <div class="info">
                                            <input type="checkbox" checked name="update_location_nesated" id="update_location_nested" style="display: none;">
                                            <p><?php echo __('Update <strong>location</strong>', 'trizen-helper'); ?></p>
                                            <p class="status"></p>
                                        </div>
                                    </div>
                                    <div class="item step-3">
                                        <div class="info">
                                            <input type="checkbox" checked name="update_location_relationships" id="update_location_relationships" style="display: none;">
                                            <p><?php echo __('Update <strong>location relationships', 'trizen-helper'); ?></p>
                                            <p class="status"></p>
                                        </div>
                                    </div>

                                    <div class="update-glocation-progress">
                                        <div class="progress-bar"><span style="width: 0%;"></span></div>
                                    </div>
                                </form>

                                <div class="update-glocation-note">
                                    <?php esc_html_e('(*) Note: "The update data can occur error corrupt the content or misleading information. We recommend that customers should back up your data before performing.', 'trizen-helper'); ?>
                                </div>

                                <div class="update-glocation-message"></div>
                                <div class="update-glocation-button"><?php esc_html_e('Run', 'trizen-helper'); ?></div>
                                <input type="checkbox" checked name="reset_table" id="reset_table" value="<?php esc_attr_e('Reset', 'trizen-helper'); ?>" style="display: none;">
                        </div>
                    </div>
                    <?php
                    /*echo balanceTags( $this->load_view( 'upgrade/index', false, [
                        'upgraded' => $upgraded
                    ] ) );*/
                }
            }

        }

        public function isset_table( $table ) {
            global $wpdb;
            if ( strtolower( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) != strtolower( $table ) ) {
                return false;
            }

            return true;
        }

        public function _deleteTable( $table ) {
            global $wpdb;
            $wpdb->query( "DROP TABLE {$table}" );
        }

        public function _check_table_location_nested() {
            global $wpdb;
            $dbhelper = new DatabaseHelper( '1.0.1' );
            $dbhelper->setTableName( $this->table_nested );
            $column       = [
                'id'      => [
                    'type'           => 'bigint',
                    'length'         => 9,
                    'AUTO_INCREMENT' => true
                ],
                'location_id'      => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'location_country' => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'parent_id'  => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'left_key'   => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'right_key'  => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'name'       => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'fullname' => [
                    'type' => 'text'
                ],
                'language'   => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'status'     => [
                    'type'   => 'varchar',
                    'length' => 255
                ]
            ];
            $this->column = $column;
            $dbhelper->setDefaultColums( $column );
            $dbhelper->check_meta_table_is_working( 'location_nested_table_version' );
            $sql     = "SELECT id FROM " . $wpdb->prefix . $this->table_nested . " LIMIT 1";
            $num_row = (int) $wpdb->get_var( $sql );
            if ( $num_row <= 0 ) {
                $ns = new Nested_set();
                $ns->setControlParams( $wpdb->prefix . $this->table_nested );
                $ns->insertNewTree( [
                    'location_id' => 0,
                    'name'        => 'root',
                    'status'      => 'private_root',
                    'fullname'    => 'root'
                ] );
            }
        }

        public function _check_table_properties() {
            global $wpdb;
            $dbhelper = new DatabaseHelper( '1.0.0' );
            $dbhelper->setTableName( 'ts_properties' );
            $column = [
                'id' => [
                    'type'           => 'bigint',
                    'length'         => 9,
                    'AUTO_INCREMENT' => true
                ],
                'post_id'    => [
                    'type'   => 'int',
                    'length' => 11
                ],
                'name'       => [
                    'type'   => 'text',
                    'length' => 500,
                ],
                'featured'   => [
                    'type'   => 'varchar',
                    'length' => 200
                ],
                'description' => [
                    'type' => 'text',
                ],
                'icon'       => [
                    'type'   => 'varchar',
                    'length' => 200
                ],
                'lat'        => [
                    'type'   => 'float',
                    'length' => '10,6'
                ],
                'lng'        => [
                    'type'   => 'float',
                    'length' => '10,6'
                ]
            ];
            $column;
            $dbhelper->setDefaultColums( $column );
            $dbhelper->check_meta_table_is_working( 'properties_table_version' );

        }

        public function _check_table_location_relationships() {
            global $wpdb;
            $dbhelper = new DatabaseHelper( '1.0.0' );
            $dbhelper->setTableName( $this->table_relationships );
            $column       = [
                'id'      => [
                    'type'           => 'bigint',
                    'length'         => 9,
                    'AUTO_INCREMENT' => true
                ],
                'post_id'    => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'location_from' => [
                    'type'   => 'bigint',
                    'length' => 11
                ],
                'location_to' => [
                    'type'    => 'bigint',
                    'length'  => 11
                ],
                'post_type'  => [
                    'type'   => 'varchar',
                    'length' => 255
                ],
                'location_type' => [
                    'type'   => 'varchar',
                    'length' => 255
                ]
            ];
            $this->column = $column;
            $dbhelper->setDefaultColums( $column );
            $dbhelper->check_meta_table_is_working( 'location_relationships_table_version' );
        }

        /**
         * @since 1.0.0
         **/
        public function ts_get_data_location_nested() {
            global $wpdb;
            $duplicate = TSDuplicateData::inst();
            if ( request( 'update_table_post_type', '' ) == 'update_table_post_type' && request( 'step', '' ) == 'update_table_post_type' ) {
                $progress = (float) request( 'progress', '' );
                $sql      = "SELECT count(ID) as total FROM {$wpdb->prefix}posts WHERE post_type in ('ts_hotel') AND post_status IN ('publish','private')";
                $total    = (int) $wpdb->get_var( $sql );
                if ( $progress == 100 || $total <= 0 ) {
                    update_option( 'ts_duplicated_data', 'updated' );
                    echo json_encode( [
                        'status'                        => 'continue',
                        'progress'                      => 100,
                        'step'                          => 'update_location_nested',
                        'page'                          => 1,
                        'number_page'                   => '',
                        'update_location_nested'        => request( 'update_location_nested', '' ),
                        'update_location_relationships' => request( 'update_location_relationships', '' ),
                        'message'                       => 'The next step is being checked...',
                        'reset_table'                   => 'reset',
                        'post_type'                     => ''
                    ] );
                    die;
                }
                if ( request( 'reset_table', '' ) == 'reset' ) {
                    $duplicate->tsDeleteTable();
                    $duplicate->tsCreateTable();
                    update_option( 'ts_duplicated_data', '' );
                    update_option( 'update_meta_hotel', '' );
                }
                $posts_per_page                             = 10;
                $post_type                                  = request( 'post_type', 'hotel_room' );
                $page                                       = (int) request( 'page', 1 );
                $results                                    = $duplicate->runUpdate( $post_type );
                $results[ 'progress' ]                      = $duplicate->get_progress( $total );
                $results[ 'number_page' ]                   = '';
                $results[ 'status' ]                        = 'continue';
                $results[ 'update_table_post_type' ]        = request( 'update_table_post_type', '' );
                $results[ 'update_location_nested' ]        = request( 'update_location_nested', '' );
                $results[ 'update_location_relationships' ] = request( 'update_location_relationships', '' );
                $results[ 'message' ]                       = 'Updating new table service ...';
                echo json_encode( $results );
                die();
            }
            if ( request( 'update_location_nested', '' ) == 'update_location_nested' && request( 'step', '' ) == 'update_location_nested' ) {
                $table = $wpdb->prefix . $this->table_nested;
                $ns    = new Nested_set();
                $ns->setControlParams( $table );
                if ( request( 'reset_table', '' ) == 'reset' ) {
                    if ( $this->isset_table( $table ) ) {
                        $this->_deleteTable( $table );
                        $this->_check_table_location_nested();
                        update_option( 'ts_completed_update_location_nested', '' );
                    }
                }
                $sql = "SELECT
        				(
        					SELECT
        						count(ID)
        					FROM
        						{$wpdb->prefix}posts
        					WHERE
        						post_type = 'location'
        					AND post_status NOT IN ('auto-draft')
        				) - (
        					SELECT
        						count(id)
        					FROM
        						{$wpdb->prefix}ts_location_nested
        					WHERE
        						id <> 1
        				)";
                $compare = $wpdb->get_var( $sql );
                if ( $compare == 0 ) {
                    update_option( 'ts_completed_update_location_nested', 'completed' );
                    echo json_encode( [
                        'status'                        => 'continue',
                        'progress'                      => 100,
                        'step'                          => 'update_location_full_name',
                        'page'                          => 1,
                        'number_page'                   => '',
                        'update_location_nested'        => request( 'update_location_nested', '' ),
                        'update_location_relationships' => request( 'update_location_relationships', '' ),
                        'message'                       => 'The next step is being checked...',
                        'reset_table'                   => '',
                        'post_type'                     => ''
                    ] );
                    die;
                }
                $results = $this->get_data_location();

                if ( !empty( $results[ 'lists' ] ) ) {
                    $string_value = '';
                    foreach ( $results[ 'lists' ] as $list ) {
                        $id               = null;
                        $location_id      = (int) $list[ 'ID' ];
                        $location_country = get_post_meta( $location_id, 'location_country', true );
                        $name             = $list[ 'post_title' ];
                        $parent_id        = (int) $list[ 'post_parent' ];
                        $status           = $list[ 'post_status' ];
                        if ( $parent_id == 0 ) {
                            $this->setFirstChild( $parent_id, $location_country, $location_id, $name, $status );
                        } else {
                            $this->setChild( $parent_id, $location_country, $location_id, $name, $status );
                        }
                    }
                    $progress = $wpdb->get_var( "SELECT
    					(
    						SELECT
    							count(id)
    						FROM
    							{$wpdb->prefix}ts_location_nested
    						WHERE
    							id <> 1
    					) / (
    						SELECT
    							count(ID)
    						FROM
    							{$wpdb->prefix}posts
    						WHERE
    							post_type = 'location'
    						AND post_status NOT IN ('auto-draft')

    					) * 100" );

                    $results[ 'page' ]                          = '';
                    $results[ 'number_page' ]                   = '';
                    $results[ 'progress' ]                      = $progress;
                    $results[ 'status' ]                        = 'continue';
                    $results[ 'step' ]                          = request( 'step', '' );
                    $results[ 'update_location_nested' ]        = request( 'update_location_nested', '' );
                    $results[ 'update_location_relationships' ] = request( 'update_location_relationships', '' );
                    $results[ 'message' ]                       = esc_html__('Updating location data...', 'trizen-helper');
                    $results[ 'post_type' ]                     = '';
                    unset( $results[ 'lists' ] );
                    echo json_encode( $results );
                    die();
                } else {
                    $results[ 'page' ]                          = 1;
                    $results[ 'number_page' ]                   = '';
                    $results[ 'progress' ]                      = 100;
                    $results[ 'status' ]                        = 'continue';
                    $results[ 'reset_table' ]                   = '';
                    $results[ 'step' ]                          = 'update_location_full_name';
                    $results[ 'update_location_nested' ]        = 'update_location_nested';
                    $results[ 'update_location_relationships' ] = 'update_location_relationships';
                    $results[ 'message' ]                       = esc_html__('The next step is being checked...', 'trizen-helper');
                    $results[ 'post_type' ]                     = '';
                    if(isset($results['lists'])) {
                        unset($results['lists']);
                    }
                    echo json_encode($results);die;
                    /*echo json_encode( [
                        'status'  => 'error',
                        'message' => 'Your location is empty.'
                    ] );
                    die();*/
                }
            }
            if ( request( 'update_location_nested', '' ) == 'update_location_nested' && request( 'step', '' ) == 'update_location_full_name' ) {
                $table = $wpdb->prefix . $this->table_nested;
                $ns    = new Nested_set();
                $ns->setControlParams( $table );
                $posts_per_page = 10;
                $number_page    = (int) request( 'number_page', '' );
                if ( $number_page == 0 ) {
                    $root        = $ns->getRootNodes();
                    $total       = (int) $ns->getNumberOfChildren( $root[ 0 ] );
                    $number_page = ceil( $total / $posts_per_page );
                }
                $page = (int) request( 'page', 1 );
                if ( $page > $number_page ) {
                    echo json_encode( [
                        'status'                        => 'continue',
                        'progress'                      => 100,
                        'step'                          => 'update_location_relationships',
                        'page'                          => 1,
                        'number_page'                   => '',
                        'update_location_nested'        => request( 'update_location_nested', '' ),
                        'update_location_relationships' => request( 'update_location_relationships', '' ),
                        'message'                       => 'The next step is being checked...',
                        'reset_table'                   => 'reset',
                        'post_type'                     => ''
                    ] );
                    die;
                }
                $offset = ( $page - 1 ) * $posts_per_page;
                $lists  = $this->get_data_location_inserted( $page, $number_page, $posts_per_page );
                if ( !empty( $lists ) ) {
                    foreach ( $lists as $node ) {
                        $string = $this->getFullName( $node );
                        $this->updateExtrafield( (int) $node[ 'location_id' ], [
                            'fullname' => $string
                        ] );
                    }
                    $progress                                   = $progress = ( $page / $number_page ) * 100;
                    $next_page                                  = ( $page < $number_page ) ? $page + 1 : $number_page + 1;
                    $results[ 'page' ]                          = $next_page;
                    $results[ 'number_page' ]                   = $number_page;
                    $results[ 'progress' ]                      = $progress;
                    $results[ 'status' ]                        = 'continue';
                    $results[ 'step' ]                          = request( 'step', '' );
                    $results[ 'update_location_nested' ]        = request( 'update_location_nested', '' );
                    $results[ 'update_location_relationships' ] = request( 'update_location_relationships', '' );
                    $results[ 'message' ]                       = 'Updating name location data ...';
                    $results[ 'post_type' ]                     = '';
                    echo json_encode( $results );
                    die();
                } else {
                    update_option( 'ts_completed_update_location_nested', 'completed' );
                    echo json_encode( [
                        'status'                        => 'continue',
                        'progress'                      => 100,
                        'step'                          => 'update_location_relationships',
                        'page'                          => 1,
                        'number_page'                   => '',
                        'update_location_nested'        => request( 'update_location_nested', '' ),
                        'update_location_relationships' => request( 'update_location_relationships', '' ),
                        'message'                       => 'The next step is being checked...',
                        'reset_table'                   => 'reset',
                        'post_type'                     => ''
                    ] );
                    die;
                }
            }
            if ( request( 'update_location_relationships', '' ) == 'update_location_relationships' && request( 'step', '' ) == 'update_location_relationships' ) {
                $table = $wpdb->prefix . $this->table_relationships;
                $ns    = new Nested_set();
                $ns->setControlParams( $table );
                if ( request( 'reset_table', '' ) == 'reset' ) {
                    if ( $this->isset_table( $table ) ) {
                        $this->_deleteTable( $table );
                        $this->_check_table_location_relationships();
                        update_option( 'ts_completed_update_location_relationships', '' );
                    }
                }
                $posts_per_page = 10;
                $number_page    = (int) request( 'number_page', '' );
                if ( $number_page == 0 ) {
                    $total       = intval( $wpdb->get_var( "SELECT
						COUNT(DISTINCT ID)
					FROM
						{$wpdb->prefix}posts
					INNER JOIN {$wpdb->prefix}postmeta AS mt ON mt.post_id = {$wpdb->prefix}posts.ID
					AND mt.meta_key = 'multi_location'
					WHERE
						post_type IN (
							'ts_hotel',
							'hotel_room'
						)
					AND mt.meta_value <> ''
					AND post_status NOT IN ('auto-draft')" ) );
                    $number_page = ceil( $total / $posts_per_page );
                }
                $page = (int) request( 'page', 1 );
                if ( $page > $number_page ) {
                    update_option( 'ts_completed_update_location_relationships', 'completed' );
                    echo json_encode( [
                        'status'      => 'completed',
                        'progress'    => 100,
                        'step'        => '',
                        'page'        => 1,
                        'number_page' => '',
                        'mesage'      => ''
                    ] );
                    die;
                }
                $results = $this->get_data_location_relationship( $page, $number_page, $posts_per_page );
                if ( !empty( $results[ 'lists' ] ) ) {
                    $string_value = '';
                    foreach ( $results[ 'lists' ] as $list ) {
                        $post_id        = (int) $list[ 'ID' ];
                        $multi_location = explode( ',', $list[ 'location' ] );
                        $post_type      = $list[ 'post_type' ];
                        foreach ( $multi_location as $location ) {
                            if ( !empty( $location ) ) {
                                $location = (int) str_replace( '_', '', $location );
                                $data     = [
                                    'post_id'       => $post_id,
                                    'location_from' => $location,
                                    'location_to'   => '',
                                    'post_type'     => $post_type,
                                    'location_type' => 'multi_location'
                                ];
                                $wpdb->insert( $wpdb->prefix . $this->table_relationships, $data );
                            }
                        }
                    }
                    $progress                                   = ( $page / $number_page ) * 100;
                    $next_page                                  = ( $page < $number_page ) ? $page + 1 : $number_page + 1;
                    $results[ 'page' ]                          = $next_page;
                    $results[ 'number_page' ]                   = $number_page;
                    $results[ 'progress' ]                      = $progress;
                    $results[ 'status' ]                        = 'continue';
                    $results[ 'step' ]                          = request( 'step', '' );
                    $results[ 'update_location_nested' ]        = request( 'update_location_nested', '' );
                    $results[ 'update_location_relationships' ] = request( 'update_location_relationships', '' );
                    $results[ 'message' ]                       = 'Updating location relationships data...';
                    $results[ 'reset_table' ]                   = '';
                    $results[ 'post_type' ]                     = '';
                    unset( $results[ 'lists' ] );
                    echo json_encode( $results );
                    die();
                } else {
                    echo json_encode( [
                        'status'  => 'error',
                        'message' => __('Have an error when get relationships data. Contact with supporter for help!', 'trizen-helper')
                    ] );
                    die();
                }
            }
            echo json_encode( [
                'status'   => 'completed',
                'progress' => 100,
                'mesage'   => ''
            ] );
            die();
        }

        public function setFirstChild( $parent_id = 0, $location_country = '', $location_id = 0, $name = '', $status = 'publish' ) {
            global $wpdb;
            $ns = new Nested_set();
            $ns->setControlParams( $wpdb->prefix . $this->table_nested );
            $node_root = $ns->getRootNodes();
            $node      = [
                'id'        => (int) $node_root[ 0 ][ 'id' ],
                'parent_id' => (int) $node_root[ 0 ][ 'parent_id' ],
                'left_key'  => (int) $node_root[ 0 ][ 'left_key' ],
                'right_key' => (int) $node_root[ 0 ][ 'right_key' ]
            ];
            $language  = $this->langcode_post_id( $location_id );
            $extra     = [
                'location_country' => $location_country,
                'location_id'      => $location_id,
                'name'             => $name,
                'status'           => $status,
                'language'         => $language
            ];
            $new_node  = $ns->appendNewChild( $node, $extra );

            return $new_node;
        }

        public function setChild( $parent_id, $location_country, $location_id, $name, $status ) {
            global $wpdb;
            $ns = new Nested_set();
            $ns->setControlParams( $wpdb->prefix . $this->table_nested );
            $node_parent = $ns->getNodeWhere( 'location_id = ' . $parent_id );
            if ( empty( $node_parent ) ) {
                $this->setFirstChild( $parent_id, $location_country, $location_id, $name, $status );
            } else {
                $node     = [
                    'id'        => (int) $node_parent[ 'id' ],
                    'parent_id' => (int) $node_parent[ 'parent_id' ],
                    'left_key'  => (int) $node_parent[ 'left_key' ],
                    'right_key' => (int) $node_parent[ 'right_key' ]
                ];
                $language = $this->langcode_post_id( $location_id );
                $extra    = [
                    'location_country' => $location_country,
                    'name'             => $name,
                    'location_id'      => $location_id,
                    'status'           => $status,
                    'language'         => $language
                ];
                $new_node = $ns->appendNewChild( $node, $extra );

                return $new_node;
            }
        }

        public function getFullName( $node, $new_name = '' ) {
            global $wpdb;
            $table = $wpdb->prefix . $this->table_nested;
            $ns    = new Nested_set();
            $ns->setControlParams( $wpdb->prefix . $this->table_nested );
            if ( empty( $node ) ) {
                return '';
            }
            $string = isset($node['name']) ? $node['name'] : '';
            if(!empty($new_name)){
                $string = $new_name;
            }

            $tree   = $ns->getNodesWhere( "left_key < " . (int) $node[ 'left_key' ] . " AND right_key > " . (int) $node[ 'right_key' ] . " AND location_id <> 0", "left_key DESC" );
            if ( !empty( $tree ) ) {
                foreach ( $tree as $key => $item ) {
                    $string .= ', ' . $item[ 'name' ];
                }
            }
            $string .= $this->getZipCodeHtml( (int) $node[ 'location_id' ] );

            return $string;
        }

        public function getZipCodeHtml( $post_id ) {
            $zipcode = get_post_meta( $post_id, 'zipcode', true );
            if ( $zipcode && !empty( $zipcode ) ) {
                return '||' . $zipcode;
            } else {
                return '';
            }
        }

        public function updateExtrafield( $location_id, $extrafields = [] ) {
            global $wpdb;
            $table = $wpdb->prefix . $this->table_nested;
            $data  = $extrafields;
            $where = [
                'location_id' => $location_id
            ];
            $wpdb->update( $table, $data, $where );
        }

        public function get_data_location_inserted( $page, $number_page, $posts_per_page ) {
            global $wpdb;
            $offset  = ( $page - 1 ) * $posts_per_page;
            $sql     = "SELECT
				`location_id`,
				`parent_id`,
				`left_key`,
				`right_key`,
				`name`
			FROM
				{$wpdb->prefix}ts_location_nested
			WHERE id <> 1
			ORDER BY id ASC
			LIMIT {$offset},
			{$posts_per_page}";
            $results = $wpdb->get_results( $sql, ARRAY_A );
            return $results;
        }

        public function get_data_location() {
            global $wpdb;
            $table        = $wpdb->prefix . 'posts';
            $table_nested = $wpdb->prefix . 'ts_location_nested';
            $sql          = "SELECT
    				ID,
    				post_parent,
    				post_title,
    				post_status,
    				post_type
    			FROM
    				{$wpdb->prefix}posts
    			WHERE
    				post_type = 'location'
    			AND post_status NOT IN ('auto-draft')
    			AND post_parent IN ( SELECT location_id FROM {$table_nested})
    			AND ID NOT IN (SELECT location_id FROM {$table_nested})
    			ORDER BY post_parent ASC
    			LIMIT 0, 10";
           $results      = $wpdb->get_results( $sql, ARRAY_A );
           return [
                'lists' => $results
           ];
        }

        public function get_data_location_relationship( $page = 1, $number_page = 1, $posts_per_page = 1 ) {
            global $wpdb;
            $offset  = ( $page - 1 ) * $posts_per_page;
            $sql     = "SELECT DISTINCT
				ID,
				mt.meta_value as location,
				post_type
			FROM
				{$wpdb->prefix}posts
			INNER JOIN {$wpdb->prefix}postmeta AS mt ON mt.post_id = {$wpdb->prefix}posts.ID
			AND mt.meta_key = 'multi_location'
			WHERE
				post_type IN ('ts_hotel','hotel_room')
			AND mt.meta_value <> ''
			AND post_status NOT IN ('auto-draft')
			ORDER BY
				id ASC
			LIMIT {$offset}, {$posts_per_page}";
            $results = $wpdb->get_results( $sql, ARRAY_A );
            return [
                'lists' => $results
            ];
        }

        public function langcode_post_id( $post_id ) {
            global $wpdb;
            if ( function_exists( 'icl_object_id' ) ) {
                $query      = $wpdb->prepare( 'SELECT language_code FROM ' . $wpdb->prefix . 'icl_translations WHERE element_id="%d"', $post_id );
                $query_exec = $wpdb->get_row( $query );
                if ( !empty( $query_exec->language_code ) ) {
                    return $query_exec->language_code;
                }
                return 'en';
            }
            return 'en';
        }

        public function ts_update_location_nested( $post_id, $post_object ) {
            if ( get_post_type( $post_id ) == 'location' && get_post_field( 'post_status', $post_id ) != 'auto-draft' ) {
                global $wpdb;
                $ns = new Nested_set();
                $ns->setControlParams( $wpdb->prefix . $this->table_nested );
                $location_id      = (int) $post_id;
                $location_country = get_post_meta( $post_id, 'location_country', true );
                $name             = get_the_title( $post_id );
                $parent_id        = (int) get_post_field( 'post_parent', $post_id );
                $status           = get_post_field( 'post_status', $post_id );
                $node             = $ns->getNodeWhere( 'location_id = ' . $post_id );
                if ( !empty( $node ) ) {
                    $target = $ns->getNodeWhere( 'location_id = ' . $parent_id );
                    $ns->setNodeAsLastChild( $node, $target );
                    $new_node = $ns->getNodeWhere( 'location_id = ' . $post_id );
                    $string   = $this->getFullName( $new_node, esc_html( $post_object->post_title ) );
                    $language = $this->langcode_post_id( $location_id );
                    $this->updateExtrafield( $location_id, [
                        'name'             => $name,
                        'status'           => $status,
                        'location_country' => $location_country,
                        'fullname'         => $string,
                        'language'         => $language
                    ] );
                } else {
                    if ( $parent_id == 0 ) {
                        $new_node = $this->setFirstChild( $parent_id, $location_country, $location_id, $name, $status );
                        $string   = $this->getFullName( $new_node, esc_html( $post_object->post_title ) );
                        $language = $this->langcode_post_id( (int) $new_node[ 'location_id' ] );
                        $this->updateExtrafield( (int) $new_node[ 'location_id' ], [
                            'fullname' => $string,
                            'language' => $language
                        ] );
                    } else {
                        $new_node = $this->setChild( $parent_id, $location_country, $location_id, $name, $status );
                        $string   = $this->getFullName( $new_node, esc_html( $post_object->post_title ) );
                        $language = $this->langcode_post_id( (int) $new_node[ 'location_id' ] );
                        $this->updateExtrafield( (int) $new_node[ 'location_id' ], [
                            'fullname' => $string,
                            'language' => $language
                        ] );
                    }
                }
            }
        }

        public function ts_delete_location_nested( $post_id ) {
            if ( get_post_type( $post_id ) == 'location' ) {
                global $wpdb;
                $ns = new Nested_set();
                $ns->setControlParams( $wpdb->prefix . $this->table_nested );
                $parent      = (int) get_post_field( 'post_parent', $post_id );
                $node_parent = $ns->getNodeWhere( 'location_id = ' . $parent );
                $node        = $ns->getNodeWhere( 'location_id = ' . $post_id );
                if ( !empty( $node ) ) {
                    $node_child = $ns->getFirstChild( $node );
                    if ( !empty( $node_child ) ) {
                        $ns->setNodeAsLastChild( $node_child, $node_parent );
                    }
                    $node = $ns->getNodeWhere( 'location_id = ' . $post_id );
                    if ( !empty( $node ) ) {
                        $ns->deleteNode( $node );
                    }
                }
            }
        }
    }

    new TSUpgradeData;
}
