<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Loader Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Loader
{

    /**
     * Classes
     */
    private $classes = array();

    /**
     * Config
     */
    private $config = array();

    /**
     * Flag of loaded config core.
     */
    private $is_loaded_conf_core = FALSE;

    /**
     * No value constant.
     */
    const NO_VALUE = '__NO_VALUE__';

    /**
     * Constructor
     */
    public function __construct()
    {
        // load config for core.
        $this->config['core'] = require_once ZIU_CORE_CONF_PATH . 'core.php';
        $this->conf('core');
    }

    /**
     * Is Load
     * @param string $key  : class or config name
     * @param string $kind : kind name of target
     * @return mixed object or array, when none is FALSE
     */
    public function is($key, $kind = 'core')
    {
        switch ($kind) {
            case 'core' :
            case 'logic' :
                if (isset($this->classes[$key])) {
                    return $this->classes[$key];
                }
                break;
            case 'conf' :
                if (isset($this->config[$key])) {
                    return $this->config[$key];
                }
                break;
            default :
        }
        return FALSE;
    }

    /**
     * Load Core
     * @param string $class : class name
     * @return object instance of class
     */
    public function core($class)
    {
        return $this->_class($this->config['core']['base_path'] . strtolower($class) . '.php');
    }

    /**
     * Load/Update Config
     * @param string $name  : config name
     * @param mixed  $value : value to update
     * @return array config
     */
    public function conf($name, $value = self::NO_VALUE)
    {
        if (strpos($name, '/') !== FALSE) {
            $hash = explode('/', $name);
            $name = array_shift($hash);
        }
        if (($name == 'core' && $this->is_loaded_conf_core === FALSE)
             || ! isset($this->config[$name])) {
            // load conf.
            $core = $this->config['core'];
            if ($name == 'core') {
                $this->is_loaded_conf_core = TRUE; // loading config core.
            } else {
                $this->config[$name] = array();
            }
            $paths = array(
                        $core['conf_path'],
                        $core['apps_conf_path'],
                        $core['app_conf_path'],
                    );
            foreach ($paths as $path) {
                $path = str_replace('/', DS, $path);
                $file = $path . strtolower($name) . '.php';
                if (is_readable($file)) {
                    $data = include_once $file;
                    if (is_array($data)) {
                        $this->config[$name] = $data + $this->config[$name];
                    }
                }
            }
        }
        if ($value !== self::NO_VALUE && isset($this->config[$name])) {
            // update conf.
            $val = &$this->config[$name];
            $not = FALSE;
            if (isset($hash)) {
                foreach ($hash as $key) {
                    if (isset($val[$key])) {
                        $val = &$val[$key];
                    } else {
                        $not = TRUE;
                        break;
                    }
                }
            }
            if ($not === FALSE) {
                // update, if exists.
                $val = $value;
            }
        }
        if (isset($hash)) {
            return $this->_read_conf($hash, $this->config[$name]);
        } else {
            return $this->config[$name];
        }
    }

    /**
     * Read config value in recursion
     * @param array $hash   : array of hash keys
     * @param array $config : array of config
     * @return mixed config data
     */
    private function _read_conf(array $hash, array $config)
    {
        $name = array_shift($hash);
        if (count($hash) > 0 && is_array($config[$name])) {
            return $this->_read_conf($hash, $config[$name]);
        } else {
            return isset($config[$name]) ? $config[$name] : '';
        }
    }

    /**
     * Include File
     * @param mixed $paths : path for import
     * @return void
     */
    public function import($paths)
    {
        if (! is_array($paths)) {
            $paths = array($paths);
        }
        $loader = $this; // set this loader
        foreach ($paths as $path) {
            $path = str_replace('/', DS, $path);
            if (strrpos($path, '.php') === FALSE) {
                $path = $path . '.php';
            }
            if (isset($path[0]) && $path[0] != DS && $path[1] != ':' && $path[0] != '.') {
                // $path[1] = ':'; for windows
                $dir = '';
                if (($pos = strpos($path, DS)) !== FALSE) {
                    $dir = substr($path, 0, $pos);
                    $path = substr($path, $pos + 1);
                }
                $conf = $this->config['core'];
                $list = array(
                    rtrim($conf['app_path'] . $dir, DS) . DS,
                    (isset($conf['app_' . $dir . '_path']) ? $conf['app_' . $dir . '_path'] : ''),
                    (isset($conf['apps_' . $dir . '_path']) ? $conf['apps_' . $dir . '_path'] : ''),
                    (isset($conf[$dir . '_path']) ? $conf[$dir . '_path'] : ''),
                );
                foreach ($list as $val) {
                    if ($val !== '') {
                        $filepath = $val . $path;
                        if (is_file($filepath)) {
                            include_once $filepath;
                            break;
                        }
                    }
                }
            } else {
                if (is_file($path)) {
                    include_once $path;
                }
            }
        }
    }

    /**
     * Load Singleton Class
     * @param string $path  : path of class
     * @param mixed  $param : param for class
     * @return object instance of class
     */
    public function singleton($path, $param = NULL)
    {
        if (strpos($path, 'lib/') === 0) {
            $obj = $this->lib(substr($path, 4), $param);
        } else {
            $obj = $this->_class($path, $param);
        }
        return $obj;
    }

    /**
     * Load Helper
     * @param string $name : helper name
     * @return void
     */
    public function help($name)
    {
        $file = strtolower($name) . '.php';
        $conf = $this->config['core'];
        $this->import(array(
                            $conf['help_path'] . $file,
                            $conf['apps_help_path'] . $file,
                            $conf['app_help_path'] . $file,
                        )
                    );
    }

    /**
     * Load Class
     * @param string $path  : path of class
     * @param mixed  $param : param for class
     * @param array  $add   : add info
     * @param boolean $add  : flag to exec init()
     * @return object instance of class
     */
    private function _class($path, $param = NULL, array $add = array(
                                                        'name' => ''), $init = TRUE)
    {
        $path = str_replace('/', DS, $path);
        if (is_readable($path)) {
            if ($add['name'] !== '') {
                $name = $add['name'];
            } else {
                $class = pathinfo($path, PATHINFO_FILENAME);
                $name = 'Ziu_' . ucfirst($class);
            }
            if (($obj = $this->is($name)) === FALSE) {
                include_once $path;
                $obj = new $name($param);
                if ($add['name'] === '') {
                    $obj->loader = $this; // set this loader.
                }
                $this->classes[$name] = $obj;
            } else {
                if (method_exists($obj, '__construct')) {
                    $obj->__construct($param);
                }
            }
            if ($init && method_exists($obj, 'init')) {
                $obj->init();
            }
            return $obj;
        }
    }

    /**
     * Load Library as Class
     * @param string $path  : path of library class
     * @param mixed  $param : param of library class
     * @return object instance of library class
     */
    public function lib($path, $param = NULL)
    {
        if (($pos = strpos($path, DS)) !== FALSE) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $dir   = dirname($path);
            $class = str_replace(DS, '_', $dir) . '_' . $filename;
            $file  = $dir . DS . $filename . '.php';
        } else {
            $class = $path;
            $file  = $class . '.php';
        }
        if ($pos === 0) {
            $paths = array($path);
        } else {
            $conf = $this->config['core'];
            $paths = array(
                // order of
                $conf['app_lib_path'] . $file,
                $conf['apps_lib_path'] . $file,
                $conf['lib_path'] . $file,
            );
        }
        foreach ($paths as $key => $path) {
            // set a class name except core lib.
            //$add = $key < 2 ? array('name' => $class) : array('name' => '');
            $add = array('name' => $class);
            $obj = $this->_class($path, $param, $add);
            if (is_object($obj)) {
                return $obj;
            }
        }
    }

    /**
     * Load Model as Class
     * @param string $class : model class name
     * @param mixed  $param : param of model
     * @return object instance of model
     */
    public function model($class, $param = NULL)
    {
        // load model super class
        $super = str_replace('Model_', '', $this->conf('db/default_model_super_class'));
        $conf = $this->config['core'];
        if (! empty($super)) {
            $file  = strtolower($super) . '.php';
            $paths = array(
                // order of
                $conf['app_model_path'] . $file,
                $conf['apps_model_path'] . $file,
                $conf['model_path'] . $file,
            );
            foreach ($paths as $path) {
                if (is_file($path)) {
                    $this->import($path);
                    break;
                }
            }
        }
        // load model
        $file  = $class . '.php';
        $paths = array(
            // order of
            $conf['app_model_path'] . $file,
            $conf['apps_model_path'] . $file,
        );
        foreach ($paths as $key => $path) {
            $add = array('name' => 'Model_' . ucfirst($class));
            $obj = $this->_class($path, $param, $add);
            if (is_object($obj)) {
                return $obj;
            }
        }
    }

    /**
     * Load Logic
     * @param string $dir  : module directory name
     * @param string $file : logic name
     * @param array  $params : param of logic
     * @param object $prep : prep for logic
     * @return object instance of logic
     */
    public function logic($dir, $file, array $params = array(), $prep = FALSE)
    {
        $conf = $this->config['core'];
        $name = empty($dir) ? ucfirst($file) : (ucfirst($dir) . '_' . ucfirst($file));
        $path = $conf['app_path'] . $dir . DS . $file . '.' . $conf['suffix_logic'] . '.php';
        $obj = $this->_class($path, NULL, array(
                                                'name' => $name,
                                                ));
        if (is_object($obj)) {
            $obj->params = $params;
            if (is_object($prep)) {
                $obj->prep = $prep;
            }
        }
        return $obj;
    }

    /**
     * Load Unit for logic
     * @param string $dir  : module directory name
     * @param string $file : logic name
     * @param string $func : func name
     * @return object instance of unit
     */
    public function unit($dir, $file, $func)
    {
        $obj = NULL;
        $unit_dir = $this->config['core']['app_unit_path'];
        $dir_name = empty($dir) ? '' : (ucfirst($dir) . '_');
        $pre_name = 'Unit_' . $dir_name . ucfirst($file);
        $pre_path = $unit_dir . $dir . DS . $file;
        // unit for func...
        $f_name = $pre_name . '_' . ucfirst($func);
        $f_path = $pre_path . DS . $func . '.php';
        if (is_file($f_path)) {
            $param = array();
            $this->import('lib/delegate');
            $this->import($f_path);
            if (is_subclass_of($f_name, 'Delegate')) {
                // unit for abstract...
                $s_name = $pre_name . '_Abstract';
                $s_path = $pre_path . DS . ZIU_FUNC_PREFIX . 'abstract.php';
                $param = $this->_class($s_path, array(), array('name' => $s_name), FALSE);
            }
            $obj = $this->_class($f_path, $param, array('name' => $f_name));
        }
        // unit for default...
        $d_name = $pre_name . '_Default';
        $d_path = $pre_path . DS . ZIU_FUNC_PREFIX . 'default.php';
        if (! is_object($obj) && is_file($d_path)) {
            $obj = $this->_class($d_path, array(), array('name' => $d_name));
        }
        return $obj;
    }

    /**
     * Load Prep
     * @param string $dir  : module directory name
     * @param string $file : prep name
     * @param array  $params : param of logic
     * @return object instance of prep
     */
    public function prep($dir, $file, array $params = array())
    {
        $conf = $this->config['core'];
        $name = empty($dir) ? ucfirst($file) : (ucfirst($dir) . '_' . ucfirst($file));
        $path = $conf['app_path'] . $dir . DS . $file . '.' . $conf['suffix_prep'] . '.php';
        $obj = $this->_class($path, array(), array(
                                                'name' => $name . '_Prep',
                                                ));
        if (is_object($obj)) {
            $obj->params = $params;
        }
        return $obj;
    }

    /**
     * Load Pre/End Script
     * @param string $mode : pre or end
     * @return void
     */
    public function autoload($mode)
    {
        $loader = $this; // set this loader
        switch ($mode) {
            case 'pre' :
                $pre = 's';
                $rex = '/^s\d+_.+\.php$/';
                break;
            case 'end' :
                $pre = 'e';
                $rex = '/^e\d+_.+\.php$/';
                break;
            default :
                return;
        }
        $conf = $this->config['core'];
        $paths = array(
                    $conf['init_path'],
                    $conf['apps_init_path'],
                    $conf['app_init_path']
                );
        foreach ($paths as $path) {
            foreach ((array)glob($path . $pre . '*.php') as $file) {
                $name = str_replace($path, '', $file);
                if (is_readable($file) && preg_match($rex, $name)) {
                    $this->_include($file);
                }
            }
        }
    }

    /**
     * Include
     * @param string $path   : file path
     * @param array  $params : param to extract
     * @return integer
     */
    private function _include($path, array $params = array())
    {
        extract($params);
        return include_once $path;
    }

}

