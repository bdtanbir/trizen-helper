<?php
class TS_Posts_Model extends TS_Model{
    protected $ignore_create_table=true;
    protected $table_name='posts';
    protected $table_key='ID';

    protected static $_inst;

    public function __construct()
    {
        parent::__construct();
    }

    public static function inst()
    {
        if(!self::$_inst) self::$_inst=new self();
        return self::$_inst;
    }



}

TS_Posts_Model::inst();