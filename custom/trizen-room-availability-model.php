<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
class TS_Model {

	protected static $_inst;

	/**
	 * Name of the Table
	 * @var bool
	 * @since 1.0
	 */
	protected $table_name = FALSE;


	/**
	 * Table version to upgrade
	 * @var bool
	 * @since 1.0
	 */
	protected $table_version = '1.0';


	/**
	 * All Columns
	 * @var array
	 * @since 1.0
	 */
	protected $columns = array();


	/**
	 * Check if table meta is created
	 * @var bool
	 * @since 1.0
	 */
	protected $is_ready = FALSE;


	/**
	 * Identity Column Name for search
	 * @var string
	 * @since 1.0
	 */
	protected $table_key = 'id';


	/**
	 * If dont want to create meta table, just set it to TRUE
	 * @var bool
	 * @since 1.0
	 */
	protected $ignore_create_table = FALSE;


	protected $_where_query  = array();
	protected $_join_query   = array();
	protected $_select_query = array();
	protected $_order_query  = array();
	protected $_limit_query  = array();
	protected $_last_query   = array();
	protected $_last_result  = array();
	protected $_groupby      = array();
	protected $_having       = array();
	protected $_like_query   = array();




	function limit($key, $value = 0)
	{
		$this->_limit_query[0] = $key;
		$this->_limit_query[1] = $value;

		return $this;
	}

	/**
	 * Run Get Query and store the result
	 *
	 * @since 1.0
	 * @author dannie
	 *
	 * @param bool $limit
	 * @param bool $offset
	 * @param string $result_type
	 * @return $this
	 */
	function get($limit=FALSE,$offset=FALSE,$result_type=ARRAY_A)
	{
		if($limit){
			$this->limit($limit,$offset);
		}
		global $wpdb;
		$query = $this->_get_query();
		$this->_last_query = $query;
		$this->_last_result = $wpdb->get_results($query, $result_type);

		return $this;
	}


	/**
	 * Add Where Clause to current Query
	 *
	 * @author dannie
	 * @since 1.0
	 *
	 * @param $key
	 * @param bool|FALSE $value
	 * @param $raw_where bool
	 * @return $this
	 */
	function where($key, $value = FALSE, $raw_where = FALSE)
	{
		if (is_array($key) and !empty($key)) {
			foreach ($key as $k1 => $v1) {
				$this->where($k1, $v1, $raw_where);
			}

			return $this;
		}
		if (is_string($key)) {
			$this->_where_query[] = array(
				'key'    => $key,
				'value'  => $value,
				'clause' => 'and',
				'is_raw' => $raw_where
			);
		}

		return $this;
	}




	/**
	 * Build the query
	 *
	 * @since 1.0
	 * @author dannie
	 *
	 * @return bool|string
	 */
	public function _get_query()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;

		$select = FALSE;
		if (!empty($this->_select_query)) {

			$select = implode(',', $this->_select_query);
		} else {
			$select = '*';
		}

		$join = FALSE;
		if (!empty($this->_join_query)) {
			foreach ($this->_join_query as $j) {
				$j = wp_parse_args($j, array(
					'tale'    => FALSE,
					'on'      => FALSE,
					'keyword' => FALSE
				));

				if (!$j['table'] or !$j['on']) continue;

				$table = $wpdb->prefix . $j['table'];

				// Replace JOIN ON to add Table prefix to Table Name
				$j['on'] = str_replace($j['table'], $table, $j['on']);
				$j['on'] = str_replace($this->table_name, $table_name, $j['on']);

				$join .= ' ' . $j['keyword'] . ' JOIN ' . $table . ' ON ' . $j['on'];
			}
		}

		$where = ' WHERE 1=1 ';
		if (!empty($this->_where_query)) {

			foreach ($this->_where_query as $key => $value) {
				$value = wp_parse_args($value, array(
					'key'    => FALSE,
					'value'  => FALSE,
					'clause' => 'and',
					'is_raw' => FALSE
				));
				if (!$value['is_raw']) {
					$last = substr($value['key'], -1);
					switch ($last) {
						case ">":
						case "<":
						case "=":
							$where .= $wpdb->prepare(' ' . $value['clause'] . ' ' . $value['key'] . '%s ', array($value['value']));
							break;
						default:
							$where .= $wpdb->prepare(' ' . $value['clause'] . ' ' . $value['key'] . '=%s ', array($value['value']));
							break;

					}
				} else {
					$where .= ' ' . $value['clause'] . ' ' . $value['key'];
				}

			}
		}

		// Like
		if (!empty($this->_like_query)) {

			foreach ($this->_like_query as $key => $value) {
				$value = wp_parse_args($value,
					array(
						'key'    => FALSE,
						'value'  => FALSE,
						'clause' => 'AND',
						'format' => 'both'
					));
				switch ($value['format']) {
					case "before":
						$where .= ' ' . $value['clause'] . ' ' . $value['key'] . " LIKE '%" . $wpdb->_real_escape($value['value']) . "' ";
						break;
					case "after":
						$where .= ' ' . $value['clause'] . ' ' . $value['key'] . " LIKE '" . $wpdb->_real_escape($value['value']) . "%' ";
						break;

					default:
						$where .= ' ' . $value['clause'] . ' ' . $value['key'] . " LIKE '%" . $wpdb->_real_escape($value['value']) . "%' ";
						break;

				}
			}
		}


		$order = FALSE;
		if (!empty($this->_order_query)) {
			$order = ' ORDER BY ';
			foreach ($this->_order_query as $k => $v) {
				$order .= ' ' . $k . ' ' . $v . ',';
			}

			$order = substr($order, 0, -1);
		}

		$groupby = FALSE;

