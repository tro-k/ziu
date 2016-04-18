<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base View Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_View
{

    /**
     * View name
     */
    private $view = '';

    /**
     * Params for view
     */
    private $params = array();

    /**
     * Variables for view
     */
    private $vars = array();

    /**
     * Content for default layout
     */
    private $content = FALSE;

    /**
     * Callback method name
     */
    private $callback = FALSE;

    /**
     * Layout view name
     */
    private $layout = FALSE;

    /**
     * Prep object
     */
    private $prep = FALSE;

    /**
     * Asset
     */
    private $asset = array(
        'javascript' => array(),
        'stylesheet' => array(),
    );

    /**
     * Content for
     */
    private $content_for = array();

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
        $this->conf = $this->loader->conf('core');
    }

    /**
     * Disable View
     * @return void
     */
    public function disable()
    {
        $this->layout(FALSE);
        $this->name(FALSE);
    }

    /**
     * Content for script
     * @param string $name : content name
     * @return void
     */
    public function content_for($name)
    {
        if (! isset($this->content_for[$name])) {
            $this->content_for[$name] = array('active' => FALSE, 'data' => '');
        }
        if ($this->content_for[$name]['active'] === FALSE) {
            ob_start();
            $bt = debug_backtrace();
            $file = str_replace($this->conf['app_path'], '', $bt[1]['file']);
            $line = $bt[1]['line'];
            $this->content_for[$name]['active'] = TRUE;
            $this->content_for[$name]['data'] .= "<!-- {{{ content_for_$name in $file at line $line -->\n";
        }
    }

    /**
     * Content end for script
     * @param string  $name : content name
     * @param boolean $packer : Flag to pack
     * @return void
     */
    public function content_end_for($name, $packer = TRUE)
    {
        if (isset($this->content_for[$name])) {
            if ($this->content_for[$name]['active'] === TRUE) {
                $this->content_for[$name]['data'] .= $packer ? $this->packer($name, ob_get_clean()) : ob_get_clean();
                $this->content_for[$name]['data'] .= "<!-- }}} -->\n";
            }
            $this->content_for[$name]['active'] = FALSE;
        }
    }

    /**
     * Packer
     * @param string $name : content name
     * @param string $data : content data
     * @return string
     */
    public function packer($name, $data)
    {
        switch ($name) {
            case 'javascript' :
                static $jp = NULL;
                if (is_null($jp)) {
                    $this->loader->import('vendor/packer.php/class.JavaScriptPacker.php');
                }
                $jp = new JavaScriptPacker($data, 'None', TRUE, FALSE);
                $data = $jp->pack() . "\n";
                break;
            case 'stylesheet' :
                $this->loader->import('vendor/css-packer-function-php.php');
                $data = packCSS($data) . "\n";
                break;
            default :
        }
        return $data;
    }

    /**
     * Asset
     * @param string $type : asset type
     * @param mixed  $args : asset data
     * @return void
     */
    public function asset_for($type, $args)
    {
        $args = func_get_args();
        $type = array_shift($args);
        if (strpos($type, '[')) {
            list($type, $name) = explode('[', $type);
            $name = $name === FALSE ? 'default' : trim($name, ']');
        } else {
            $name = 'default';
        }
        if (! isset($this->asset[$type][$name])) {
            $this->asset[$type][$name] = array();
        }
        $this->asset[$type][$name] = array_merge($this->asset[$type][$name], $args);
    }

    /**
     * Asset javascript
     * @param mixed $mode : asset mode or direct val
     * @return string
     */ 
    public function asset_javascript($mode = 'all')
    {
        $tag = '';
        switch ($mode) {
            case 'all' :
                foreach ($this->asset['javascript'] as $val) {
                    foreach ($val as $v) {
                        $tag .= $this->_asset_javascript($v);
                    }
                }
                break;
            default :
                if (isset($this->asset['javascript'][$mode])) {
                    foreach ($this->asset['javascript'][$mode] as $val) {
                        $tag .= $this->_asset_javascript($val);
                    }
                } else {
                    $tag .= $this->_asset_javascript($mode);
                }
        }
        return $tag;
    }

    /**
     * Asset stylesheet
     * @param mixed $mode : asset mode or direct val
     * @return string
     */ 
    public function asset_stylesheet($mode = 'all')
    {
        $tag = '';
        switch ($mode) {
            case 'all' :
                foreach ($this->asset['stylesheet'] as $val) {
                    foreach ($val as $v) {
                        $tag .= $this->_asset_stylesheet($v);
                    }
                }
                break;
            default :
                if (isset($this->asset['stylesheet'][$mode])) {
                    foreach ($this->asset['stylesheet'][$mode] as $val) {
                        $tag .= $this->_asset_stylesheet($val);
                    }
                } else {
                    $tag .= $this->_asset_stylesheet($mode);
                }
        }
        return $tag;
    }

    /**
     * Asset javascript
     * @param mixed $val : path or attr
     * @return string
     */
    private function _asset_javascript($val)
    {
        $tag = '';
        if (is_array($val) && isset($val['src'])) {
            $tmp = array();
            foreach ($val as $k => $v) {
                if ($k == 'src') {
                    $attr = 'src="' . $this->asset_url($val['src']);
                } else {
                    $attr = $k . '="' . $v . '"';
                }
                $tmp[] = $attr;
            }
            $tmp = implode(' ', $attr);
            $tag .= '<script type="text/javascript" ' . $tmp . '></script>' . "\n";
        } else {
            $url = $this->asset_url($val);
            $tag .= '<script type="text/javascript" src="' . $url . '"></script>' . "\n";
        }
        return $tag;
    }

    /**
     * Asset stylesheet
     * @param mixed $val : path or attr
     * @return string
     */
    private function _asset_stylesheet($val)
    {
        $tag = '';
        if (is_array($val) && isset($val['href'])) {
            $tmp = array();
            foreach ($val as $k => $v) {
                if ($k == 'href') {
                    $attr = 'href="' . $this->asset_url($val['href']);
                } else {
                    $attr = $k . '="' . $v . '"';
                }
                $tmp[] = $attr;
            }
            $tmp = implode(' ', $attr);
            $tag .= '<link type="text/css" rel="stylesheet" ' . $tmp . ' />' . "\n";
        } else {
            $url = $this->asset_url($val);
            $tag .= '<link type="text/css" rel="stylesheet" href="' . $url . '" />' . "\n";
        }
        return $tag;
    }

    /**
     * Asset url
     * @param string $uri : uri
     * @return string
     */
    public function asset_url($url)
    {
        if ((string)$url[0] === '/' || preg_match('/^https\?:\/\/[^\/]+/', (string)$url)) {
            return $url;
        } else {
            return $this->dispatched_uri($url);
        }
    }

    /**
     * Dispatched uri
     * @param string $uri : uri
     * @return string
     */
    public function dispatched_uri($uri)
    {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            static $docroot_path = NULL;
            if (is_null($docroot_path)) {
                $docroot_path = $_SERVER['DOCUMENT_ROOT'];
                if (strpos(ZIU_DISPATCH_PATH, $docroot_path) !== 0) {
                    // if symlink, search real path as docroot_path
                    $docroot_dirs = explode(DS, $docroot_path);
                    $tmp_path = $docroot_path;
                    $tmp_dirs = array();
                    for ($i = count($docroot_dirs) - 1; $i >= 0; $i--) {
                        if (is_link($tmp_path)) {
                            $docroot_path = readlink($tmp_path);
                            if (! empty($tmp_dirs)) {
                                $docroot_path .= DS . implode(DS, array_reverse($tmp_dirs));
                            }
                            break;
                        }
                        $tmp_dirs[] = array_pop($docroot_dirs);
                        $tmp_path = implode(DS, $docroot_dirs);
                    }
                }
            }
            if ($docroot_path[1] == '/') {
                // abusolute path
                $tmp_dispatch_path = ZIU_DISPATCH_PATH;
            } else {
                // relative path
                $docroot_path = ltrim($docroot_path, '.');
                $tmp_dispatch_path = substr(ZIU_DISPATCH_PATH, strpos(ZIU_DISPATCH_PATH, $docroot_path));
            }
            $dirs = explode(DS, trim(substr($tmp_dispatch_path, strlen($docroot_path)), DS));
            array_push($dirs, ltrim($uri, '/'));
            return '/' . ltrim(implode('/', $dirs), '/');
        } else {
            return $uri;
        }
    }

    /**
     * Set/Get Layout Name
     * @param string $name : layout of view name
     * @return mixed layout name or void
     */
    public function layout($name = TRUE)
    {
        if ($name === TRUE) {
            return $this->layout;
        } else {
            $this->layout = str_replace('/', DS, $name);
        }
    }

    /**
     * Set variables
     * @param array   $params   : variables for view
     * @param boolean $security : flag to use security
     * @return void
     */
    public function set(array $params, $security = TRUE)
    {
        $vars = $this->vars;
        if ($security === TRUE) {
            foreach ($params as $key => $val) {
                $vars[$key] = $this->_security($val);
            }
        } else {
            $vars = array_merge($vars, $params);
        }
        $this->vars = $vars;
    }

    /**
     * Sanitize for security
     * @param mixed $val : variables for view
     * @return mixed secured variables
     */
    private function _security($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = $this->_security($v);
            }
        } else {
            $val = htmlspecialchars($val, ENT_QUOTES);
        }
        return $val;
    }

    /**
     * Set/Get prep
     * @param object $prep : prep object
     * @return mixed prep object or void
     */
    public function prep($prep = FALSE)
    {
        if ($prep === FALSE) {
            return $this->prep;
        } else {
            if (is_object($prep)) {
                $this->prep = $prep;
            }
        }
    }

    /**
     * Set/Get params
     * @param array $params : view params
     * @return mixed array or void
     */
    public function param($params = FALSE)
    {
        if ($params === FALSE) {
            return $this->params;
        } else {
            $this->params = array_merge($this->params, (array)$params);
        }
    }

    /**
     * Set/Get view Name
     * @param string $name : view name
     * @return mixed view name or void
     */
    public function name($name = TRUE)
    {
        if ($name === TRUE) {
            return $this->view;
        } else {
            $this->view = str_replace('/', DS, $name);
        }
    }

    /**
     * Rendering view
     * @param string $name   : view name
     * @param array  $params : view params
     * @param boolean $flush : flag to flush
     * @return void
     */
    public function render($name, $params = array(), $flush = TRUE)
    {
        if (is_array($params) && ! empty($params)) {
            $this->param($params);
        }
        $this->name($name);
        $file = $this->conf['view_path'] . $this->name() . '.' . $this->conf['suffix_view'] . '.php';
        if (is_file($file)) {
            return $this->_rendering($file, $flush);
        }
    }

    /**
     * Execute
     * @return void
     */
    public function execute()
    {
        $this->render($this->name());
        $this->name(NULL); // Init...
    }

    /**
     * Content
     * @return string view content
     */
    public function content()
    {
        if ($this->content !== FALSE) {
            $content = $this->content;
            $this->content = '';
            return $content;
        }
    }

    /**
     * Rendering
     * @param string  $path  : path of view file
     * @param boolean $flush : flag to flush
     * @return void
     */
    private function _rendering($path, $flush = TRUE)
    {
        $params = $this->params;
        $loader = $this->loader;
        $prep   = $this->prep;
        extract($this->vars);
        if ($flush === FALSE) {
            ob_start();
            include $path;
            return ob_get_clean();
        } else {
            include $path;
        }
    }

    /**
     * Layouting
     * @return boolean
     */
    private function _layouting()
    {
        $dirs = array(
                    $this->conf['app_layout_path'],
                    $this->conf['apps_layout_path'],
                    $this->conf['layout_path']
                );
        $file = $this->layout . '.' . $this->conf['suffix_view'] . '.php';
        foreach ($dirs as $dir) {
            $path = $dir . $file;
            if (is_file($path)) {
                foreach ($this->content_for as $key => $val) {
                    if ($val['active'] === FALSE) {
                        ${'content_for_' . $key} = (string)$val['data'];
                    }
                }
                unset($dirs, $dir, $key, $val);
                $params = $this->params;
                extract($this->vars);
                include $path;
                return TRUE;
                break;
            }
        }
        return FALSE;
    }

    /**
     * Output
     * @param string $mode : start or end
     * @return void
     */
    public function output($mode)
    {
        switch ($mode) {
            case 'start' :
                // Output buffering with callback
                $callback = $this->conf['view_buffer_callback'];
                if ($callback && is_callable($callback)) {
                    $this->callback = $callback;
                    ob_start($this->callback);
                }
                // Output buffering with layout
                ob_start();
                if ($layout = $this->conf['view_layout_default']) {
                    $this->layout = $layout;
                }
                break;
            case 'end' :
                // Output buffering with layout
                $this->content = ob_get_clean();
                if (! $this->layout || $this->_layouting() === FALSE) {
                    // Can't load layout
                    echo($this->content);
                }
                // Output buffering with callback
                if ($this->callback) {
                    ob_end_flush();
                }
                break;
            default :
        }
    }

}

