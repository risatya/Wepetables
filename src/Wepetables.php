<?php
namespace wepe;

/**
	* Wepetables
	*
	* This is a wrapper class/library inspired and based on Ignited Datatables
	* found at https://github.com/IgnitedDatatables/Ignited-Datatables for CodeIgniter 3.x
	*
	* @package    CodeIgniter 4.x
	* @subpackage libraries
	* @category   library
	* @version    1.0 <beta>
	* @author     Bagus W.P <wenpiee@gmail.com / https://wepedev.web.id>
	*/

class Wepetables
{
	/**
	* Global container variables for chained argument results
	*
	*/
	private $db;
	private $table;
	private $select         = array();
	private $joins          = array();
	private $columns        = array();
	private $where          = array();
	private $or_Where       = array();
	private $where_In       = array();
	private $like           = array();
	private $or_Like        = array();
	private $groupBy        = array();
	private $orderBy        = array();
	private $add_columns    = array();
	private $added_columns  = array();
	private $edit_columns   = array();
	private $unset_columns  = array();
	private $request;

	public function __construct()
	{
		$this->db = \Config\Database::connect();
		$this->request = \Config\Services::request();
	}

	/**
	* Generates the SELECT portion of the query
	*
	* @param string $columns
	* @return mixed
	*/
	public function select($columns)
	{
		foreach ($this->explode(',', $columns) as $val) {
			$column = trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$2', $val));
			$column = preg_replace('/.*\.(.*)/i', '$1', $column); // get name after `.`
			$this->columns[] =  $column;
			$this->select[$column] =  trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$1', $val));
		}

		$this->select = $columns;
		return $this;
	}

