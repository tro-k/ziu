<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Session Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

class Session
{

    // {{{ variable
    /**
     * Variables
     */
    private $config = array(
        'session' => array(
            // see. http://www.php.net/manual/ja/session.configuration.php
            'save_handler'   => 'files', // files, user or memcache(memcache.so needed)
            'name'           => 'SESS',
            'gc_maxlifetime' => 1440,    // lifetime for session data
            'gc_probability' => 1,       // gc_probability/gc_divisor
            'gc_divisor'     => 100,     // default is 1/100
        ),
        'user' => array(
            'driver' => 'mysql', // mysql or ... not prepared yet
        ),
        'namespace' => 'SSLIB',
    );

    private $session_id = NULL;

    private $namespace = NULL;
    // }}}

    // {{{ initialize
    /**
     * Constructor
     * @param array $config : config variables
     */
    public function __construct($config = NULL)
    {
        $this->init($config);
    }

    /**
     * Init
     * @param array $config : config variables
     * @return object this
     */
    public function init($config = NULL)
    {
        $this->config($config);
        if (! isset($_SESSION)) {
            foreach ($this->config['session'] as $key => $val) {
                ini_set('session.' . $key, $val);
            }
            if ($this->config['session']['save_handler'] == 'user') {
                $path = dirname(__FILE__) . '/session/' . $this->config['user']['driver'] . '.php';
                $path = str_replace('/', DS, $path);
                if (is_readable($path)) {
                    require_once $path;
                }
            }
            $this->namespace = $this->config['namespace'];
            session_start();
            if (! isset($_SESSION[$this->namespace])) {
                $_SESSION[$this->namespace] = NULL;
            }
        }
        if (is_null($this->session_id)) {
            $this->session_id = session_regenerate_id();
        }
        return $this;
    }

    /**
     * Config
     * @param array $config : config variables
     * @return object this
     */
    public function config($config)
    {
        if (is_array($config)) {
            if (isset($config['session'])) {
                $this->config['session'] = array_merge($this->config['session'], $config['session']);
            }
            if (isset($config['user'])) {
                $this->config['user'] = array_merge($this->config['user'], $config['user']);
            }
            if (isset($config['namespace'])) {
                $this->config['namespace'] = $config['namespace'];
            }
        }
        if (isset($this->config['session']['save_path'])) {
            $this->config['session']['save_path'] = str_replace('/', DS, $this->config['session']['save_path']);
        }
        return $this;
    }
    // }}}

    // {{{ getter / setter
    /**
     * Set data in session
     * @param string $key   : session variable name
     * @param mixed  $value : session value
     * @return object this
     */
    public function set($key, $value)
    {
        $_SESSION[$this->namespace][$key] = $value;
    }

    /**
     * Get data in session
     * @param string $key : session variable name
     * @return mixed saved data in session
     */
    public function get($key)
    {
        $tmp = $_SESSION[$this->namespace];
        $data = array();
        $regex = preg_quote($key, '/');
        foreach ((array)$tmp as $k => $v) {
            if (preg_match('/^' . $regex . '/', $k)) {
                $data = $this->_make_array($k, $v, $data);
            }
        }
        return $this->_read_array(explode('/', $key), $data);
    }
    // }}}

    // {{{ parser
    /**
     * Read array value in recursion
     * @param array $hash : array of hash keys
     * @param array $data : array of data
     * @return mixed data
     */
    private function _read_array(array $hash, array $data)
    {
        $name = array_shift($hash);
        if (count($hash) > 0 && isset($data[$name]) && is_array($data[$name])) {
            return $this->_read_array($hash, $data[$name]);
        } else {
            return isset($data[$name]) ? $data[$name] : '';
        }
    }

    /**
     * Make array value in recursion
     * @param mixed $key  : session variable
     * @param array $val  : session value
     * @param array $data : session array in construction
     * @return mixed data
     */
    private function _make_array($key, $val, $data = array())
    {
        if (! is_array($key)) {
            $key = explode('/', $key);
        }
        if (count($key)) {
            $node = array_shift($key);
            if (! isset($data[$node])) {
                $data[$node] = NULL;
            }
            $data[$node] = $this->_make_array($key, $val, $data[$node]);
        } else {
            $data = $val;
        }
        return $data;
    }
    // }}}

}

