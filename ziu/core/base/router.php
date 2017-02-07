<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Router Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Router
{

    /**
     * Routed Parts
     */
    private $conf;
    private $directory, $class, $method, $args, $action;
    private $routes = array(); // routes setting from conf
    private $_def, $_404, $_500; // static routes
    private $main_segments = FALSE;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Init
     * @return void
     */
    public function init()
    {
        // set config
        $this->conf = $this->loader->conf('core');
        // set mapping route
        $this->routes = $this->loader->conf('routes');
        $this->env = $this->loader->conf('env');
    }

    /**
     * Configure
     * @return object this
     */
    public function config($config = array())
    {
        if (isset($config['routes'])) {
            $this->routes = $config['routes'] + $this->routes;
        }
        // set static route setting
        $this->_def = (! isset($this->routes['_def_']) || $this->routes['_def_'] == '') ? FALSE : strtolower($this->routes['_def_']);
        $this->_404 = (! isset($this->routes['_404_']) || $this->routes['_404_'] == '') ? FALSE : strtolower($this->routes['_404_']);
        $this->_500 = (! isset($this->routes['_500_']) || $this->routes['_500_'] == '') ? FALSE : strtolower($this->routes['_500_']);
        return $this;
    }

    /**
     * Routing
     * @param string $uri : uri of routing
     * @return object this clone
     */
    public function routing($uri)
    {
        // reset
        $this->directory = NULL;
        $this->class     = NULL;
        $this->method    = NULL;
        $this->args      = NULL;
        $this->action    = NULL;
        $this->config();
        // parse
        $this->_parse($uri);
        if ($this->main_segments === FALSE) {
            // set segments, if first routing
            $this->main_segments = $this->r_segments();
        }
        return clone $this;
    }

    /**
     * Get default controller
     * @return string
     */
    private function _get_def()
    {
        static $uri = NULL;
        if ($this->_def === FALSE) {
            $this->_show_http_message('No _def_ setting');
        } elseif ($this->_def === '') {
            $this->_show_http_message('No _def_ module ' . $uri);
        } elseif (is_null($uri)) {
            // remove _def_, when use at 1st time.
            // prevent infinity loop.
            $uri = $this->_is_module($this->_def) ? $this->_def : 'index/index';
            $this->_def = '';
        }
        return $uri;
    }

    /**
     * Show http message
     * @param string  $message : http status message
     * @return void
     */
    private function _show_http_message($message)
    {
        $this->loader->core('log')->error($message, __METHOD__);
        die($message);
    }

    /**
     * Get http status controller
     * @param integer $code : http status code
     * @return string
     */
    private function _get_http_status($code)
    {
        static $uri = NULL;
        // set http header
        switch ($code) {
            case '404' :
                $status = 'Not Found';
                break;
            default :
                $status = 'Internal Server Error';
        }
        if (! headers_sent()) {
            header("HTTP/1.0 {$code} {$status}");
        }
        // set http status message
        $var = '_' . $code;
        if (! isset($this->$var) || $this->$var === FALSE) {
            $this->_show_http_message("No _{$code}_ setting");
        } elseif ($this->$var === '') {
            $this->_show_http_message("No _{$code}_ module " . $uri);
        } elseif (is_null($uri)) {
            // remove _uri_, when use at 1st time.
            // prevent infinity loop.
            $uri = $this->$var;
            $this->$var = ''; // if file on $uri no existed, stop with _show_http_message().
        }
        return $uri;
    }

    /**
     * Set route
     * @param string $uri : uri of routing
     * @return void
     */
    private function _set_route($uri = '')
    {
        $is_module_dir = FALSE;
        $segments = $this->_explore($uri, $is_module_dir);
        if ($is_module_dir) {
            $this->directory = $this->_adjust_string(array_shift($segments));
        }
        if (count($segments) == 1) {
            // adjust default method with no indication
            // ex. /hoge route Hoge::index
            // or  /hoge/fuga route Hoge_Fuga::index
            $segments = array($segments[0], 'index');
        }
        $this->class = $this->_adjust_string(array_shift($segments));
        if (is_numeric($segments[0])) {
            // ex. /hoge/edit/23 route Hoge_Edit::index(23)
            $this->method = 'index';
        } else {
            $this->method = $this->_adjust_string(array_shift($segments));
        }
        // Over segments
        if (! empty($segments)) {
            foreach ($segments as $key => $val) {
                $segments[$key] = $this->_adjust_string($val);
            }
        }
        $this->args = $segments;
    }

    /**
     * Is module?
     * @param string $path : path of module in app
     * @return boolean
     */
    public function is_module($path)
    {
        return $this->_is_module($path);
    }

    /**
     * Is module?
     * @param string $path : path of module in app
     * @return boolean
     */
    private function _is_module($path)
    {
        $result = FALSE;
        foreach (explode(',', $this->conf['suffix_join']) as $type) {
            $dir = $type == $this->conf['suffix_view'] ? $this->conf['view_path'] : $this->conf['app_path'];
            $file = $dir . str_replace('/', DS, $path) . '.' . $type . '.php';
            if (file_exists($file)) {
                $result = TRUE;
                break;
            }
        }
        return $result;
    }

    /**
     * Is module dir
     * @param string $path : path of module directory in app
     * @return boolean
     */
    private function _is_module_dir($path)
    {
        return is_dir($this->conf['app_path'] . $path) || is_dir($this->conf['view_path'] . $path);
    }

    /**
     * Explore uri
     * @param string  $uri            : uri
     * @param boolean &$is_module_dir : flag of existing module dir
     * @return array segments
     */
    private function _explore($uri, &$is_module_dir)
    {
        $segments = explode('/', $uri);
        if (($cnt = count($segments)) == 0 || ($cnt == 1 && $segments[0] === '')) {
            // no segments...(ex. /)
            return $this->_explore($this->_get_def(), $is_module_dir);
        }
        if ($this->_is_module($segments[0])) {
            // controller exists under app dir...
            if (isset($segments[1])) {
                // (ex. /hoge/fuga route Hoge::fuga)
                return $segments;
            } else {
                // (ex. /hoge route Hoge::Index)
                return array($segments[0], 'index');
            }
        }
        if ($is_module_dir = $this->_is_module_dir($segments[0])) {
            // module dir exists ...(ex. /hoge/ or /hoge/fuga)
            // if indicated controller name...
            if (count($segments) > 1) {
                if ($this->_is_module($segments[0] . DS . $segments[1])) {
                    // (ex. /hoge/fuga route Hoge_Fuga::[method])
                    return $segments;
                } elseif ($this->_is_module($segments[0] . DS . 'index')) {
                    // (ex. /hoge/fuga route Hoge_Index::fuga)
                    return array_merge(array($segments[0], 'index'), array_slice($segments, 1));
                }
            } elseif ($this->_is_module($segments[0] . DS . 'index')) {
                    // (ex. /hoge/ route Hoge_Index::index)
                    return array($segments[0], 'index', 'index');
            }
        }
        // 404 not found
        return $this->_explore($this->_get_http_status('404'), $is_module_dir);
    }

    /**
     * Parse routes
     * @param string $uri : uri of routing
     * @return void
     */
    private function _parse($uri)
    {
        if (isset($this->routes[$uri])) {
            // set match string in routes.php
            $uri = $this->routes[$uri];
        } else {
            foreach ($this->routes as $key => $val) {
                // looking for regex in routes.php
                $rex = array(
                            array(':any', '.*'),
                            array(':num', '[0-9]+'),
                            array(':seg', '[^/]+'),
                        );
                foreach ($rex as $r) {
                    $key = str_replace($r[0], $r[1], $key);
                }
                if (preg_match('#^' . $key . '$#', $uri)) {
                    // match regex routes...
                    if (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE) {
                        // if exist $ reference, replace...
                        $val = preg_replace('#^' . $key . '$#', $val, $uri);
                    }
                    $uri = $val;
                    break;
                }
            }
        }
        // set route
        $this->_set_route(trim($uri, '/'));
    }

    /**
     * adjust string
     */
    private function _adjust_string($str)
    {
        return str_replace(array(DS, '.'), '', $str);
    }

    // {{{ getter
    /**
     * Get the route action string
     * ex. directory/class/method/arguments
     * @param boolean $main : flag to return main segments
     * @return string action uri
     */
    public function r_action($main = FALSE)
    {
        $segments = $this->r_segments($main);
        $args = array_pop($segments);
        return trim(implode('/', $segments) . ($args ? ('/' . implode('/', $args)) : ''), '/');
    }

    /**
     * Get the route module name
     * ex. directory/class/method
     * @param boolean $main : flag to return main segments
     * @return string module uri
     */
    public function r_module($main = FALSE)
    {
        $segments = $this->r_segments($main);
        unset($segments['args']);
        return trim(implode('/', $segments), '/');
    }

    /**
     * Get the route segments
     * @param boolean $main : flag to return main segments
     * @return array segments
     */
    public function r_segments($main = FALSE)
    {
        return $main ? $this->main_segments : array(
            'directory' => $this->directory,
            'class'     => $this->class,
            'method'    => ($this->method == $this->class ? 'index' : $this->method),
            'args'      => $this->args,
        );
    }
    // }}}

}