	/**
	* Generates the FROM portion of the query
	*
	* @param string $table
	* @return mixed
	*/
	public function from($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	* Generates the JOIN portion of the query
	*
	* @param string $table
	* @param string $fk
	* @param string $type
	* @return mixed
	*/
	public function join($table, $fk, $type = NULL)
	{
		$this->joins[] = array($table, $fk, $type);
		return $this;
	}

	/**
	* Generates the WHERE portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @return mixed
	*/
	public function where($key_condition, $val = NULL)
	{
		$this->where[$key_condition] = $val;
		return $this;
	}

	/**
	* Generates the WHERE portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @return mixed
	*/
	public function orWhere($key_condition, $val = NULL)
	{
		$this->or_Where[$key_condition] = $val;
		return $this;
	}

	/**
	* Generates the WHERE IN portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @return mixed
	*/
	public function whereIn($key_condition, $val = NULL)
	{
		$this->where_In[$key_condition] = $val;
		return $this;
	}

	/**
	* Generates a %LIKE% portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @param bool $side
	* @return mixed
	*/
	public function like($key_condition, $val = NULL, $side = 'both')
	{
		$this->like[] = array($key_condition, $val, $side);
		return $this;
	}

	/**
	* Generates a OR %LIKE% portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @param bool $side
	* @return mixed
	*/
	public function orLike($key_condition, $val = NULL, $side = 'both')
	{
		$this->or_Like[] = array($key_condition, $val, $side);
		return $this;
	}

	/**
	* Generates the ORDER BY portion of the query
	*
	* @param mixed $key_condition
	* @param string $val
	* @return mixed
	*/
	public function orderBy($key_condition, $val = 'ASC'){
		$this->orderBy[] = array($key_condition, $val);
		return $this;
	}

	/**
	* Generates a custom GROUP BY portion of the query
	*
	* @param string $val
	* @return mixed
	*/
	public function groupBy($val)
	{
		$this->groupBy[] = $val;
		return $this;
	}

	/**
	* Sets additional column variables for adding custom columns
	*
	* @param string $column
	* @param string $content
	* @param string $match_replacement
	* @return mixed
	*/
	public function add_column($column, $content, $match_replacement = NULL)
	{
		$this->add_columns[$column] = array('content' => $content, 'replacement' => $this->explode(',', $match_replacement));

		$idx_select = count($this->explode(',', $this->select));
		$idx_added = count($this->added_columns);
		$idx = (($idx_select > 0) ? $idx_select-1 : $idx_select) + $idx_added;
		$this->added_columns[$idx+1] = $column;
		return $this;
	}

	/**
	* Sets additional column variables for editing columns
	*
	* @param string $column
	* @param string $content
	* @param string $match_replacement
	* @return mixed
	*/
	public function edit_column($column, $content, $match_replacement)
	{
		$this->edit_columns[$column][] = array('content' => $content, 'replacement' => $this->explode(',', $match_replacement));
		return $this;
	}

	/**
	* Unset column
	*
	* @param string $column
	* @return mixed
	*/
	public function unset_column($column)
	{
		$columns = $this->explode(',', $column);

		foreach ($columns as $key) {
			$this->unset_columns[trim($key)] = 1;
		}
		
		return $this;
	}

	/**
	* Builds all the necessary query segments and performs the main query based on results set from chained statements
	*
	* @param string $output
	* @param string $charset
	* @return string
	*/
	public function generate($output = 'json', $charset = 'UTF-8')
	{

		if(strtolower($output) == 'json')
			return $this->produce_output(strtolower($output), strtolower($charset));
	}

	/**
	* Builds an encoded string data. Returns JSON by default, and an array of aaData if output is set to raw.
	*
	* @param string $output
	* @param string $charset
	* @return mixed
	*/
	private function produce_output($output, $charset)
	{
		$aaData = array();
		$rResult = $this->get_display_result();

		if($output == 'json')
		{
			$iTotal = $this->get_total_results();
			$iFilteredTotal = $this->get_total_results(TRUE);
		}

		foreach ($rResult->getResultArray() as $row_key => $row_val) {
			$aaData[$row_key] =  ($this->check_cType()) ? $row_val : array_values($row_val);

			foreach ($this->add_columns as $field => $val)
				if ($this->check_cType())
					$aaData[$row_key][$field] = $this->exec_replace($val, $aaData[$row_key]);
				else
					$aaData[$row_key][] = $this->exec_replace($val, $aaData[$row_key]);

			foreach ($this->edit_columns as $modkey => $modval)
				foreach ($modval as $val)
					$aaData[$row_key][($this->check_cType()) ? $modkey : array_search($modkey, $this->columns)] = $this->exec_replace($val, $aaData[$row_key]);

			$aaData[$row_key] = array_diff_key($aaData[$row_key], $this->unset_columns);

			if (!$this->check_cType())
				$aaData[$row_key] = array_values($aaData[$row_key]);
		}

		if($output == 'json')
		{
			$search = $this->request->getPost('search');
			$sSearch = $this->db->escapeLikeString(trim($search['value']));
			$sOutput = array(
				'draw'                => intval($this->request->getPost('draw')),
				'recordsTotal'        => $iTotal,
				'recordsFiltered'     => $iFilteredTotal,
				'data'                => $aaData
			);

			if($charset == 'utf-8')
				return json_encode($sOutput);
			else
				return $this->jsonify($sOutput);
		}
		else
			return array('aaData' => $aaData);
	}

	/**
	* Compiles the select statement based on the other functions called and runs the query
	*
	* @return mixed
	*/
	private function get_display_result()
	{
		$builder = $this->db->table($this->table);
		if ($this->select != null) $builder->select($this->select);

		if ($this->joins != null) $this->setJoin($builder);

		if ($this->where != null) $builder->where($this->where);
		if ($this->or_Where != null) $builder->orWhere($this->or_Where);

		if ($this->like != null) $this->setLike($builder);
		if ($this->or_Like != null) $this->setOrLike($builder);

		if ($this->orderBy != null) $this->setOrderBy($builder);

		if ($this->groupBy != null) $builder->groupBy($this->groupBy);

		/*FILTERING*/
		$sWhere = '';
		$mColArray = $this->request->getPost('columns');
		$search = $this->request->getPost('search');
		$sSearch = $this->db->escapeLikeString(trim($search['value']));
		$columns = array_values(array_diff($this->columns, $this->unset_columns));
		if($sSearch != ''){
			for($i = 0; $i < count($mColArray); $i++){
				$arrExist = array_key_exists($mColArray[$i]['data'], $this->added_columns);
				if ($mColArray[$i]['searchable'] == 'true' && $arrExist==FALSE){
					if($this->check_cType())
						$builder->like($mColArray[$i]['data'], $sSearch);
					else 
						$builder->orLike($this->explode(',', $this->select)[$i], $sSearch);
				}
			}
		}

		/*ORDERING*/
		if ($this->request->getPost('order')){
			foreach ($this->request->getPost('order') as $key)
				$arrExist = array_key_exists($mColArray[$key['column']]['data'], $this->added_columns);
				if ($mColArray[$key['column']]['orderable'] == 'true' && $arrExist==FALSE)
					if($this->check_cType())
						$builder->orderBy($mColArray[$key['column']]['data'], $key['dir']);
					else
						$builder->orderBy($this->columns[$key['column']] , $key['dir']);
		}

		/*PAGING*/
		$iStart = $this->request->getPost('start');
		$iLength = $this->request->getPost('length');
		$builder->limit($iLength, ($iStart) ? $iStart : 0);

		return $builder->get();
	}

	/**
	* Get result count
	*
	* @return integer
	*/
	private function get_total_results($filtering = FALSE)
	{
		$builder = $this->db->table($this->table);
		if ($this->select != null) $builder->select($this->select);

		if ($this->joins != null) $this->setJoin($builder);

		if ($this->where != null) $builder->where($this->where);
		if ($this->or_Where != null) $builder->orWhere($this->or_Where);

		if ($this->like != null) $this->setLike($builder);
		if ($this->or_Like != null) $this->setOrLike($builder);

		if ($this->orderBy != null) $this->setOrderBy($builder);

		if ($this->groupBy != null) $builder->groupBy($this->groupBy);

		/*FILTERING*/
		if($filtering==TRUE){
			$sWhere = '';
			$mColArray = $this->request->getPost('columns');
			$search = $this->request->getPost('search');
			$sSearch = $this->db->escapeLikeString(trim($search['value']));
			$columns = array_values(array_diff($this->columns, $this->unset_columns));
			if($sSearch != ''){
				for($i = 0; $i < count($mColArray); $i++){
					$arrExist = array_key_exists($mColArray[$i]['data'], $this->added_columns);
					if ($mColArray[$i]['searchable'] == 'true' && $arrExist==FALSE){
						if($this->check_cType())
							$builder->like($mColArray[$i]['data'], $sSearch);
						else 
							$builder->orLike($this->explode(',', $this->select)[$i], $sSearch);
					}
				}
			}
		}

		$num_rows = count($builder->get()->getResultArray());
		return $num_rows;
	}

	/**
	* Execute the JOIN portion of the query
	*
	* @return mixed
	*/
	private function setJoin($builder)
	{
		for ($i = 0; $i < count($this->joins); $i++) {
			$builder->join($this->joins[$i][0], $this->joins[$i][1], $this->joins[$i][2]);
		}
		return $builder;
	}

	/**
	* Execute the LIKE portion of the query
	*
	* @return mixed
	*/
	private function setLike($builder)
	{
		for ($i = 0; $i < count($this->like); $i++) {
			$builder->like($this->like[$i][0], $this->like[$i][1], $this->like[$i][2]);
		}
		return $builder;
	}

	/**
	* Execute the OR LIKE portion of the query
	*
	* @return mixed
	*/
	private function setOrLike($builder)
	{
		for ($i = 0; $i < count($this->or_Like); $i++) {
			$builder->orLike($this->or_Like[$i][0], $this->or_Like[$i][1], $this->or_Like[$i][2]);
		}
		return $builder;
	}

	/**
	* Execute the ORDER BY portion of the query
	*
	* @return mixed
	*/
	private function setOrderBy($builder){
		for ($i = 0; $i < count($this->orderBy); $i++) {
			$builder->orderBy($this->orderBy[$i][0], $this->orderBy[$i][1]);
		}
		return $builder;
	}

	/**
	* Return the difference of open and close characters
	*
	* @param string $str
	* @param string $open
	* @param string $close
	* @return string $retval
	*/
	private function balanceChars($str, $open, $close)
	{
		$openCount = substr_count($str, $open);
		$closeCount = substr_count($str, $close);
		$retval = $openCount - $closeCount;
		return $retval;
	}

	/**
	* Explode, but ignore delimiter until closing characters are found
	*
	* @param string $delimiter
	* @param string $str
	* @param string $open
	* @param string $close
	* @return mixed $retval
	*/
	private function explode($delimiter, $str, $open = '(', $close = ')')
	{
		$retval = array();
		$hold = array();
		$balance = 0;
		$parts = explode($delimiter, $str);

		foreach ($parts as $part) {
			$hold[] = $part;
			$balance += $this->balanceChars($part, $open, $close);

			if ($balance < 1) {
				$retval[] = implode($delimiter, $hold);
				$hold = array();
				$balance = 0;
			}
		}

		if (count($hold) > 0)
			$retval[] = implode($delimiter, $hold);

		return $retval;
	}

	/**
	* Runs callback functions and makes replacements
	*
	* @param mixed $custom_val
	* @param mixed $row_data
	* @return string $custom_val['content']
	*/
	private function exec_replace($custom_val, $row_data)
	{
		$replace_string = '';

		// Go through our array backwards, else $1 (foo) will replace $11, $12 etc with foo1, foo2 etc
		$custom_val['replacement'] = array_reverse($custom_val['replacement'], true);

		if (isset($custom_val['replacement']) && is_array($custom_val['replacement'])) {
			//Added this line because when the replacement has over 10 elements replaced the variable "$1" first by the "$10"
			$custom_val['replacement'] = array_reverse($custom_val['replacement'], true);
			foreach ($custom_val['replacement'] as $key => $val) {
				$sval = preg_replace("/(?<!\w)([\'\"])(.*)\\1(?!\w)/i", '$2', trim($val));

				if (preg_match('/(\w+::\w+|\w+)\((.*)\)/i', $val, $matches) && is_callable($matches[1])) {
					$func = $matches[1];
					$args = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[,]+/", $matches[2], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

					foreach ($args as $args_key => $args_val) {
						$args_val = preg_replace("/(?<!\w)([\'\"])(.*)\\1(?!\w)/i", '$2', trim($args_val));
						$args[$args_key] = (in_array($args_val, $this->columns)) ? ($row_data[($this->check_cType()) ? $args_val : array_search($args_val, $this->columns)]) : $args_val;
					}

					$replace_string = call_user_func_array($func, $args);
				} elseif (in_array($sval, $this->columns))
					$replace_string = $row_data[($this->check_cType()) ? $sval : array_search($sval, $this->columns)];
				else
					$replace_string = $sval;

				$custom_val['content'] = str_ireplace('$' . ($key + 1), $replace_string, $custom_val['content']);
			}
		}

		return $custom_val['content'];
	}

	/**
	* Check column type -numeric or column name
	*
	* @return bool
	*/
	private function check_cType()
	{
		$column = $this->request->getPost('columns');
		if (is_numeric($column[0]['data']))
			return FALSE;
		else
			return TRUE;
	}

	/**
	* Workaround for json_encode's UTF-8 encoding if a different charset needs to be used
	*
	* @param mixed $result
	* @return string
	*/
	private function jsonify($result = FALSE)
	{
		if(is_null($result))
			return 'null';

		if($result === FALSE)
			return 'false';

		if($result === TRUE)
			return 'true';

		if(is_scalar($result))
		{
			if(is_float($result))
			return floatval(str_replace(',', '.', strval($result)));

			if(is_string($result))
			{
			static $jsonReplaces = array(array('\\', '/', '\n', '\t', '\r', '\b', '\f', '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $result) . '"';
			}
			else
			return $result;
		}

		$isList = TRUE;

		for($i = 0, reset($result); $i < count($result); $i++, next($result))
		{
			if(key($result) !== $i)
			{
			$isList = FALSE;
			break;
			}
		}

		$json = array();

		if($isList)
		{
			foreach($result as $value)
			$json[] = $this->jsonify($value);

			return '[' . join(',', $json) . ']';
		}
		else
		{
			foreach($result as $key => $value)
			$json[] = $this->jsonify($key) . ':' . $this->jsonify($value);

			return '{' . join(',', $json) . '}';
		}
	}
}
