<?php

class Nested_set {

    private $table_name;
    private $left_column_name;
    private $right_column_name;
    private $primary_key_column_name;
    private $parent_column_name;
    private $text_column_name;
    private $db;

    /**
     * Constructor
     * @access	public
     */
    public function __construct()	{
        global $wpdb; // to access CI resources, use $CI instead of $this
        $this->db = $wpdb;
    }

    public function setControlParams($table_name, $left_column_name = 'left_key', $right_column_name = 'right_key', $primary_key_column_name = 'id', $parent_column_name = 'parent_id', $text_column_name = 'name') {
        $this->table_name              = $table_name;
        $this->left_column_name        = $left_column_name;
        $this->right_column_name       = $right_column_name;
        $this->primary_key_column_name = $primary_key_column_name;
        $this->parent_column_name      = $parent_column_name;
        $this->text_column_name        = $text_column_name;
    }

    public function getNodeLevel($node) {
        $leftcol	=	   $this->left_column_name;
        $rightcol   =	   $this->right_column_name;
        $leftval	= (int) $node[$leftcol];
        $rightval   = (int) $node[$rightcol];

        $sql = "SELECT COUNT(id) FROM {$this->table_name} WHERE {$leftcol} < {$leftval} AND {$rightcol} > {$rightval} ";

        $result = $this->db->get_var( $sql );

        return $result;
    }


}
