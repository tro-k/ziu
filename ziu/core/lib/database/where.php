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

abstract class Database_Where extends Database_Query
{

    /**
     * Varivables.
     */
    protected $where = array();

    /**
     * Where
     * @param array () : conditions
     * @return object this
     */
    public function where()
    {
        $args = func_get_args();
        $this->where = array_merge($this->where, array('and'), $args);
        return $this;
    }

    /**
     * And
     * @param array () : conditions
     * @return object this
     */
    public function and_()
    {
        $args = func_get_args();
        $this->where = array_merge($this->where, array('and'), $args);
        return $this;
    }

    /**
     * Or
     * @param array () : conditions
     * @return object this
     */
    public function or_()
    {
        $args = func_get_args();
        $this->where = array_merge($this->where, array('or'), $args);
        return $this;
    }

    /**
     * Parent
     * @param string () : conditions
     * @return object this
     */
    public function paren()
    {
        $args = func_get_args();
        switch (strtolower($args[0])) {
            case 'and' :
            case 'or' :
                $conj = array(strtolower(array_shift($args)));
                break;
            default :
                $conj = array();
        }
        $this->where = array_merge($this->where, $conj, array(array('(')), $args, array(array(')')));
        return $this;
    }

    /**
     * Where
     * @return string query of where
     */
    protected function _where()
    {
        $query = '';
        if (! empty($this->where)) {
            $where = $this->_clause($this->where);
            $where = $this->_clause_implode($where);
            $where = trim($where);
            if ($where != '') {
                if (strpos($where, 'and ') === 0) {
                    $query = ' where ' . substr($where, 4);
                } elseif (strpos($where, 'or ') === 0) {
                    $query = ' where ' . substr($where, 3);
                } else {
                    $query = ' where ' . $where;
                }
            }
        }
        return $query;
    }

}

