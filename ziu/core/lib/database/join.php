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

class Database_Join extends Database_Query
{

    /**
     * Varivables.
     */
    private $join = array();

    /**
     * Constractor
     * @param array   &$place : place folder
     * @param integer &$count : place counter
     * @return void
     */
    public function __construct(&$place, &$count)
    {
        $this->place =& $place;
        $this->count =& $count;
    }

    /**
     * Push
     * @param array $condition : join condition of table and type
     * @return void
     */
    public function push($condition)
    {
        $this->join[] = $condition;
    }

    /**
     * Pop
     * @return array join condition of table and type
     */
    public function pop()
    {
        return array_pop($this->join);
    }

    /**
     * build
     * @return string completed query of join
     */
    public function build()
    {
        $join = array();
        foreach ($this->join as $val) {
            $type = strtolower($val['type']);
            switch ($type) {
                case 'left' :
                case 'left inner' :
                case 'left outer' :
                case 'right' :
                case 'right innter' :
                case 'right outer' :
                case 'inner' :
                case 'outer' :
                    $table = $this->_table_alias($val['table']);
                    if (isset($val['using'])) {
                        $conj = ' using(' . implode(', ', $val['using']) . ')';
                    } elseif (isset($val['on'])) {
                        $conj = ' on';
                        $on = $this->_clause($val['on']);
                        $clause = $this->_clause_implode($on);
                        if ($clause != '') {
                            $clause = trim($clause);
                            if (strpos($clause, 'and ') === 0) {
                                $conj = ' on ' . substr($clause, 4);
                            } elseif (strpos($clause, 'or ') === 0) {
                                $conj = ' on ' . substr($clause, 3);
                            } else {
                                $conj = ' on ' . $clause;
                            }
                        }
                    } else {
                        $conj = '';
                    }
                    $join[] = sprintf("%s join %s%s", $type, $table, $conj);
                    break;
                default :
            }
        }
        if (! empty($join)) {
            $query = ' ' . implode(' ', $join);
        } else {
            $query = '';
        }
        return $query;
    }

}