		if (!empty($this->_groupby)) {
			$groupby = ' GROUP BY ';
			foreach ($this->_groupby as $k => $v) {
				$groupby .= ' ' . $v . ',';
			}

			$groupby = substr($groupby, 0, -1);

			$having = FALSE;
			if (!empty($this->_having)) {
				$having .= ' HAVING ';
				foreach ($this->_having as $k => $v) {
					$having .= ' ' . $v . ',';
				}

				$having = substr($having, 0, -1);

				$groupby .= ' ' . $having;
			}

		}

		$limit = FALSE;
		if (!empty($this->_limit_query[0])) {
			$limit = ' LIMIT ';

			$offset = !empty($this->_limit_query[1]) ? $this->_limit_query[1] : 0;

			$limit .= $offset . ',' . $this->_limit_query[0];

		}

		if ($select) {
			$query = "SELECT  SQL_CALC_FOUND_ROWS {$select} FROM {$table_name} ";
			//$query=$wpdb->prepare($query,array($select));
			$query .= $join;
			$query .= $where;
			$query .= $groupby;
			$query .= $order;
			$query .= $limit;

			return $query;
		}

		return FALSE;
	}


	/**
	 * Clear Query Condition after each query
	 *
	 * @since 1.0
	 * @author dannie
	 */
	public function _clear_query()
	{
		$this->_where_query  = array();
		$this->_select_query = array();
		$this->_order_query  = array();
		$this->_limit_query  = array();
		$this->_join_query   = array();
		$this->_groupby      = array();
		$this->_having       = array();
	}

	/**
	 * Return first row of last query result
	 *
	 * @author dannie
	 * @since 1.0
	 *
	 * @return bool|array
	 */
	function row($key=false)
	{
		$data = isset($this->_last_result[0]) ? $this->_last_result[0] : FALSE;
		$this->_clear_query();

		if(!empty($key)) return isset($data[$key])?$data[$key]:null;

		return $data;

	}

	/**
	 * Run Update Query
	 *
	 * @since 1.0
	 * @author dannie
	 *
	 * @param array $data
	 * @return bool|false|int
	 */
	function update($data = array())
	{
		if (empty($data)) {
			return FALSE;
		}
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;

		$where = FALSE;
		if (!empty($this->_where_query)) {
			$where=' WHERE 1=1 ';
			foreach ($this->_where_query as $key => $value) {
				$value = wp_parse_args($value, array(
					'key'    => FALSE,
					'value'  => FALSE,
					'clause' => 'and',
					'is_raw' => FALSE
				));
				if (!$value['is_raw']) {
					$last = substr($value['key'], -1);
					switch ($last) {
						case ">":
						case "<":
						case "=":
							$where .= $wpdb->prepare(' ' . $value['clause'] . ' ' . $value['key'] . '%s ', array($value['value']));
							break;
						default:
							$where .= $wpdb->prepare(' ' . $value['clause'] . ' ' . $value['key'] . '=%s ', array($value['value']));
							break;

					}
				} else {
					$where .= ' ' . $value['clause'] . ' ' . $value['key'];
				}
			}
		}

		$set = FALSE;
		foreach ($data as $key => $value) {
			$prepare='%s';
			if(is_float($value)){
				$prepare='%f';
			}elseif(is_integer($value)){
				$prepare='%d';
			}
			$set .= $wpdb->prepare("$key={$prepare},",$value);
		}
		$set    = substr($set, 0, -1);
		$query  = "UPDATE " . $table_name . " SET " . $set;
		$query .= $where;
		$this->_last_query = $query;
		$this->_clear_query();
		return $wpdb->query($query);
	}

	/**
	 * Run Insert Query
	 *
	 * @since 1.0
	 * @author dannie
	 *
	 * @param array $data
	 * @return bool|int
	 */
	function insert($data = array())
	{
		if (empty($data)) {
			return FALSE;
		}
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;

		$set         = FALSE;
		$set_data    = array();
		$set_columns = FALSE;

		foreach ($data as $key => $value) {
			//if(!array_key_exists($key,$this->columns)) continue;
			$set        .= "%s,";
			$set_data[]  = $value;
			$set_columns.=$key.',';
		}

		$set = substr($set, 0, -1);
		$set_columns = substr($set_columns, 0, -1);

		$query = "INSERT INTO " . $table_name . " ({$set_columns}) VALUES ($set)";

		$query = $wpdb->prepare($query, $set_data);

		$this->_last_query = $query;
		$wpdb->query($query);

		$this->_clear_query();
		return $wpdb->insert_id;

	}

	function insertOrUpdate($data)
	{
		$data = wp_parse_args($data,array(
			'post_id'     => '',
			'check_in'    => '',
			'check_out'   => '',
			'price'       => '',
			'status'      => '',
			'is_base'     => 0,
//			'adult_price' => '',
//			'child_price' => '',
		));
		$where = [
			'post_id'=>$data['post_id'],
			'check_in'=>$data['check_in'],
		];
		$check = $this->where($where)->get(1)->row();
		if($check)
		{
			unset($data['post_id']);
			unset($data['check_in']);
			return $this->where($where)->update($data);
		}else{
//			$data['adult_number']=get_post_meta($data['post_id'],'adult_number',true);
//			$data['child_number']=get_post_meta($data['post_id'],'child_number',true);
//			$data['allow_full_day']=get_post_meta($data['post_id'],'allow_full_day',true);
			$data['number']=get_post_meta($data['post_id'],'trizen_hotel_room_number',true);

			return $this->insert($data);
		}
	}
	public static function inst()
	{
		if(!self::$_inst) self::$_inst=new self();
		return self::$_inst;
	}
}
TS_Model::inst();