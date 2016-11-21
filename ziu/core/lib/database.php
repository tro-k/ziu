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
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

require_once dirname(__FILE__) . DS . 'database' . DS . 'query.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'where.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'join.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'select.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'insert.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'update.php';
require_once dirname(__FILE__) . DS . 'database' . DS . 'delete.php';

class Database
{

    // {{{ const
    /**
     * Constants
     */
    const SELECT = 1;
    const INSERT = 2;
    const UPDATE = 3;
    const DELETE = 4;
    // }}}

    // {{{ variable
    /**
     * Variables
     */
    private $connect = FALSE;
    private $driver  = FALSE;
    private $builder = FALSE;
    private $statement = FALSE;
    private $sqlmode = FALSE;
    private $affected = 0;
    private $config  = array(
                            'dsn'  => '',
                            'user' => '',
                            'pass' => '',
                            'charset' => 'utf8',
                            'pooling' => FALSE,
                            'autocommit' => TRUE,
                            'ssl-crt' => '',
                            'ssl-key' => '',
                            'ssl-ca'  => '',
                            'reporting'  => 'exception', // silent, warning or exception
                            'table' => '', // default table name
                        );
    // }}}

    /**
     * Constructor
     * @param array $config : list of config
     */
    public function __construct($config = NULL)
    {
        $this->config($config);
    }

    // {{{ config
    /**
     * Config
     * @param array $config : list of config
     * @return array config
     */
    public function config($config = NULL)
    {
        if (! is_null($config)) {
            if (is_array($config)) {
                $this->config = array_merge($this->config, $config);
            } elseif (is_string($config) && isset($this->config[$config])) {
                return $this->config[$config];
            }
        }
        return $this->config;
    }
    // }}}

    // {{{ connect
    /**
     * Connect
     * @return object PDO instance
     */
    public function connect()
    {
        if (! $this->connect) {
            $attr = array();
            if ($this->config('pooling') === TRUE) {
                $attr[PDO::ATTR_PERSISTENT] = TRUE;
            }
            if ($this->config('autocommit') === TRUE) {
                $attr[PDO::ATTR_AUTOCOMMIT] = TRUE;
            }
            if (($level = $this->_reporting()) !== FALSE) {
                $attr[PDO::ATTR_ERRMODE] = $level;
            }
            list($this->driver) = explode(':', $this->config('dsn'));
            if ($this->driver == 'mysql') {
                if ($this->config('ssl-crt')) {
                    $attr[PDO::MYSQL_ATTR_SSL_CERT] = $this->config('ssl-crt');
                }
                if ($this->config('ssl-key')) {
                    $attr[PDO::MYSQL_ATTR_SSL_KEY] = $this->config('ssl-key');
                }
                if ($this->config('ssl-ca')) {
                    $attr[PDO::MYSQL_ATTR_SSL_CA] = $this->config('ssl-ca');
                }
            }
            $this->connect = new PDO($this->config('dsn')
                                    , $this->config('user')
                                    , $this->config('pass')
                                    , $attr);
            $this->charset($this->config('charset'));
        }
        return $this->connect;
    }

    /**
     * Reporting
     * @return integer PDO error level
     */
    private function _reporting()
    {
        $level = FALSE;
        switch (strtolower($this->config('reporting'))) {
            case 'silent' :
                $level = PDO::ERRMODE_SILENT;
                break;
            case 'warning' :
                $level = PDO::ERRMODE_WARNING;
                break;
            case 'exception' :
                $level = PDO::ERRMODE_EXCEPTION;
                break;
            default :
        }
        return $level;
    }
    // }}}

    // {{{ transaction
    /**
     * Begin transaction
     * @return boolean
     */
    public function begin()
    {
        return $this->connect()->beginTransaction();
    }

    /**
     * Commit transaction
     * @return boolean
     */
    public function commit()
    {
        return $this->connect()->commit();
    }

    /**
     * Rollback transaction
     * @return boolean
     */
    public function rollback()
    {
        return $this->connect()->rollback();
    }

    /**
     * Check transaction
     * @return boolean
     */
    public function transaction()
    {
        return $this->connect()->inTransaction();
    }

