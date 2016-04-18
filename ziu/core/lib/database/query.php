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

abstract class Database_Query
{

    /**
     * Varivables.
     */
    protected $place = array();
    protected $count = 0;
    protected $query = NULL;

    /**
     * Folder
     * @return array place folder
     */
    public function folder()
    {
        return $this->place;
    }

    /**
     * Place name
     * @param integer $id   : place number
     * @param string  $name : column name
     * @return string place name
     */
    protected function _place_name($id, $name)
    {
        $invalid = str_split('-^ !"#$%&\'()~=~|<>?,./\\@[:]{}`*');
        $name = str_replace($invalid, '_', $name);
        return ':' . (int)$id . '_' . $name;
    }

    /**
     * Reverse place name
     * @param string $pname : place name
     * @return array place folder info
     */
    protected function _reverse_place_name($pname)
    {
        list($id, $name) = sscanf($pname, ":%d_%s");
        return array('id' => $id, 'name' => $name);
    }

    /**
     * Table alias
     * @param mixed $val : table info
     * @return string alias of table
     */
    protected function _table_alias($val)
    {
        if (is_array($val)) {
            $table = $val[0];
            $alias = ' ' . $val[1];
        } else {
            $table = $val;
            $alias = '';
        }
        $this->table = $table; // set table
        if ($table instanceof Database_Select) {
            $table = '(' . $this->_sub_select($table) . ')';
        }
        return $table . $alias;
    }

    /**
     * Clause implode
     * @param array $clause : condition
     * @return string query of condition clause
     */
    protected function _clause_implode(array $clause)
    {
        $query = '';
        if (! empty($clause)) {
            $conj = FALSE;
            foreach ($clause as $key => $val) {
                switch ($val) {
                    case 'and' :
                    case 'or' :
                    case '(' :
                    case ')' :
                        $query .= " $val";
                        $conj = TRUE;
                        break;
                    default :
                        if ($conj) {
                            $query .= " $val";
                        } else {
                            $query .= " and $val";
                        }
                        $conj = FALSE;
                }
            }
        }
        return $query;
    }

    /**
     * Sub select
     * @param object $sn : sql navigator
     * @return string sub query of select
     */
    protected function _sub_select(Database_Select $sn)
    {
        $place = array();
        $sql = trim($sn, ' ;\'');
        $old = $new = array();
        foreach ($sn->folder() as $subpname => $subval) {
            $rev = $this->_reverse_place_name($subpname);
            $newpname = $this->_place_name(++$this->count, $rev['name']);
            $place[$newpname] = $subval;
            $old[] = $subpname;
            $new[] = $newpname;
        }
        unset($sn); // free memory
        $this->place = array_merge($this->place, $place);
        return str_replace($old, $new, $sql);
    }

    /**
     * Clause
     * @param array $condition : condition
     * @return array clauses
     */
    protected function _clause(array $condition)
    {
        $clause = array();
        $place = array();
        foreach ($condition as $val) {
            if (! is_array($val) && is_string($val)) {
                // ex. '(', ')', 'and', 'or'
                $clause[] = trim($val);
            } else {
                $cnt = count($val);
                switch (count($val)) {
                    case 3 :
                        switch (strtolower($val[1])) {
                            case 'between' :
                                // ex. array('hoge', 'between', array(1, 2))
                                $f_pname = $this->_place_name(++$this->count, $val[0]);
                                $t_pname = $this->_place_name(++$this->count, $val[0]);
                                $clause[] = sprintf("%s %s %s and %s", $val[0], $val[1], $f_pname, $t_pname);
                                $place[$f_pname] = $val[2][0];
                                $place[$t_pname] = $val[2][1];
                                break;
                            case 'in' :
                            case 'not in' :
                                if (is_array($val[2])) {
                                    // ex. array('hoge', 'in', array(1, 2, 3))
                                    $tmp = array();
                                    foreach ($val[2] as $v) {
                                        $pname = $this->_place_name(++$this->count, $val[0]);
                                        $place[$pname] = $v;
                                        $tmp[] = $pname;
                                    }
                                    $in = implode(', ', $tmp);
                                    $clause[] = sprintf("%s %s (%s)", $val[0], $val[1], $in);
                                } elseif ($val[2] instanceof Database_Select) {
                                    // ex. array('hoge', 'in', $sn)
                                    // ss is sub query alias for mysql
                                    $clause[] = sprintf("%s %s (select * from (%s) ss)", $val[0], $val[1]
                                                                    , $this->_sub_select($val[2]));
                                } elseif (is_string($val[2])) {
                                    // ex. array('hoge', 'in', '(1, 2, 3)')
                                    $in = trim($val[2], '( )');
                                    $clause[] = sprintf("%s %s (%s)", $val[0], $val[1], $in);
                                }
                                break;
                            default :
                                // ex. array('hoge', '=', '1')
                                $pname = $this->_place_name(++$this->count, $val[0]);
                                $clause[] = sprintf("%s %s %s", $val[0], $val[1], $pname);
                                $place[$pname] = $val[2];
                        }
                        break;
                    case 2 :
                        // ex. array('hoge', '1')
                        $pname = $this->_place_name(++$this->count, $val[0]);
                        $clause[] = sprintf("%s = %s", $val[0], $pname);
                        $place[$pname] = $val[1];
                        break;
                    case 1 :
                        // ex. array('substring(hoge, -2) = 11')
                        $clause[] = trim($val[0]);
                        break;
                    default :
                }
            }
                    
        }
        $this->place = array_merge($this->place, $place);
        return $clause;
    }

    /**
     * To string
     * @return string completed query
     */
    public function __toString()
    {
        if (is_null($this->query)) {
            try {
                $this->query = $this->build();
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        return $this->query;
    }

}

