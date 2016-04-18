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

class Database_Update extends Database_Where
{

    /**
     * Varivables.
     */
    private $table = '';
    private $join  = NULL;
    private $sets  = array();

    /**
     * Constractor
     * @param string $table : table name
     * @return object this
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    // {{{ join
    /**
     * Join
     * @param string $table : table name
     * @param string $type  : join type
     * @return object this
     */
    public function join($table, $type = 'left')
    {
        if (is_null($this->join)) {
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

    // {{{ set
    /**
     * Sets
     * @param array $sets : values of sets
     * @return object this
     */
    public function set(array $sets)
    {
        $this->sets = $sets;
        return $this;
    }

    /**
     * Sets
     * @return string query of sets
     */
    private function _sets()
    {
        $query = '';
        $place = array();
        $sets = array();
        foreach ($this->sets as $key => $val) {
            if (is_array($val)) {
                // no place folder to indicate sql directly
                $sets[] = sprintf("%s = (%s)", $key, implode(' ', $val));
            } else {
                $pname = $this->_place_name(++$this->count, $key);
                $sets[] = sprintf("%s = %s", $key, $pname);
                $place[$pname] = $val;
            }
        }
        $query = ' set ' . implode(', ', $sets);
        $this->place = array_merge($this->place, $place);
        return $query;
    }
    // }}}

    /**
     * Build
     * @return string completed query of update
     */
    public function build()
    {
        $this->query = 'update ' . $this->table;
        $this->query .= $this->_join();
        $this->query .= $this->_sets();
        $this->query .= $this->_where();
        $this->query .= ' ;';
        return $this->query;
    }

}

