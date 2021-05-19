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

    public function insertNewTree($extrafields = array()) {
        $sql = "SELECT MAX({$this->right_column_name}) AS lft FROM {$this->table_name}";
        $result = $this->db->get_row($sql, ARRAY_A);
        $node = array(
            $this->parent_column_name => 0,
            $this->left_column_name  => $result['lft'] + 1,
            $this->right_column_name => $result['lft'] + 2,
        );
        $this->_setNewNode($node, $extrafields);
        return $this->getNodeWhereLeft($node[$this->left_column_name]);
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

    private function _setParent($node, $parent_id) {
        $primarykeycol	=	$this->primary_key_column_name;
        $parentcol		=	$this->parent_column_name;
        $primarykeyval	=	(int) $node[$primarykeycol];
        $parentval		=	(int) $node[$parentcol];
        if($parentval != $parent_id) {
            $data = array(
                $parentcol => $parent_id,
            );
            $where = array(
                $primarykeycol => $primarykeyval
            );
            $this->db->update($this->table_name, $data, $where);
        }
    }

    public function setNodeAsLastChild($node, $target) {
        $this->_setParent($node, $target[$this->primary_key_column_name]);
        return $this->_moveSubtree($node, $target[$this->right_column_name]);
    }

    private function _moveSubtree($node, $targetValue) {
        $sizeOfTree = $node[$this->right_column_name] - $node[$this->left_column_name] + 1;
        $this->_modifyNode($targetValue, $sizeOfTree);
        if($node[$this->left_column_name] >= $targetValue) {
            $node[$this->left_column_name] += $sizeOfTree;
            $node[$this->right_column_name] += $sizeOfTree;
        }
        $newpos = $this->_modifyNodeRange($node[$this->left_column_name], $node[$this->right_column_name], $targetValue - $node[$this->left_column_name]);
        $this->_modifyNode($node[$this->right_column_name]+1, - $sizeOfTree);
        if($node[$this->left_column_name] <= $targetValue) {
            $newpos[$this->left_column_name] -= $sizeOfTree;
            $newpos[$this->right_column_name] -= $sizeOfTree;
        }
        return $newpos;
    }

    private function _modifyNode($node_int, $changeVal) {
        $leftcol	=	$this->left_column_name;
        $rightcol	=	$this->right_column_name;

        $leftval = $leftcol . '+' . (int) $changeVal;
        $sql = "UPDATE {$this->table_name} SET {$leftcol} = {$leftval} WHERE {$leftcol} >= ". (int) $node_int . " ";
        $this->db->query($sql);

        $rightval = $rightcol . '+' . (int) $changeVal;
        $sql = "UPDATE {$this->table_name} SET {$rightcol} = {$rightval} WHERE {$rightcol} >= ". (int) $node_int . " ";
        $this->db->query( $sql );
    }

    /**
     * Gets an array of nodes
     *
     * @param mixed $whereArg String or array of where arguments
     * @param string $orderArg Orderby argument
     * @param integer $limit_start Number of rows to retrieve
     * @param mixed $limit_offset Row to start retrieving from
     * @return array Returns array of nodes found
     */
    public function getNodesWhere($whereArg = '"1"="1"', $orderArg = '', $limit_start = 0, $limit_offset = null) {
        $resultNode[$this->left_column_name]	=	$resultNode[$this->right_column_name]	=	0;
        $sql = "SELECT * FROM {$this->table_name} WHERE {$whereArg} ";
        if($orderArg) {
            $sql .= " ORDER BY {$orderArg} ";
        }
        if($limit_start || $limit_offset) {
            $sql .= " LIMIT {$limit_start}, {$limit_offset} ";
        }
        $result = $this->db->get_results($sql, ARRAY_A);
        $resultNodes = array();
        if( !empty($result) )
        {
            $resultNodes = $result;
        }
        return $resultNodes;
    }

    /**
     * Returns the root nodes
     * @return array $resultNode The node returned
     */
    public function getRootNodes() {
        return $this->getNodesWhere($this->parent_column_name . ' = 0 ');
    }

    /**
     * Same as insertNewChild except the new node is added as the last child
     * @param array $parentNode The node array of the parent to use
     * @param array $extrafields An associative array of fieldname=>value for the other fields in the recordset
     * @return array $childNode An associative array representing the new node
     */
    public function appendNewChild($parentNode, $extrafields = array()) {
        $childNode[$this->parent_column_name]	=	$parentNode[$this->primary_key_column_name];
        $childNode[$this->left_column_name]		=	$parentNode[$this->right_column_name];
        $childNode[$this->right_column_name]	=	$parentNode[$this->right_column_name]+1;

        $this->_modifyNode($childNode[$this->left_column_name], 2);
        $this->_setNewNode($childNode, $extrafields);

        return $this->getNodeWhereLeft($childNode[$this->left_column_name]);
    }

    private function _setNewNode($node, $extrafields) {
        $parentcol	=		$this->parent_column_name;
        $leftcol	=		$this->left_column_name;
        $rightcol	=		$this->right_column_name;
        $parentval	= (int) $node[$parentcol];
        $leftval	= (int) $node[$leftcol];
        $rightval	= (int) $node[$rightcol];
        $data = array(
            $parentcol => $parentval,
            $leftcol => $leftval,
            $rightcol => $rightval,
        );
        if(is_array($extrafields) && !empty($extrafields)) $data = array_merge($data, $extrafields);
        $result = $this->db->insert($this->table_name, $data);
        if(!$result) {
            $this->log_message('error', 'Node addition failed for ' . $leftval . ' - ' . $rightval);
        }
        return $result;
    }

    public function getFirstChild($parentNode) {
        return $this->getNodeWhere($this->left_column_name . ' = ' . ($parentNode[$this->left_column_name]+1));
    }

    /**
     * Returns the node identified by the given left value
     * @param integer $leftval The left value to use to select the node
     * @return array $resultNode The node returned
     */
    public function getNodeWhereLeft($leftval) {
        return $this->getNodeWhere($this->left_column_name . ' = ' . $leftval);
    }

    /**
     * Selects the first node to match the given where clause argument
     * @param mixed $whereArg Any valid SQL to follow the WHERE keyword in an SQL statement
     * @return array $resultNode The node returned from the query
     */
    public function getNodeWhere($whereArg = '"1"="1"') {
        $resultNode[$this->left_column_name]	=	$resultNode[$this->right_column_name]	=	0;
        $sql = "SELECT * FROM {$this->table_name} WHERE {$whereArg}";
        $result = $this->db->get_results($sql, ARRAY_A);
        $resultNode = array();
        if( !empty($result)) {
            $resultNode = array_shift($result); // assumes CI standard $row[0] = first row
        }
        return $resultNode;
    }

    /**
     * Empties the table currently in use - use with extreme caution!
     * @return boolean
     */
    public function deleteTree() {
        return $this->db->delete($this->table_name);
    }

    public function deleteNode($node) {
        $leftanchor		=	$node[$this->left_column_name];
        $leftcol		=	$this->left_column_name;
        $rightcol		=	$this->right_column_name;
        $leftval		=	$node[$this->left_column_name];
        $rightval		=	$node[$this->right_column_name];
        $sql            = "DELETE FROM {$this->table_name} WHERE {$leftcol} >= {$leftval} AND {$rightcol} <= {$rightval}";
        $this->db->query( $sql );
        $this->_modifyNode($node[$this->right_column_name] + 1, $node[$this->left_column_name] - $node[$this->right_column_name] - 1);
        return $this->getNodeWhere($leftcol . ' < ' . $leftanchor . ' ORDER BY ' . $leftcol  . ' DESC');
    }

    public function log_message($message = ''){

    }
    private function _modifyNodeRange($lowerbound, $upperbound, $changeVal) {
        $leftcol	=	$this->left_column_name;
        $rightcol	=	$this->right_column_name;

        $leftval = $leftcol . '+' . (int) $changeVal;
        $sql     = "UPDATE {$this->table_name} SET {$leftcol} = {$leftval} WHERE {$leftcol} >= ". (int) $lowerbound . " AND {$leftcol} <= ". (int) $upperbound . " ";
        $this->db->query($sql);

        $rightval = $rightcol . '+' . (int) $changeVal;
        $sql      = "UPDATE {$this->table_name} SET {$rightcol} = {$rightval} WHERE {$rightcol} >= ". (int) $lowerbound . " AND {$rightcol} <= ". (int) $upperbound . " ";
        $this->db->query($sql);

        $retArray = array(
            $this->left_column_name  =>  $lowerbound+$changeVal,
            $this->right_column_name =>  $upperbound+$changeVal
        );
        return $retArray;
    }


}
