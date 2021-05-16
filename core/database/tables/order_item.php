<?php
class TS_Order_Item_Model extends TS_Model {
    protected $table_version = '1.0';
    protected $ignore_create_table=true;
    protected $table_name='ts_order_item_meta';
    protected $table_key='id';

    protected static $_inst;

    public function __construct()
    {
        parent::__construct();
    }

    public static function getOrderByID($order_id){
        $res = TS_Order_Item_Model::inst()->where('order_item_id', $order_id)->get()->result();
        return $res;
    }


    public static function inst() {
        if(!self::$_inst) self::$_inst=new self();
        return self::$_inst;
    }
}

TS_Order_Item_Model::inst();