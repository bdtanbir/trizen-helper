<?php

class TS_Single_Hotel {
    static $_inst;
    public function __construct(){
        add_action('wp_ajax_ts_filter_room_ajax', array($this, '__singleRoomFilterAjax'));
        add_action('wp_ajax_nopriv_ts_filter_room_ajax', array($this, '__singleRoomFilterAjax'));
        // Load instagram ajax
        add_action('wp_ajax_load_instagram', [$this, 'ts_load_instagram_images']);
        add_action('wp_ajax_nopriv_load_instagram', [$this, 'ts_load_instagram_images']);
    }

    public function startInjectQuery(){
        add_action('pre_get_posts', array($this, '__changeSearchRoomArgs'));
        add_filter( 'posts_where', array($this, '__changeWhereQuery' ));
        add_action( 'posts_fields', array( $this, '__changePostField' ));
        add_filter( 'posts_join', array( $this, '__changeJoinQuery' ));
        add_filter('posts_groupby', array($this, '__changeGroupBy'));
    }
    public function endInjectQuery(){
        remove_action('pre_get_posts', array($this, '__changeSearchRoomArgs'));
        remove_filter( 'posts_where', array($this, '__changeWhereQuery' ));
        remove_action( 'posts_fields', array( $this, '__changePostField' ));
        remove_filter( 'posts_join', array( $this, '__changeJoinQuery' ));
        remove_filter('posts_groupby', array($this, '__changeGroupBy'));
    }


    public function setQueryRoomSearch() {
        global $wp_query, $ts_search_query;
        if (TravelHelper::is_wpml()) {
            $current_lang = TravelHelper::current_lang();
            $main_lang = TravelHelper::primary_lang();
            global $sitepress;
            $sitepress->switch_lang($main_lang, true);
        }
        $this->startInjectQuery();
        $paged = get_query_var('paged') ? get_query_var('paged'): '1';
        $args = [
            'post_type'   => 'hotel_room',
            's'           => '',
            'post_status' => [ 'publish' ],
            'paged'       => $paged,
        ];
        query_posts( $args );
        $ts_search_query = $wp_query;
        $this->endInjectQuery();
    }
    static function inst(){
        if ( !self::$_inst ) {
            self::$_inst = new self();
        }
        return self::$_inst;
    }
}

TS_Single_Hotel::inst();