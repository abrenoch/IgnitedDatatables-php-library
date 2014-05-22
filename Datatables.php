<?php
  /**
  * Ignited Datatables Php Library
  *
  * This is a php class/library for Datatables(table plug-in for Jquery)
  *
  * @version    0.7a ( Php Library )
  * @author     Vincent Bambico <metal.conspiracy@gmail.com>
  *             Yusuf Ozdemir <yusuf@ozdemir.be>
  *
  * Php Library:
  * @Fork     : https://github.com/n1crack/IgnitedDatatables-native-php-version
  * @Discuss  : http://www.datatables.net/forums/discussion/5133/ignited-datatables-php-library
  * 
  * Codeigniter Library:
  * @Fork     : https://github.com/IgnitedDatatables/Ignited-Datatables
  * @Discuss  : http://ellislab.com/forums/viewthread/160896/
  * 
  */

  class Datatables
  {
    /**
    * Global container variables for chained argument results
    *
    */
    private $table;
    private $group          = array();
    private $order          = array();
    private $select         = array();
    private $joins          = array();
    private $columns        = array();
    private $where          = array();
    private $filter         = array();
    private $add_columns    = array();
    private $edit_columns   = array();
    private $unset_columns  = array();

    /**
    * Load ActiveRecord functions
    *
    */
    public function __construct($driver = 'mysql') 
    {
      require( dirname(__FILE__) . '/ActiveRecords/' . $driver . '.php' );
      $this->ar = new ActiveRecords;
    }

    /**
    * Database settings
    *
    */
    public function connect($config) 
    {
      $this->ar->connect($config);
    }

    /**
    * Get input data (post or get)
    *
    */
    private function input($field, $escape = TRUE)
    {
      if(isset($_POST['sEcho']) && isset($_POST[$field]))
        return ($escape == TRUE)? $this->ar->escape_db($_POST[$field]) : $_POST[$field];
      elseif(isset($_GET['sEcho']) && isset($_GET[$field]))
        return ($escape == TRUE)? $this->ar->escape_db($_GET[$field]) : $_GET[$field];
      else
        return FALSE;
    }

    /**
    * Generates the SELECT portion of the query
    *
    * @param string $columns
    * @param bool $backtick_protect
    * @return mixed
    */
    public function select($columns, $backtick_protect = TRUE)
    {
      foreach($this->explode(',', $columns) as $val)
      {
        $column = trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$2', $val));
        $this->columns[] =  $column;
        $this->select[$column] =  trim(preg_replace('/(.*)\s+as\s+(\w*)/i', '$1', $val));
      }
      $this->ar->select($this->explode(',', $columns), $backtick_protect);
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
      $this->ar->from($this->explode(',', $table));
      return $this;
    }

    /**
    * Generates a custom GROUP BY portion of the query
    *
    * @param string $val
    * @return mixed
    */
    public function group_by($val)
    {
      foreach($this->explode(',', $val) as $value)
        $this->group[] = $value;
      $this->ar->group_by($this->explode(',', $val));
      return $this;
    }

    /**
    * Generates the ORDER BY portion of the query
    *
    */
    public function order_by($orderby, $direction = '')
    {
      $this->order[] = $orderby;
      $direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), TRUE))? ' '.$direction : ' ASC';
      $this->ar->order_by($orderby,$direction);
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
      $this->ar->join($table, $fk, $type);
      return $this;
    }

    /**
    * Generates the WHERE portion of the query
    *
    * @param mixed $key_condition
    * @param string $val
    * @param bool $backtick_protect
    * @return mixed
    */
    public function where($key_condition, $val = NULL, $backtick_protect = TRUE)
    {
      $this->where[] = array($key_condition, $val, $backtick_protect);
      $this->ar->where($key_condition, $val, $backtick_protect);
      return $this;
    }

    /**
    * Adds LIKE portion to the query
    *
    * @param mixed $key_condition
    * @param string $val
    * @param bool $backtick_protect
    * @return mixed
    */
    public function like($key_condition, $val = NULL, $backtick_protect = TRUE)
    {
      $this->like[] = array($key_condition, $val, $backtick_protect);
      $this->ar->like($key_condition, $val, $backtick_protect);
      return $this;
    }


    /**
    * Generates the WHERE portion of the query
    *
    * @param mixed $key_condition
    * @param string $val
    * @param bool $backtick_protect
    * @return mixed
    */
    public function filter($key_condition, $val = NULL, $backtick_protect = TRUE)
    {
      $this->filter[] = array($key_condition, $val, $backtick_protect);
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
    public function add($column, $content, $match_replacement = NULL)
    {
      $this->add_columns[$column] = array('content' => $content, 'replacement' => $this->explode(',', $match_replacement));
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
    public function edit($column, $content, $match_replacement)
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
      $this->unset_columns[] = $column;
      return $this;
    }

    /**
    * Builds all the necessary query segments and performs the main query based on results set from chained statements
    *
    * @param string charset
    * @return string
    */
    public function generate($charset = 'UTF-8')
    {
      $this->get_paging();
      $this->get_ordering();
      $this->get_filtering();
      return $this->produce_output($charset);
    }

    /**
    * Generates the LIMIT portion of the query
    *
    * @return mixed
    */
    private function get_paging()
    {
      $iStart = $this->input('iDisplayStart');
      $iLength = $this->input('iDisplayLength');
      $this->ar->limit(($iLength != '' && $iLength != '-1')? $iLength : 10, ($iStart)? $iStart : 0);
    }

    /**
    * Generates the ORDER BY portion of the query
    *
    * @return mixed
    */
    private function get_ordering()
    {
      if ($this->check_mDataprop())
        $mColArray = $this->get_mDataprop();
      elseif ($this->input('sColumns'))
        $mColArray = explode(',', $this->input('sColumns'));
      else
        $mColArray = $this->columns;

      $mColArray = array_values(array_diff($mColArray, $this->unset_columns));
      $columns = array_values(array_diff($this->columns, $this->unset_columns));

      for($i = 0; $i < intval($this->input('iSortingCols')); $i++)
        if(isset($mColArray[intval($this->input('iSortCol_' . $i))]) && in_array($mColArray[intval($this->input('iSortCol_' . $i))], $columns ) && $this->input('bSortable_'.intval($this->input('iSortCol_' . $i))) == 'true' )
          $this->ar->order_by($mColArray[intval($this->input('iSortCol_' . $i))], $this->input('sSortDir_' . $i));
    }

    /**
    * Generates the LIKE portion of the query
    *
    * @return mixed
    */
    private function get_filtering()
    {
      if ($this->check_mDataprop())
        $mColArray = $this->get_mDataprop();
      elseif ($this->input('sColumns'))
        $mColArray = explode(',', $this->input('sColumns'));
      else
        $mColArray = $this->columns;

      $sWhere = '';
      $sSearch = $this->input('sSearch');

      $mColArray = array_values(array_diff($mColArray, $this->unset_columns));
      $columns = array_values(array_diff($this->columns, $this->unset_columns));

      if($sSearch != '')
        for($i = 0; $i < count($mColArray); $i++)
          if($this->input('bSearchable_' . $i) == 'true' && in_array($mColArray[$i], $columns))
            $sWhere .= $this->select[$mColArray[$i]] . " LIKE '%" . $sSearch . "%' OR ";

      $sWhere = substr_replace($sWhere, '', -3);

      if($sWhere != '')
        $this->ar->where('(' . $sWhere . ')');

      for($i = 0; $i < intval($this->input('iColumns')); $i++)
      {
        if($this->input('sSearch_' . $i) && $this->input('sSearch_' . $i) != '' && in_array($mColArray[$i], $columns))
        {
          $miSearch = explode(',', $this->input('sSearch_' . $i));
          foreach($miSearch as $val)
          {
            if(preg_match("/(<=|>=|=|<|>)(\s*)(.+)/i", trim($val), $matches))
              $this->ar->where($this->select[$mColArray[$i]].' '.$matches[1], $matches[3]);
            else
              $this->ar->where($this->select[$mColArray[$i]].' LIKE', '%'.$val.'%');
          }
        }
      }

      foreach($this->filter as $val)
        $this->ar->where($val[0], $val[1], $val[2]);
    }

    /**
    * Compiles the select statement based on the other functions called and runs the query
    *
    * @return mixed
    */
    private function get_display_result()
    {
      return $this->ar->get();
    }

    /**
    * Builds a JSON encoded string data
    *
    * @param string charset
    * @return string
    */
    private function produce_output($charset)
    {
      $aaData = array();
      $rResult = $this->get_display_result();
      $iTotal = $this->get_total_results();
      $iFilteredTotal = $this->get_total_results(TRUE);

      foreach($rResult->result_array() as $row_key => $row_val)
      {
        $aaData[$row_key] = ($this->check_mDataprop())? $row_val : array_values($row_val);

        foreach($this->add_columns as $field => $val)
          if($this->check_mDataprop())
            $aaData[$row_key][$field] = $this->exec_replace($val, $aaData[$row_key]);
          else
            $aaData[$row_key][] = $this->exec_replace($val, $aaData[$row_key]);

        foreach($this->edit_columns as $modkey => $modval)
          foreach($modval as $val)
            $aaData[$row_key][($this->check_mDataprop())? $modkey : array_search($modkey, $this->columns)] = $this->exec_replace($val, $aaData[$row_key]);

        $aaData[$row_key] = array_diff_key($aaData[$row_key], ($this->check_mDataprop())? $this->unset_columns : array_intersect($this->columns, $this->unset_columns));

        if(!$this->check_mDataprop())
          $aaData[$row_key] = array_values($aaData[$row_key]);
      }

      $sColumns = array_diff($this->columns, $this->unset_columns);
      $sColumns = array_merge_recursive($sColumns, array_keys($this->add_columns));

      $sOutput = array
      (
        'sEcho'                => intval($this->input('sEcho')),
        'iTotalRecords'        => $iTotal,
        'iTotalDisplayRecords' => $iFilteredTotal,
        'aaData'               => $aaData,
        'sColumns'             => implode(',', $sColumns)
      );

      if(strtolower($charset) == 'utf-8')
        return json_encode($sOutput);
      else
        return $this->jsonify($sOutput);
    }

    /**
    * Get result count
    *
    * @return integer
    */
    public function get_total_results($filtering = FALSE)
    {

      if($filtering)
        $this->get_filtering();

      foreach($this->joins as $val)
        $this->ar->join($val[0], $val[1], $val[2]);

      foreach($this->where as $key => $val)
        $this->ar->where($val[0], $val[1], $val[2]);

      foreach($this->group as $val)
        $this->ar->group_by($val);

      foreach($this->like as $key => $val)
        $this->ar->like($val[0], $val[1], $val[2]);

      return $this->ar->count_all_results($this->table);
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

      if(isset($custom_val['replacement']) && is_array($custom_val['replacement']))
      {
        foreach($custom_val['replacement'] as $key => $val)
        {
          $sval = preg_replace("/(?<!\w)([\'\"])(.*)\\1(?!\w)/i", '$2', trim($val));
          if(preg_match('/(\w+)\((.*)\)/i', $val, $matches) && function_exists($matches[1]))
          {
            $func = $matches[1];
            $args = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[,]+/", $matches[2], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

            foreach($args as $args_key => $args_val)
            {
              $args_val = preg_replace("/(?<!\w)([\'\"])(.*)\\1(?!\w)/i", '$2', trim($args_val));
              $args[$args_key] = (in_array($args_val, $this->columns))? ($row_data[($this->check_mDataprop())? $args_val : array_search($args_val, $this->columns)]) : $args_val;
            }

            $replace_string = call_user_func_array($func, $args);
          }
          elseif(in_array($sval, $this->columns))
            $replace_string = $row_data[($this->check_mDataprop())? $sval : array_search($sval, $this->columns)];
          else
            $replace_string = $sval;

          $custom_val['content'] = str_ireplace('$' . ($key + 1), $replace_string, $custom_val['content']);
        }
      }

      return $custom_val['content'];
    }

    /**
    * Check mDataprop
    *
    * @return bool
    */
    private function check_mDataprop()
    {
      if ($this->input('mDataProp_0') === false) return FALSE;

      for($i = 0; $i < intval($this->input('iColumns')); $i++)
        if(!is_numeric($this->input('mDataProp_' . $i)))
          return TRUE;

      return FALSE;
    }

    /**
    * Get mDataprop order
    *
    * @return mixed
    */
    private function get_mDataprop()
    {
      $mDataProp = array();

      for($i = 0; $i < intval($this->input('iColumns')); $i++)
        $mDataProp[] = $this->input('mDataProp_' . $i);

      return $mDataProp;
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
    private function explode($delimiter, $str, $open='(', $close=')') 
    {
      $retval = array();
      $hold = array();
      $balance = 0;
      $parts = explode($delimiter, $str);

      foreach ($parts as $part) 
      {
        $hold[] = $part;
        $balance += $this->balanceChars($part, $open, $close);
        if ($balance < 1)
        {
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
    * Workaround for json_encode's UTF-8 encoding if a different charset needs to be used
    *
    * @param mixed result
    * @return string
    */
    private function jsonify($result = FALSE)
    {
      if(is_null($result)) return 'null';
      if($result === FALSE) return 'false';
      if($result === TRUE) return 'true';

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
/* End of file Datatables.php */