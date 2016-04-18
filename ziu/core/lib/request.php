<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Request Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Request
{

    private $get, $post, $cookie, $files;

    const FALLBACK  = null;
    const SEP_ARRAY = '/';
    const SEP_MULTI = ',';

    public function __construct()
    {
        $this->get    = isset($_GET)    ? $_GET    : array();
        $this->post   = isset($_POST)   ? $_POST   : array();
        $this->cookie = isset($_COOKIE) ? $_COOKIE : array();
        $this->files  = isset($_FILES)  ? $_FILES  : array();
    }

    public function get($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return self::_ntrim($this->raw_get($key, $fallback, $regex));
    }

    public function raw_get($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return $this->_fetch('get', $key, $fallback, $regex);
    }

    public function post($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return self::_ntrim($this->raw_post($key, $fallback, $regex));
    }

    public function raw_post($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return $this->_fetch('post', $key, $fallback, $regex);
    }

    public function cookie($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return self::_ntrim($this->raw_cookie($key, $fallback, $regex));
    }

    public function raw_cookie($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return $this->_fetch('cookie', $key, $fallback, $regex);
    }

    public function files($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return self::_ntrim($this->raw_files($key, $fallback, $regex));
    }

    public function raw_files($key = null, $fallback = self::FALLBACK, $regex = null)
    {
        return $this->_fetch('files', $key, $fallback, $regex);
    }

    /**
     * recursive ntrim
     * Removing all control code exclude TAB, LF, CR, SPACE and trim too.
     */
    protected static function _ntrim($mixed)
    {
        if (is_scalar($mixed) && ! is_bool($mixed)) {
            // Removing all control code except TAB, LF, CR, SPACE.
            // \x09: TAB
            // \x0a: LF
            // \x0d: CR
            // \x20: SPACE
            $mixed = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u', '', $mixed);
            return trim($mixed);
        } elseif (is_array($mixed)) {
            return array_map(array('self', '_ntrim'), $mixed);
        } else {
            return $mixed;
        }
    }

    protected function _fetch($kind, $key, $fallback, $regex = null)
    {
        switch ($kind) {
            case 'get' :
            case 'post' :
            case 'files' :
            case 'cookie' :
                $var =& $this->{$kind};
                break;
            default:
                $var = null;
        }
        // get all
        if ($key === null) return $var;
        // for multi
        if (strpos($key, self::SEP_MULTI) !== false) {
            $ret = array();
            $keys = explode(self::SEP_MULTI, $key);
            foreach ($keys as $k) {
                if (strpos($k, self::SEP_ARRAY) !== false) {
                    // array
                    self::_prep_array($ret, explode(self::SEP_ARRAY, $k)
                        , self::_array($k, $var, $fallback, $regex));
                } else {
                    // scalar
                    $ret[$k] = self::_scalar($k, $var, $fallback, $regex);
                }
            }
            return $ret;
        }
        // for array
        if (strpos($key, self::SEP_ARRAY) !== false) {
            $ret = array();
            self::_prep_array($ret, explode(self::SEP_ARRAY, $key)
                , self::_array($key, $var, $fallback, $regex));
            return $ret;
        }
        // for scalar
        return self::_scalar($key, $var, $fallback, $regex);
    }

    protected static function _prep_array(&$ret, $hash, $value)
    {
        $key = array_shift($hash);
        if (empty($hash)) {
            $ret[$key] = $value;
        } else {
            self::_prep_array($ret[$key], $hash, $value);
        }
    }

    protected static function _scalar($key, $var, $fallback, $regex)
    {
        $key = trim($key);
        if ( ! isset($var[$key])) {
            return $fallback;
        }
        if ($regex !== null) {
            return self::_regex($var[$key], $regex, $fallback);
        }
        return $var[$key];
    }

    protected static function _array($key, $var, $fallback, $regex)
    {
        $keys = explode(self::SEP_ARRAY, $key);
        $keys = array_map('trim', $keys);
        foreach ($keys as $k) {
            if ( ! isset($var[$k])) {
                return $fallback;
            } else {
                $var = $var[$k];
            }
        }
        if ($regex !== null) {
            return  self::_regex($var, $regex, $fallback);
        } else {
            return $var;
        }
    }

    protected static function _regex($mixed, $regex, $fallback)
    {
        if (is_scalar($mixed) && ! is_bool($mixed)) {
            return preg_match($regex, $mixed) ? $mixed : $fallback;
        } elseif (is_array($mixed)) {
            $buf = array();
            foreach ($mixed as $k => $v) {
                $buf[$k] = self::_regex($v, $regex, $fallback);
            }
            return $buf;
        } else {
            return $mixed;
        }
    }

}

