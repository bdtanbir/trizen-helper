<?php
class TS_Availability extends TS_Model {
    protected $table_version = '1.0';
    protected $ignore_create_table=true;
    protected $table_name    = 'ts_availability';
//    protected $table_key='id';

    protected static $_inst;

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {

    }

    public static function inst() {
        if(!self::$_inst) self::$_inst=new self();
        return self::$_inst;
    }
}

TS_Availability::inst();

