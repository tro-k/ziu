<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Function Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */


// {{{ init
if (! function_exists('invoke')) {
    function invoke($name, array $params = array(), $flush = TRUE)
    {
        global $loader;
        return $loader->core('init')->invoke($name, $params, $flush);
    }
}
// }}}

// {{{ loader
if (! function_exists('conf')) {
    function conf($name)
    {
        global $loader;
        return $loader->conf($name);
    }
}

if (! function_exists('lib')) {
    function lib($name, $param = NULL)
    {
        global $loader;
        return $loader->lib($name, $param);
    }
}

if (! function_exists('model')) {
    function model($name, $param = NULL)
    {
        global $loader;
        return $loader->model($name, $param);
    }
}

if (! function_exists('help')) {
    function help($name)
    {
        global $loader;
        return $loader->help($name);
    }
}

if (! function_exists('import')) {
    function import($path)
    {
        global $loader;
        $loader->import($path);
    }
}
// }}}

// {{{ view
if (! function_exists('redirect_to')) {
    function redirect_to($url, $code = 302)
    {
        // 301 Moved Permanently
        // 302 Found
        // 303 See Other
        // 307 Temporary Redirect
        header('Location: ' . redirect_uri($url), TRUE, $code);
        exit;
    }
}

if (! function_exists('redirect_uri')) {
    function redirect_uri($url, $https = 'HTTPS')
    {
        if (! preg_match("#^https?://#", $url)) {
            $scheme = (! empty($_SERVER[$https]) && $_SERVER[$https] !== 'off')
                        ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $path = $url[0] != '/' ? uri('/' . $url) : $url;
            $url  = $scheme . '://' . $host . '/' . ltrim($path, '/');
        }
        return $url;
    }
}

if (! function_exists('render')) {
    function render($name, array $params = array(), $flush = TRUE)
    {
        global $loader;
        return $loader->core('view')->render($name, $params, $flush);
    }
}

if (! function_exists('render_content')) {
    function render_content()
    {
        global $loader;
        return $loader->core('view')->content();
    }
}

if (! function_exists('render_set')) {
    function render_set(array $vars, $security = TRUE)
    {
        global $loader;
        $loader->core('view')->set($vars, $security);
    }
}

if (! function_exists('render_name')) {
    function render_name($name = TRUE, array $vars = array(), $security = TRUE)
    {
        global $loader;
        $loader->core('view')->set($vars, $security);
        return $loader->core('view')->name($name);
    }
}

if (! function_exists('layout_name')) {
    function layout_name($name = TRUE)
    {
        global $loader;
        return $loader->core('view')->layout($name);
    }
}

if (! function_exists('url')) {
    function url($url)
    {
        global $loader;
        return $loader->core('view')->asset_url($url);
    }
}

if (! function_exists('uri')) {
    function uri($uri)
    {
        global $loader;
        return $loader->core('view')->dispatched_uri($uri);
    }
}

if (! function_exists('json_view')) {
    function json_view($result)
    {
        layout_name(FALSE);
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($result));
        exit;
    }
}

if (! function_exists('asset_javascript_tag')) {
    function asset_javascript_tag($mode = 'all')
    {
        global $loader;
        return $loader->core('view')->asset_javascript($mode);
    }
}

if (! function_exists('asset_stylesheet_tag')) {
    function asset_stylesheet_tag($mode = 'all')
    {
        global $loader;
        return $loader->core('view')->asset_stylesheet($mode);
    }
}

if (! function_exists('asset_for')) {
    function asset_for($type, $args)
    {
        global $loader;
        $args = func_get_args();
        $type = array_shift($args);
        foreach ($args as $val) {
            $loader->core('view')->asset_for($type, $val);
        }
    }
}

if (! function_exists('content_for')) {
    function content_for($type)
    {
        global $loader;
        $loader->core('view')->content_for($type);
    }
}

if (! function_exists('content_end_for')) {
    function content_end_for($type, $packer = TRUE)
    {
        global $loader;
        $loader->core('view')->content_end_for($type, $packer);
    }
}
// }}}

// {{{ env
if (! function_exists('is_dev')) {
    function is_dev()
    {
        global $loader;
        return $loader->core('env')->is_dev();
    }
}

if (! function_exists('is_cli')) {
    function is_cli()
    {
        global $loader;
        return $loader->core('env')->is_cli();
    }
}

if (! function_exists('is_ajax')) {
    function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
}
// }}}

// {{{ router
if (! function_exists('is_module')) {
    function is_module($uri)
    {
        global $loader;
        return $loader->core('router')->is_module($uri);
    }
}

if (! function_exists('module_uri')) {
    function module_uri($main = FALSE)
    {
        global $loader;
        return $loader->core('router')->r_module($main);
    }
}

if (! function_exists('action_uri')) {
    function action_uri($main = FALSE)
    {
        global $loader;
        return $loader->core('router')->r_action($main);
    }
}

if (! function_exists('main_uri')) {
    function main_uri()
    {
        global $loader;
        return $loader->core('uri')->parse();
    }
}
// }}}

// {{{ log
if (! function_exists('logger')) {
    function logger($level, $msg, $method = NULL)
    {
        global $loader;
        return $loader->core('log')->write($level, $msg, $method);
    }
}

if (! function_exists('l_debug')) {
    function l_debug($msg, $method = NULL)
    {
        global $loader;
        return $loader->core('log')->debug($msg, $method);
    }
}

if (! function_exists('l_info')) {
    function l_info($msg, $method = NULL)
    {
        global $loader;
        return $loader->core('log')->info($msg, $method);
    }
}

if (! function_exists('l_warn')) {
    function l_warn($msg, $method = NULL)
    {
        global $loader;
        return $loader->core('log')->warning($msg, $method);
    }
}

if (! function_exists('l_error')) {
    function l_error($msg, $method = NULL)
    {
        global $loader;
        return $loader->core('log')->error($msg, $method);
    }
}
// }}}

// {{{ debug
if (! function_exists('ticker')) {
    function ticker($exec = TRUE)
    {
        if (! class_exists('Ticker')) {
            import('lib/ticker');
            declare(ticks=1);
            register_shutdown_function(array('Ticker', 'display'));
        }
        if ($exec) {
            Ticker::init(ZIU_START_TIME, ZIU_START_MEMORY);
            register_tick_function(array('Ticker', 'profile'));
        } else {
            unregister_tick_function(array('Ticker', 'profile'));
            Ticker::stop();
        }
    }
}
// }}}

// {{{ database
if (! function_exists('database_connect')) {
    function database_connect()
    {
        static $db = NULL;
        if (is_null($db)) {
            global $loader;
            $conf = $loader->conf('db');
            $info = $conf['connection'][$conf['connection_name']];
            $db = clone $loader->lib('database', $info);
        }
        return $db;
    }
}
// }}}

// {{{ other
if (! function_exists('paginate')) {
    function paginate($page_id, $limit_row = 20, $config = array())
    {
        global $loader;
        return $loader->lib('paginate', array(
            'page_num'  => (int)$page_id,
            'limit_row' => (int)$limit_row,
        ) + (array)$config);
    }
}
// }}}