    /**
     * Set auto commit attribute
     * @param boolean $bool : TRUE or FALSE
     * @return boolean
     */
    public function autocommit($bool = TRUE)
    {
        return $this->connect()->setAttribute(PDO::ATTR_AUTOCOMMIT, (bool)$bool);
    }
    // }}}

    // {{{ statement
    /**
     * Get default table
     * @param string $table : table name
     * return string table name
     */
    private function _table($table)
    {
        $table = empty($table) ? $this->config('table') : $table;
        if (empty($table)) {
            throw new Exception('SQLSTATE[-]:-: - no table name');
        }
        return $table;
    }

    /**
     * Select
     * @return object sql navicator
     */
    public function select()
    {
        $this->sqlmode = self::SELECT;
        $args = func_get_args();
        $table = $this->config('table');
        if (empty($table)) {
            return new Database_Select($args);
        } else {
            $obj = new Database_Select($args);
            return $obj->table($table);
        }
    }

    /**
     * Insert
     * @param string $table : table name
     * @param string $cols  : columns name
     * @return object sql navicator
     */
    public function insert($table = '', $cols = NULL)
    {
        $this->sqlmode = self::INSERT;
        return new Database_Insert($this->_table($table), $cols);
    }

    /**
     * Update
     * @param string $table : table name
     * @return object sql navicator
     */
    public function update($table = '')
    {
        $this->sqlmode = self::UPDATE;
        return new Database_Update($this->_table($table));
    }

    /**
     * Delete
     * @param string $table : table name
     * @return object sql navicator
     */
    public function delete($table = '')
    {
        $this->sqlmode = self::DELETE;
        return new Database_Delete($this->_table($table));
    }
    // }}}

    // {{{ charset
    /**
     * Charset
     * @param string $char : charactor
     * @return integer effected row number
     */
    public function charset($char)
    {
        return $this->connect()->exec('set names ' . $this->connect()->quote($char));
    }
    // }}}

    // {{{ execute
    /**
     * Query
     * @param string  $query   : raw sql
     * @param integer $sqlmode : self::{SELECT,INSERT,UPDATE,DELETE}
     * @return object this
     */
    public function query($query, $sqlmode = FALSE)
    {
        if ($this->statement instanceof PDOStatement) {
            $this->statement->closeCursor();
        }
        $this->statement = $this->connect()->query($query);
        if ($this->statement === FALSE) {
            // query() return false, if error...
            throw new Exception('SQLSTATE[-]:-: - query() fail with [' . $query . ']');
        }
        $this->sqlmode = $sqlmode;
        return $this;
    }

    /**
     * Prepare
     * @param object $builder : sql navigator
     * @return void
     */
    private function _prepare(Database_Query $builder)
    {
        if ($this->statement instanceof PDOStatement) {
            $this->statement->closeCursor();
        }
        $this->builder   = $builder;
        $this->statement = $this->connect()->prepare((string)$builder);
    }

