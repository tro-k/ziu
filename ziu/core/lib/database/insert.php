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

class Database_Insert extends Database_Query
{

    /**
     * Varivables.
     */
    private $table = '';
    private $cols  = array();
    private $vals  = array();

    /**
     * Constractor
     * @param string $table : table name
     * @param string $cols  : columns name
     * @return object this
     */
    public function __construct($table, array $cols = NULL)
    {
        $this->table = $table;
        $this->cols  = $cols;
    }

    /**
     * Values
     * @param array $vals : values
     * @return object this
     */
    public function values(array $vals)
    {
        $vals = func_get_args();
        foreach ($vals as $val) {
            if (isset($val[0])) {
                $this->vals = array_merge($this->vals, $val);
            } else {
                $this->vals[] = $val;
            }
        }
        return $this;
    }

    /**
     * Select
     * @param object $query : sql navigator of select
     * @return object this
     */
    public function select(Database_Select $query)
    {
        $this->vals = $query;
        return $this;
    }

    /**
     * Cols
     * @return string query of columns
     */
    private function _cols()
    {
        if ($this->vals instanceof Database_Select) {
            // no cols with insert into select...
            $query = '';
        } else {
            if (empty($this->cols)) {
                $cols = array();
                foreach ($this->vals[0] as $key => $val) {
                    $cols[] = $key;
                }
            } else {
                $cols = $this->cols;
            }
            $query = '(' . implode(', ', $cols) . ')';
        }
        return $query;
    }

    /**
     * Vals
     * @return string query of values
     */
    private function _vals()
    {
        $query = '';
        $place = array();
        $count = 0;
        if ($this->vals instanceof Database_Select) {
            // insert into select...
            $query = ' ' . trim($this->vals, ' ;');
            $this->place = $this->vals->folder();
        } else {
            $tmp = array();
            foreach ($this->vals as $value) {
                $vals = array();
                foreach ($value as $key => $val) {
                    if (is_array($val)) {
                        // no place folder to indicate sql directly
                        $vals[] = implode(' ', $val);
                    } else {
                        $pname = $this->_place_name(++$count, $key);
                        $vals[] = $pname;
                        $place[$pname] = $val;
                    }
                }
                $tmp[] = '(' . implode(', ', $vals) . ')';
            }
            $query = ' values' . implode(', ', $tmp);
        }
        $this->place = array_merge($this->place, $place);
        return $query;
    }

    /**
     * Build
     * @return string completed query of insert
     */
    public function build()
    {
        $this->query = 'insert into ' . $this->table;
        $this->query .= $this->_cols();
        $this->query .= $this->_vals();
        $this->query .= ' ;';
        return $this->query;
    }

}

