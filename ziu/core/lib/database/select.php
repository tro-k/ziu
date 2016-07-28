<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Database Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Database_Select extends Database_Where
{

    /**
     * Varivables.
     */
    private $cols = array();
    private $from = array();
    private $join = NULL;
    private $group = array();
    private $having = array();
    private $order = array();
    private $union = '';
    private $limit  = NULL;
    private $offset = NULL;
    private $default_table = FALSE;
    private $in_union = FALSE;

    // {{{ cols
    /**
     * Constractor
     * @param array $args : columns
     * @return object this
     */
    public function __construct(array $args = NULL)
    {
        $this->cols = $args;
    }

    /**
     * Cols
     * @return string query of columns
     */
    private function _cols()
    {
        $query = '';
        $cols = array();
        if (empty($this->cols)) {
            $cols[] = '*';
        } else {
            foreach ($this->cols as $val) {
                if (is_array($val)) {
                    $cols[] = sprintf("%s as %s", $val[0], $val[1]);
                } else {
                    $cols[] = "$val";
                }
            }
        }
        if (! empty($cols)) {
            $query = implode(', ', $cols);
        }
        return $query;
    }
    // }}}

    // {{{ from
    /**
     * Set default table
     * @param string $table : table name
     * return object this
     */
    public function table($table)
    {
        $this->default_table = $table;
        return $this;
    }

    /**
     * From
     * @param string () : tables
     * @return object this
     */
    public function from()
    {
        $this->from = func_get_args();
        if (empty($this->from) && ! empty($this->default_table)) {
            $this->from = array($this->default_table);
        }
        return $this;
    }

    /**
     * From
     * @return string query of from
     */
    private function _from()
    {
        $query = '';
        $from = array();
        foreach ($this->from as $val) {
            $from[] = $this->_table_alias($val);
        }
        if (! empty($from)) {
            $query = ' from ' . implode(', ', $from);
        }
        return $query;
    }
    // }}}

    // {{{ join
    /**
     * Join
     * @param string $table : table name
     * @param string $type  : join type
     * @return object this
     */
    public function join($table, $type = 'left')
    {
        if (! $this->join instanceof Database_Join) {
            $this->join = new Database_Join($this->place, $this->count);
        }
        $this->join->push(array('table' => $table, 'type' => $type));
        return $this;
    }

    /**
     * On
     * @param string () : conditions of join
     * @return object this
     */
    public function on()
    {
        $join = $this->join->pop();
        if (count($join) > 0) {
            $join['on'] = func_get_args();
            $this->join->push($join);
        }
        return $this;
    }

    /**
     * Using
     * @param string () : columns of using
     * @return object this
     */
    public function using()
    {
        $join = $this->join->pop();
        if (count($join) > 0) {
            $join['using'] = func_get_args();
            $this->join->push($join);
        }
        return $this;
    }

    /**
     * Join
     * @return string query of join
     */
    private function _join()
    {
        if (! is_null($this->join)) {
            $query = (string)$this->join;
            return $query;
        }
    }
    // }}}

    // {{{ group
    /**
     * Group
     * @param string () : columns of group by
     * @return object this
     */
    public function group()
    {
        $this->group = func_get_args();
        return $this;
    }

    /**
     * Group
     * @return string query of group by
     */
    private function _group()
    {
        $query = '';
        if (! empty($this->group)) {
            $query = ' group by ' . implode(', ', $this->group);
        }
        return $query;
    }

    /**
     * Having
     * @param string () : conditions of having
     * @return object this
     */
    public function having()
    {
        $this->having = func_get_args();
        return $this;
    }

    /**
     * Having
     * @return string query of having
     */
    private function _having()
    {
        $query = '';
        if (! empty($this->having)) {
            $having = $this->_clause($this->having);
            $having = $this->_clause_implode($having);
            $having = trim($having);
            if ($having != '') {
                if (strpos($having, 'and ') === 0) {
                    $query = ' having ' . substr($having, 4);
                } elseif (strpos($having, 'or ') === 0) {
                    $query = ' having ' . substr($having, 3);
                } else {
                    $query = ' having ' . $having;
                }
            }
        }
        return $query;
    }
    // }}}

    // {{{ order
    /**
     * Order
     * @param string () : columns of order by
     * @return object this
     */
    public function order()
    {
        $this->order = func_get_args();
        return $this;
    }

    /**
     * Order
     * @return string query of order by
     */
    private function _order()
    {
        $query = '';
        if (! empty($this->order)) {
            $order = array();
            foreach ($this->order as $val) {
                if (is_array($val)) {
                    $order[] = $val[0] . ' ' . $val[1];
                } else {
                    $order[] = $val;
                }
            }
            $query = ' order by ' . implode(', ', $order);
        }
        return $query;
    }
    // }}}

    // {{{ limit
    /**
     * Limit
     * @param integer $num : number of limit
     * @return object this
     */
    public function limit($num)
    {
        $this->limit = (int)$num;
        return $this;
    }

    /**
     * Limit
     * @return string query of limit
     */
    private function _limit()
    {
        $query = '';
        if (isset($this->limit) && is_int($this->limit)) {
            $query = ' limit ' . (int)$this->limit;
        }
        return $query;
    }
    // }}}

    // {{{ offset
    /**
     * Offset
     * @param integer $num : number of offset
     * @return object this
     */
    public function offset($num)
    {
        $this->offset = (int)$num;
        return $this;
    }

    /**
     * Offset
     * @return string query of offset
     */
    private function _offset()
    {
        $query = '';
        if (isset($this->offset) && is_int($this->offset)) {
            $query = ' offset ' . (int)$this->offset;
        }
        return $query;
    }
    // }}}

    // {{{ union
    /**
     * Union
     * @param object () : Database_Select
     * @return object this
     */
    public function union()
    {
        $union = func_get_args();
        $all = '';
        if (strtolower($union[0]) == 'all') {
            array_shift($union);
            $all = ' all';
        }
        $this->in_union = TRUE;
        foreach ($union as $val) {
            $sql = $val instanceof Database_Select ? $this->_sub_select($val) : $val;
            $this->union .= " union{$all} ({$sql})";
        }
        $this->in_union = FALSE;
        return $this;
    }

    /**
     * Union
     * @return string query of union
     */
    public function _union()
    {
        return $this->union;
    }
    // }}}

    /**
     * Build
     * @return string completed query of select
     */
    public function build()
    {
        if (! empty($this->union) && $this->in_union === FALSE) {
            $this->query = '(select ';
            $this->query .= $this->_cols();
            $this->query .= $this->_from();
            $this->query .= $this->_join();
            $this->query .= $this->_where();
            $this->query .= $this->_group();
            $this->query .= $this->_having();
            $this->query .= ')';
            $this->query .= $this->_union();
            $this->query .= $this->_order();
            $this->query .= $this->_limit();
            $this->query .= $this->_offset();
            $this->query .= ' ;';
        } else {
            $this->query = 'select ';
            $this->query .= $this->_cols();
            $this->query .= $this->_from();
            $this->query .= $this->_join();
            $this->query .= $this->_where();
            $this->query .= $this->_group();
            $this->query .= $this->_having();
            $this->query .= $this->_order();
            $this->query .= $this->_limit();
            $this->query .= $this->_offset();
            $this->query .= ' ;';
        }
        return $this->query;
    }

}