    /**
     * Execute
     * @param object $builder : sql navigator
     * @return boolean TRUE
     */
    public function execute(Database_Query $builder)
    {
        try {
            $this->_prepare($builder);
            if (! $this->statement->execute($builder->folder())) {
                $error = $this->statement->errorInfo();
                $message = sprintf("SQLSTATE[%s]:-: %s %s", $error[0], $error[1], $error[2]);
                throw new Exception($message);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message .= ' [' . $this->last_query() . ']';
            $this->statement = FALSE; // reset statement for error...
            throw new Exception($message);
        }
        // always return true. if error, throw exception.
        return TRUE;
    }

    /**
     * Fetch
     * @param object $builder : sql navigator
     * @return object this
     */
    public function fetch(Database_Query $builder)
    {
        if ($this->sqlmode == self::SELECT) {
            $ret = $this->execute($builder);
            return $this;
        } else {
            throw new Exception('SQLSTATE[-]:-: - fetch() don\'t use except select()');
        }
    }

    /**
     * Affected row
     * @return integer
     */
    public function affected()
    {
        if ($this->statement === FALSE) {
            // no statement.
            throw new Exception('SQLSTATE[-]:-: - affected() fail with no `PDOStatement`');
        }
        switch ($this->sqlmode) {
            case self::SELECT :
                return $this->affected;
                break;
            case self::INSERT :
                return $this->statement->rowCount();
                break;
            case self::UPDATE :
            case self::DELETE :
                return $this->statement->errorCode() === '00000' ? $this->statement->rowCount() : -1;
                break;
            default :
        }
        return FALSE;
    }
    // }}}

    // {{{ fetch data
    /**
     * Fetch mode
     * @param mixed $object : string of class name, object instance
     * @return void
     */
    private function _fetchmode($object = FALSE)
    {
        if (! $this->statement instanceof PDOStatement) {
            throw new Exception('SQLSTATE[-]:-: - can\'t set fetchmode with no `PDOStatement`');
        } else {
            if ($object === FALSE) {
                // bind array
                $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            } else {
                if (is_string($object)) {
                    // bind class
                    if (class_exists($object)) {
                        $this->statement->setFetchMode(PDO::FETCH_CLASS, $object);
                    } else {
                        $this->statement->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
                    }
                } elseif (is_object($object)) {
                    // bind object
                    $this->statement->setFetchMode(PDO::FETCH_INTO, $object);
                } else {
                    // bind default fetch mode...
                }
            }
        }
    }

    /**
     * All
     * @param mixed $object : string of class name, object instance
     * @return array result
     */
    public function all($object = FALSE)
    {
        if ($this->statement === FALSE) {
            throw new Exception('SQLSTATE[-]:-: - can\'t fetch all with no `PDOStatement`');
        } else {
            $this->_fetchmode($object);
            // when no record, return array().
            $ret = $this->statement->fetchAll();
            $this->affected = count($ret);
            return $ret;
        }
    }

    /**
     * Row
     * @param mixed $object : string of class name, object instance
     * @return array result
     */
    public function row($object = FALSE)
    {
        if ($this->statement === FALSE) {
            throw new Exception('SQLSTATE[-]:-: - can\'t fetch row with no `PDOStatement`');
        } else {
            $this->_fetchmode($object);
            $ret = $this->statement->fetch();
            // when no record, return array() with wrap.
            // because fetch() return false.
            $this->affected = empty($ret) ? 0 : 1;
            return $ret !== FALSE ? $ret : array();
        }
    }

    /**
     * One
     * @param integer $num : column number
     * @return string result
     */
    public function one($num = 0)
    {
        if ($this->statement === FALSE) {
            throw new Exception('SQLSTATE[-]:-: - can\'t fetch one with no `PDOStatement`');
        } else {
            $ret = $this->statement->fetchColumn((int)$num);
            // when no record, return '' with wrap.
            // because fetchColumn() return false.
            $this->affected = empty($ret) ? 0 : 1;
            return $ret !== FALSE ? $ret : '';
        }
    }
    // }}}

    // {{{ quote
    /**
     * Quote
     * @param string $val : value
     * @return string quoted value
     */
    public function quote($val)
    {
        if (is_null($val)) {
            return 'NULL';
        } elseif ($val === TRUE) {
            return "'1'";
        } elseif ($val === FALSE) {
            return "'0'";
        } else {
            return $this->connect()->quote($val);
        }
    }
    // }}}

    // {{{ last
    /**
     * Real query
     * @param object $builder : sql navigator
     * @return string sql query
     */
    public function real_query(Database_Query $builder)
    {
        $place = $folder = array();
        $prepare = (string)$builder;
        foreach ($builder->folder() as $key => $val) {
            $place[]  = $key;
            $folder[] = $this->quote($val);
        }
        return str_replace($place, $folder, $prepare);
    }

    /**
     * Last query
     * @return string executed sql query at last
     */
    public function last_query()
    {
        if ($this->builder instanceof Database_Query) {
            // from qeury builder...
            return $this->real_query($this->builder);
        } elseif ($this->statement instanceof PDOStatement) {
            // from PDOStatement...
            return $this->statement->queryString;
        } else {
            // no last query...
            throw new Exception('SQLSTATE[-]:-: - not yet prepare query');
        }
    }

    /**
     * Last insert id
     * @param string $name : column name
     * @return integer sequence id of inserted record at last
     */
    public function last_insert_id($name = NULL)
    {
        return $this->connect()->lastInsertId($name);
    }
    // }}}

}

