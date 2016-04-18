<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Model Super.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

abstract class Model_Super
{

    /**
     * Connecting function
     * @var array 'read' and 'write'
     */
    protected $connecting = NULL;

    /**
     * Cache tray
     * @var array
     */
    private static $cache = array();

    /**
     * Database connection
     * @var array
     */
    private $con = array('read' => NULL, 'write' => NULL);

    /**
     * Stick database connection
     * @var string
     */
    private $stick_connection = FALSE;

    /**
     * Error Message of MySQL
     * @var string
     */
    protected $er;

    /**
     * Default table name
     * @var string
     */
    protected $table;

    // {{{ constructor
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->conf = conf('db');
        foreach ($this->conf['call_method_group'] as $key => $val) {
            if ($val !== '') {
                $this->conf['call_method_group'][$key] = explode(',', $val);
            }
        }
        if (is_null($this->connecting)) {
            $this->connecting = $this->conf['connecting'];
        }
        $this->con = array(
            'read'  => array('type' => 'r', 'obj' => $this->connecting['read']()),
            'write' => array('type' => 'w', 'obj' => $this->connecting['write']()),
        );
        if ($this->conf['debug'] && ! empty($this->conf['debug_mysql_sql_mode'])) {
            // when MySQL, use strict sql_mode.
            $this->query('set sql_mode = "' . $this->conf['debug_mysql_sql_mode'] . '"');
        }
    }
    // }}}

    // {{{ undefined call
    public function __call($name, $args)
    {
        static $last = FALSE;
        $obj = NULL;
        $chain = FALSE;
        $exec  = in_array($name, $this->conf['call_method_group']['execution']);
        $debug = $this->_is_debug($name);
        $cache = $this->conf['cache'];
        $use_cache = FALSE;
        $obj = $this->_get_object($name, $chain, $last);
        $obj['obj']->config(array('table' => $this->table())); // set default table name.
        try {
            $exec  && $this->_before_executing($obj, $name, $args);
            $debug && $this->_before_debug($obj, $name, $args);
            if ($cache && $exec && $obj['type'] == 'r') {
                $key = md5($obj['obj']->real_query($args[0]));
                if (isset(self::$cache[$key])) {
                    $result = self::$cache[$key];
                    $use_cache = TRUE;
                } else {
                    $result = call_user_func_array(array($obj['obj'], $name), $args);
                    self::$cache[$key] = $result;
                }
            } else {
                $result = call_user_func_array(array($obj['obj'], $name), $args);
            }
            $debug && $this->_after_debug($use_cache);
            $exec  && $this->_after_executing();
            if ($exec) {
                $last = $obj;
            }
            return $chain ? $this : $result;
        } catch (Exception $e) {
            $last = FALSE; // reset last object for error...
            $this->logger($e->getMessage(), 2);
            throw new Exception('Model_Super::' . $name . ' ' . $e->getMessage());
        }
    }
    // }}}

    // {{{ tools...
    /**
     * Get object
     * @param string  $name  : call method name
     * @param boolean $chain : flag of chain
     * @param object  $last  : last of object
     * @return object connection
     */
    private function _get_object($name, &$chain, &$last)
    {
        switch ($name) {
            case 'fetch' :
            case 'query' :
                $chain = TRUE;
                $obj = $name == 'fetch' ? $this->con['read'] : $this->con['write'];
                break;
            case 'all' :
            case 'row' :
            case 'one' :
            case 'quote' :
                $obj = $last['obj'] instanceof Database ? $last : $this->con['read'];
                break;
            case 'select' :
                $obj = $this->con['read'];
                break;
            case 'insert' :
            case 'update' :
            case 'delete' :
            case 'execute' :
            case 'begin' :
            case 'commit' :
            case 'rollback' :
            case 'transaction' :
            case 'autocommit' :
            case 'last_insert_id' :
                $obj = $this->con['write'];
                break;
            case 'real_query' :
            case 'last_query' :
            case 'affected' :
                if ($last['obj'] instanceof Database) {
                    $obj = $last;
                } else {
                    $message = 'Model_Super::' . $name . ' has no database class.';
                    $this->logger($message, 2);
                    throw new Exception($message);
                }
                break;
            default :
                $message = 'Model_Super::' . $name . ' does not exesit.';
                $this->logger($message, 2);
                throw new Exception($message);
        }
        return $this->stick_connection === FALSE ? $obj : $this->con[$this->stick_connection];
    }

    /**
     * Check debug flag
     * @param string $name : call method name
     * @return boolean
     */
    private function _is_debug($name)
    {
        $group_name = $this->conf['debug_method_group'];
        return $this->conf['debug']
                 && (
                    $this->conf['call_method_group'][$group_name] === ''
                    || in_array($name, $this->conf['call_method_group'][$group_name])
                 );
    }

    /**
     * Before executing action
     * @return void
     */
    protected function before_action() {}

    /**
     * After executing action
     * @return void
     */
    protected function after_action() {}

    /**
     * Before debug operation
     * @param array  $obj  : database object
     * @param string $name : database method
     * @param array  $args : database arguments
     * @return void
     */
    private function _before_debug($obj, $name, $args)
    {
        $config = $obj['obj']->config();
        $sql = isset($args[0]) ?
                ($args[0] instanceof Database_Query ?
                    $obj['obj']->real_query($args[0]) : "$name({$args[0]})"
                )
                : $name;
        $this->debug = array(
            'name'  => $name,
            'id'    => $obj['type'],
            'dsn'   => $config['dsn'],
            'sql'   => $sql,
            'stime' => microtime(TRUE),
        );
    }

    /**
     * After debug operation
     * @param boolean $use_cache : flag to use cache
     * @return void
     */
    private function _after_debug($use_cache)
    {
        $cache = $use_cache ? 'c' : 'n';
        $gap = sprintf("%.05f", (microtime(TRUE) - $this->debug['stime']));
        l_debug('<'. $this->debug['id'] . '.' . $cache . '> [' . $this->debug['dsn'] . '] (' . $gap . 'µs) ->' . $this->debug['name'] . ' : ' . $this->debug['sql']);
    }

    /**
     * Before executing operation
     * @param array  $obj  : database object
     * @param string $name : database method
     * @param array  $args : database arguments
     * @return void
     */
    private function _before_executing($obj, $name, $args)
    {
        $this->before_action();
        if (class_exists('Ticker')) {
            Ticker::query_profile_start($obj['obj']->real_query($args[0]));
        }
    }

    /**
     * After executing operation
     * @return void
     */
    private function _after_executing()
    {
        if (class_exists('Ticker')) {
            Ticker::query_profile_stop();
        }
        $this->after_action();
    }

    /**
     * Get Error
     * @return string
     */
    public function get_error()
    {
        return $this->er;
    }

    /**
     * Log
     * @param string $message : log message
     * @param integer $back : backnumber
     * @return void
     */
    protected function logger($message, $back = 1)
    {
        $this->er = $message;
        $trace = debug_backtrace();
        $info  = $trace[$back];
        l_error($message, $info['class'] . '::' . $info['function']);
    }

    /**
     * Transact
     * @param object $func : closure
     * @return void
     */
    public function transact($func)
    {
        try {
            $this->begin();
            $func($this);
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Use database connection 'write' as master
     * @param object $func : closure
     * @return mixed
     */
    public function master($func)
    {
        try {
            $this->stick_connection = 'write';
            $func($this);
            $this->stick_connection = FALSE;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get table name
     * @return string table name
     */
    protected function table()
    {
        if (isset($this->table)) {
            $table = $this->table;
        } else {
            $name = str_replace('Model_', '', get_class($this));
            $table = '';
            for ($i = 0; $i < strlen($name); $i++) {
                $table .= (ctype_upper($name[$i]) ? '_' : '') . $name[$i];
            }
            $table = strtolower(trim($table, '_'));
        }
        return $table;
    }
    // }}}

    // {{{ standard method
    /**
     * Get list
     * @param integer $limit  : Limit
     * @param integer $offset : Offset
     * @param array   $keys   : Condition
     * @param string  $order  : Order
     * @return array
     */
    public function getList($limit = NULL, $offset = NULL, array $keys = array(), $order = '')
    {
        $sn = $this->select('SQL_CALC_FOUND_ROWS *')->from();
        foreach ($keys as $key) {
            $sn->where($key);
        }
        if (! empty($order)) {
            $sn->order($order);
        }
        if (! is_null($limit) && ! is_null($offset)) {
            $sn->limit($limit)->offset($offset);
        }
        return $this->fetch($sn)->all();
    }
    // }}}

}

