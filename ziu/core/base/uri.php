<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Uri Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Uri
{

    /**
     * Constructor
     */
    function __construct()
    {
    }

    /**
     * Parse
     * @return string
     */
    public function parse()
    {
        $this->_parse_uri_string();
        return $this->uri_string;
    }

    /**
     * Parse Uri String
     * @return void
     */
    private function _parse_uri_string()
    {
        if ($this->loader->core('env')->is_cli()) {
            // argv from cli
            $this->_set_uri_string($this->_detect_from_cli());
        } elseif ($uri = $this->_detect_from_req()) {
            // REQUEST_URI from web
            $this->_set_uri_string($uri);
        } else {
            $self = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
            $path = trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO'), '/');
            $query = trim(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING'), '/');
            if ($path != '' && $path != $self) {
                // PATH_INFO from web
                $this->_set_uri_string($path);
            } elseif ($query != '') {
                // QUERY_STRING from web
                $this->_set_uri_string($query);
            } else {
                // no uri...
                $this->_set_uri_string('');
            }
        }
    }

    /**
     * Set Uri String
     * @param string $str : Request uri
     * @return void
     */
    private function _set_uri_string($str)
    {
        $this->uri_string = trim(str_replace(array('//', '../'), '/', $str), '/');
    }

    /**
     * Get Uri String from Cli
     * @return string
     */
    private function _detect_from_cli()
    {
        $args = array_slice($_SERVER['argv'], 1);
        $uri = $args ? '/' . trim(implode('/', $args), '/') : '';
        if ($uri == '/' || empty($uri)) {
            $result = '/';
        } else {
            $uri = parse_url($uri);
            $result = $uri['path'];
        }
        return $result;
    }

    /**
     * Get Uri String from Req
     * @return string
     */
    private function _detect_from_req()
    {
        $result = '';
        if (! isset($_SERVER['REQUEST_URI']) || ! isset($_SERVER['SCRIPT_NAME'])) {
            // no request
            return $result;
        }
        // uri
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            // remove filepath of uri, including script path like '/index.php'
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            // remove dirpath of uri, just dir script path like '/dirname'
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }
        // parts of query string
        $parts = explode('?', $uri);
        $uri = $parts[0];
        // modify $_GET using REQUEST_URI
        if (isset($parts[1])) {
            $_SERVER['QUERY_STRING'] = $parts[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            $_SERVER['QUERY_STRING'] = '';
            $_GET = array();
        }
        // result
        if ($uri == '/' || empty($uri)) {
            // top(default)
            $result = '/';
        } else {
            $uri = parse_url($uri);
            $result = $uri['path'];
        }
        return $result;
    }

}

