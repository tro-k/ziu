<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Validate Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

class Validate
{

    /**
     * Variables
     */
    private $mb = FALSE;
    private $action = 'default';
    private static $state = array();
    private $field = array();
    private $rules = array();
    private $class = array();
    private $error = array();
    private $valid = array();
    private $args = array(
        // {{{ validation arguments
        // string type
        'trim'     => array(),
        'required' => array(),
        'value'    => array('string', 'integer'),
        'match'    => array('string'),
        'minlen'   => array('integer'),
        'maxlen'   => array('integer'),
        'alpha'    => array(),
        'alnum'    => array(),
        'aldash'   => array(),
        'base64'   => array(),
        // internet / communication type
        'email'    => array(),
        'emails'   => array('string'),
        'ip'       => array(),
        'domain'   => array(),
        'url'      => array(),
        'ktaimail' => array(),
        'zip'      => array(),
        'tel'      => array(),
        // number type
        'minnum'   => array('integer'),
        'maxnum'   => array('integer'),
        'numeric'  => array(),
        'isnum'    => array(),
        'integer'  => array(),
        'isint'    => array(),
        'decimal'  => array(),
        'natural'  => array(),
        'nozero'   => array(),
        'range'    => array(),
        // datetime type
        'date'         => array(),
        'time'         => array(),
        'mindate'      => array('string'),
        'maxdate'      => array('string'),
        'termdate'     => array('string', 'string'),
        'termdatetime' => array('string', 'string'),
        // japanese type
        'hiragana' => array(),
        'fullkana' => array(),
        'halfkana' => array(),
        'kanji'    => array(),
        // }}}
    );
    private $config = array(
        'subdir' => '',
        'lang' => 'ja',
        'separator' => ',',
        'get_errors_type' => 'string', // string or array
        'regex' => array(
            'trim' => '/^(\x20|\xe3\x80\x80).*|.*(\x20|\xe3\x80\x80)$/',
            'alpha' => '/^([a-z])+$/i',
            'alnum' => '/^([a-z0-9])+$/i',
            'aldash' => '/^([-a-z0-9_-])+$/i',
            'base64' => '/[^a-zA-Z0-9\/\+=]/',
            'email' => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
            'domain' => '/^([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
            'url' => '/^(https?|ftp):\/\/[-_\.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+$/i',
            'ktaimail' => '/(@|\.)(docomo\.ne\.jp|ezweb\.ne\.jp|softbank\.ne\.jp|vodafone\.ne\.jp|willcom\.com|pdx\.ne\.jp|disney\.ne\.jp)$/',
            'zip' => '/^[0-9]{3}\-?[0-9]{4}$/',
            'tel' => '/^([0-9]{2,})\-?([0-9]{2,})\-?([0-9]{4,})$/',
            'numeric' => '/^[\-+]?[0-9]*\.?[0-9]+$/',
            'integer' => '/^[\-+]?[0-9]+$/',
            'decimal' => '/^[\-+]?[0-9]+\.[0-9]+$/',
            'natural' => '/^[0-9]+$/',
            'hiragana' => array(
                'euc-jp' => '/^(?:\xa4[\xa1-\xf3]|\xa1[\xb5\xb6\xab])+$/',
                'shift_jis' => '/^(?:\x82[\x9f-\xf1]|\x81[\x4a\x54\x55])+$/',
                'utf-8' => '/^(?:\xe3\x81[\x81-\xbf]|\xe3\x82[\x80-\x96]|\xe3\x82[\x99-\x9f]|\xe3\x83[\xbb-\xbc])+$/',
            ),
            'fullkana' => array(
                'euc-jp' => '/^(?:\xa5[\xa1-\xf6]|\xa1[\xb3\xb4\xbc])+$/',
                'shift_jis' => '/^(?:\x83[\x40-\x96]|\x81[\x52\x53\x5b])+$/',
                'utf-8' => '/^(?:\xe3\x82[\xa1-\xbf]|\xe3\x83[\x80-\xbf]|\xe3\x87[\xb0-\xbf])+$/',
            ),
            'halfkana' => array(
                'euc-jp' => '/^(?:\x8e[\xa6-\xdf])+$/',
                'shift_jis' => '/^(?:\xa6-\xdf)+$/',
                'utf-8' => '/^(?:\xef\xbd[\xa5-\xbf]|\xef\xbe[\x80-\x9f])+$/',
            ),
            'kanji' => array(
                'euc-jp' => '/^(?:[\xb0-\xf4][\xa1-\xfe]|[\xf9-\xfc][\xa1-\xfe]|\x8f[\xb0-\xf4][\xa1-\xfe])+$/',
                'shift_jis' => '/^(?:[\x88-\x9f][\x40-\xfc]|[\xe0-\xfb][\x40-\xfc]|\xfc[\xa2-\xee])+$/',
                'utf-8' => '/^(?:\xe4[\xb8-\xbf][\x80-\xbf]|[\xe5-\xe8][\x80-\xbf][\x80-\xbf]|\xe9[\x80-\xbe][\x80-\xbf]|\xe9\xbf[\x80-\x83]|\xef[\xa4-\xa8][\x80-\xbf]|\xef\xa9[\x80-\xaa])+$/',
            ),
        ),
    );

    // {{{ Constructor
    /**
     * Constructor
     * @param array $config : config variables
     */
    public function __construct($config = array())
    {
        $subdir = dirname(__FILE__) . DS . 'validate';
        require_once $subdir . DS . 'exception.php';
        if (! class_exists('Validate_Exception')) {
            // error operation
            trigger_error('[validate.php] Validate_Exception not exists.', E_USER_WARNING);
        }
        $config += array('subdir' => $subdir);
        $this->config($config);
    }
    // }}}

    // {{{ Init
    /**
     * Init
     * @param string $action : action name
     * @param array  $config : config variables
     * @return object this
     */
    public function init($action = 'default', $config = array())
    {
        $this->action = $action;
        $this->class = array($this);
        $this->rules = array();
        $this->mb = function_exists('mb_strlen');
        $this->config($config);
        $state_path = $this->config['subdir'] . DS . 'state_' . $this->config['lang'] . '.php';
        if (is_readable($state_path)) {
            self::$state = array_merge(self::$state, require $state_path);
        }
        return $this;
    }
    // }}}

    // {{{ Config
    /**
     * Config
     * @param mixed $config  : list of config or name
     * @param array &$origin : original config
     * @return mixed setter(this) or getter(mixed)
     */
    public function config($config, &$origin = FALSE)
    {
        if ($origin === FALSE) {
            $origin = &$this->config;
        }
        if (is_array($config)) { // set
            foreach ($config as $name => $value) {
                if (array_key_exists($name, $origin)) {
                   if (is_array($value)) {
                        $this->config($value, $origin[$name]);
                    } else {
                        $origin[$name] = $value;
                    }
                }
            }
        } elseif (is_string($config)) { // get
            $node = explode('/', $config);
            foreach ($node as $key => $name) {
                if (array_key_exists($name, $origin)) {
                    return isset($node[$key + 1])
                        ? $this->config(implode('/', array_slice($node, 1)), $origin[$name])
                        : $origin[$name]
                        ; // end of return
                }
            }
        }
        return $this;
    }
    // }}}

    // {{{ Set field
    /**
     * Add field
     * @param string $name  : field name
     * @param string $label : label name
     * @param string $rules : rule
     * @return object this
     */
    public function field($name, $label = '', $rules = '')
    {
        $this->current_field = $name;
        $this->field[$this->action][$name] = $label;
        $rules = explode('|', $rules);
        foreach ($rules as $rule) {
            if (preg_match('/^([^\[]+?)\[([^\]]+?)\]$/', $rule, $match)) {
                $rule = $match[1];
                if (! empty($this->args[$rule])) {
                    // preset rule
                    $args = count($this->args[$rule]) > 1 ? explode($this->config['separator'], $match[2]) : array($match[2]);
                    foreach ($this->args[$rule] as $key => $type) {
                        settype($args[$key], $type);
                    }
                    $param = array_merge(array($rule), $args);
                } else {
                    // custom rule
                    $param = array_merge(array($rule), explode($this->config['separator'], $match[2]));
                }
                call_user_func_array(array($this, 'rule'), $param);
            } else {
                $this->rule($rule);
            }
        }
        return $this;
    }
    // }}}

    // {{{ Set Rule
    /**
     * Add rule
     * @param string $name : rule
     * @return object this
     */
    public function rule($name)
    {
        $args = func_get_args();
        $args = array_slice($args, 1);
        $this->rules[$this->action][$this->current_field][] = array($name, $args);
        return $this;
    }
    // }}}

    // {{{ Set class
    /**
     * Add class
     * @param object $class : instance including method of _valid_(hoge)
     * @return object this
     */
    public function add($class)
    {
        if (! is_object($class)) {
            throw new Exception('Validate class is not a valid object.');
        }
        // Prevent having the same class twice in the array, remove to re-add on top if...
        $class_name = get_class($class);
        foreach ($this->class as $key => $c) {
            // ...it already exists in callables
            if (get_class($c) === $class_name) {
                unset($this->class[$key]);
            }
        }
        array_unshift($this->class, $class);
        return $this;
    }
    // }}}

    // {{{ Set message
    /**
     * Set/Get message
     * @param mixed $rule     : field name or array to set state message list
     * @param string $message : set state message
     * @return string
     */
    public static function message($rule, $message = NULL)
    {
        if (is_array($rule)) {
            self::$state = array_merge(self::$state, $rule);
        } elseif (! is_null($message)) {
            self::$state[$rule] = $message;
        } elseif (array_key_exists($rule, self::$state)) {
            return self::$state[$rule];
        }
    }
    // }}}

    // {{{ Execute
    /**
     * Find rule
     * @param string $rule : rule name
     * @return array
     */
    protected function _find_rule($rule)
    {
        if (is_string($rule)) {
            $method = '_valid_' . $rule;
            foreach ($this->class as $obj) {
                if (method_exists($obj, $method)) {
                    return array($rule => array($obj, $method));
                }
            }
        }
        return FALSE;
    }

    /**
     * Run rule
     * @param string $rule   : rule name
     * @param string &$value : value
     * @param array $params  : extra parameters
     * @param string $field  : field name
     * @param string $label  : label name for field
     * @return void
     * @throws Validate_Exception
     */
    protected function _run_rule($rule, &$value, array $params, $field, $label)
    {
        if (($call = $this->_find_rule($rule)) === FALSE) {
            return;
        }
        $output = call_user_func_array(reset($call), array_merge(array(&$value), $params));
        //if ($output === FALSE && $value !== FALSE) {
        if ($output === FALSE) { // Even FALSE value should check as target.
            $state = isset(self::$state[$rule]) ? self::$state[$rule] : '';
            $place = array(':state' => $state, ':field' => $field, ':value' => $value,
                           ':rule' => $rule, ':label' => $label);
            $param_merge = array();
            foreach ((array)$params as $key => $val) {
                $param_merge[':param[' . ($key + 1) . ']'] = $val;
            }
            $place = array_merge($place, $param_merge);
            throw new Validate_Exception($place);
        } elseif ($output !== TRUE) {
            $value = $output;
        }
    }

    /**
     * Execute
     * @param array   $input : input to validate
     * @param boolean $allow : flag of ignoring variable does not exist
     * @param array   $class : add validation object list 
     * @return boolean
     */
    public function execute(array $input = array(), $allow = FALSE, array $class = array())
    {
        // Backup current state of class.
        $backup = $this->class;
        foreach (array_reverse($class) as $obj) {
            $this->add($obj);
        }
        $this->valid[$this->action] = array();
        $this->error[$this->action] = array();
        foreach($this->field[$this->action] as $field => $label) {
            $value = isset($input[$field]) ? $input[$field] : NULL;
            if (($allow === TRUE && $value === NULL) || (is_array($allow) && ! in_array($field, $allow))) {
                // $allow is TRUE and $value is NULL, in case of NULL value passing validation.
                // in_array($fild, $allow), in case of field passing validation.
                continue;
            }
            try {
                foreach ($this->rules[$this->action][$field] as $rule) {
                    $method = $rule[0];
                    $params = $rule[1];
                    $this->_run_rule($method, $value, $params, $field, $label);
                }
                $this->valid[$this->action][$field] = $value;
            } catch (Validate_Exception $e) {
                $this->error[$this->action][$field] = $e;
            }
        }
        // Restore class.
        $this->class = $backup;
        return empty($this->error[$this->action]);
    }
    // }}}

    // {{{ Result
    /**
     * Validated field
     * @param string $field : fieldname
     * @return mixed field value or all of field value list
     */     
    public function valid($field = NULL)
    {
        if ($field === NULL) {
            return $this->valid[$this->action];
        }
        return array_key_exists($field, $this->valid[$this->action]) ? $this->valid[$this->action][$field] : FALSE;
    }

    /**
     * Error field
     * @param string $field : field name
     * @return mixed error message for field name or all of error message
     */     
    public function error($field = NULL)
    {
        if ($field === NULL) {
            return $this->error[$this->action];
        }
        return array_key_exists($field, $this->error[$this->action]) ? $this->error[$this->action][$field] : FALSE;
    }

    /**
     * Get error message
     * @param string $field  : field name
     * @param string $format : format of message
     * @param string $pre    : prefix quoute string
     * @param string $suf    : suffix quoute string
     * @return string error message
     */
    public function get_error($field, $format, $pre = '', $suf = "\n")
    {
        $msg = $this->error($field)->message($format);
        return $pre . $msg . $suf;
    }

    /**
     * Get all error messages
     * @param string $format : format of message
     * @param string $pre    : prefix quoute string
     * @param string $suf    : suffix quoute string
     * @return string error message
     */
    public function get_errors($format, $pre = '', $suf = "\n")
    {
        if ($this->config['get_errors_type'] == 'array') {
            $list = array();
            foreach ($this->error() as $field => $error) {
                $list[$field] = $error->message($format);
            }
            $result = $list;
        } else {
            // string
            $msg = '';
            foreach ($this->error() as $error) {
                $msg .= $pre . $error->message($format) . $suf;
            }
            $result = trim($msg);
        }
        return $result;
    }
    // }}}

    // {{{ Check empty
    /**
     * Special empty method because 0 and '0' are non-empty values
     * @param mixed $val : value
     * @return boolean
     */
    public static function e($val)
    {
        return ($val === FALSE || $val === NULL || $val === '' || $val === array());
    }
    // }}}

    // {{{ Check string or numeric
    /**
     * Check that value is numeric or string
     * @param mixed $val : value
     * @return boolean
     */
    public static function ns($val)
    {
        return (is_string($val) || is_numeric($val));
    }
    // }}}

    // {{{ Validate for throw
    /**
     * Throw, always return TRUE
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_throw(&$val)
    {
        return TRUE;
    }
    // }}}

    // {{{ Validate for trim
    /**
     * Trim
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_trim(&$val)
    {
        if ($this->ns($val)) {
            while (preg_match($this->config['regex']['trim'], $val, $match)) {
                // \x20 is space of ascii
                // \xe3\x80\x80 is ja full space of UTF-8
                array_shift($match); // throw match[0]
                foreach ($match as $char) {
                    $val = trim($val, $char);
                }
            }
        }
        return TRUE;
    }
    // }}}

    // {{{ Validate for character
    /**
     * Required
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_required($val)
    {
        return ! $this->e($val);
    }

    /**
     * Match value against comparison input
     * @param mixed $val      : value
     * @param mixed $compare  : comparing value
     * @param boolean $strict : flag for strict mode
     * @return boolean
     */
    public function _valid_value($val, $compare, $strict = FALSE)
    {
        // first try direct(strict) match
        if ($this->e($val) || $val === $compare || ( ! $strict && $val == $compare)) {
            return TRUE;
        }
        // allow multiple input for comparison
        if (is_array($compare)) {
            foreach($compare as $c) {
                if ($val === $c || ( ! $strict && $val == $c)) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Match PRCE pattern
     * @param mixed  $val     : value
     * @param string $pattern : a PRCE regex pattern
     * @return boolean
     */
    public function _valid_match($val, $pattern)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($pattern, $val) > 0);
    }

    /**
     * Minimum string length
     * @param mixed   $val    : value
     * @param integer $length : length
     * @return boolean
     */
    public function _valid_minlen($val, $length)
    {
        return $this->e($val) || ($this->ns($val) && ($this->mb ? mb_strlen($val) : strlen($val)) >= $length);
    }

    /**
     * Maximum string length
     * @param mixed   $val    : value
     * @param integer $length : length
     * @return boolean
     */
    public function _valid_maxlen($val, $length)
    {
        return $this->e($val) || ($this->ns($val) && ($this->mb ? mb_strlen($val) : strlen($val)) <= $length);
    }

    /**
     * Exact string length
     * @param mixed   $val    : value
     * @param integer $length : length
     * @return boolean
     */
    public function _valid_length($val, $length)
    {
        return $this->e($val) || ($this->ns($val) && ($this->mb ? mb_strlen($val) : strlen($val)) == $length);
    }

    /**
     * Alpha
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_alpha($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['alpha'], $val) > 0);
    }

    /**
     * Alpha-numeric
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_alnum($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['alnum'], $val) > 0);
    }

    /**
     * Alpha-numeric with underscores and dashes
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_aldash($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['aldash'], $val) > 0);
    }

    /**
     * Valid Base64
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_base64($val)
    {
        return $this->e($val) || ($this->ns($val) && (bool) ! preg_match($this->config['regex']['base64'], $val));
    }
    // }}}

    // {{{ Validate for internet and communication
    /**
     * Valid Email
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_email($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['email'], $val) > 0);
    }

    /**
     * Valid Multiple Email
     * @param mixed  $val : value
     * @param string $sep : separator
     * @return boolean
     */
    public function _valid_emails($val, $sep = "\t")
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $error = array();
        foreach (preg_split('/[' . preg_quote($sep, '/') . ']/', $val) as $key => $email) {
            if (! $this->_valid_email($email)) {
                $error[$key + 1] = $email;
            }
        }
        if ($error) {
            self::$state['emails'] = sprintf(self::$state['emails'], json_encode($error));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Validate IP Address
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_ip($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $seg = explode('.', $val);
        // Always 4 segments needed
        if (count($seg) != 4) {
            return FALSE;
        }
        // IP can not start with 0
        if ($seg[0][0] == '0') {
            return FALSE;
        }
        // Check each segment
        foreach ($seg as $segment) {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if ($segment == '' || preg_match("/[^0-9]/", $segment) || $segment > 255 || strlen($segment) > 3) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Valid domain
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_domain($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['domain'], $val) > 0);
    }

    /**
     * Valid url 
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_url($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['url'], $val) > 0);
    }

    /**
     * Valid Japanese mobile mail address
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_ktaimail($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['ktaimail'], $val) > 0 && $this->_valid_email($val));
    }

    /**
     * Valid zip
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_zip($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['zip'], $val) > 0);
    }

    /**
     * Valid tel
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_tel($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['tel'], $val) > 0);
    }
    // }}}

    // {{{ Validate for number
    /**
     * Checks whether numeric input has a minimum value
     * @param mixed $val     : value
     * @param mixed $min_val : float or integer 
     * @return boolean
     */
    public function _valid_minnum($val, $min_val)
    {
        return $this->e($val) || ($this->ns($val) && floatval($val) >= floatval($min_val));
    }

    /**
     * Checks whether numeric input has a maximum value
     * @param mixed $val     : value
     * @param mixed $max_val : float or integer 
     * @return boolean
     */
    public function _valid_maxnum($val, $max_val)
    {
        return $this->e($val) || ($this->ns($val) && floatval($val) <= floatval($max_val));
    }

    /**
     * Numeric
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_numeric($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['numeric'], $val) > 0);

    }

    /**
     * Is numeric
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_isnum($val)
    {
        return $this->e($val) || ($this->ns($val) && is_numeric($val));
    }

    /**
     * Integer
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_integer($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['integer'], $val) > 0);
    }

    /**
     * Is integer
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_isint($val)
    {
        return $this->e($val) || ($this->ns($val) && is_int($val));
    }

    /**
     * Decimal number
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_decimal($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['decimal'], $val) > 0);
    }

    /**
     * Is a natural number  (0,1,2,3, etc.)
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_natural($val)
    {
        return $this->e($val) || ($this->ns($val) && preg_match($this->config['regex']['natural'], $val) > 0);
    }

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_nozero($val)
    {
        return $this->e($val) || ($this->_valid_natural($val) && $val !== 0 && $val !== '0');
    }

    /**
     * Is numeric in range
     * @param mixed   $val : value
     * @param integer $min : Min numeric
     * @param integer $max : Max numeric
     * @return boolean
     */
    public function _valid_range($val, $min, $max)
    {
        return $this->e($val) || ($this->ns($val) && floatval($min) <= floatval($val) && floatval($max) >= floatval($val));
    }
    // }}}

    // {{{ Validate for date and time
    /**
     * Valid date
     * @param mixed $val : value like (YYYY-mm-dd or YYYY/mm/dd)
     * @return boolean
     */
    public function _valid_date($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $date = preg_split('/-|\//', $val);
        if (count($date) != 3) {
            return FALSE;
        }
        return ctype_digit(implode($date)) && checkdate((int)$date[1], (int)$date[2], (int)$date[0]);
    }

    /**
     * Valid time
     * @param mixed $val : value like (H or H:i or H:i:s)
     * @return boolean
     */
    public function _valid_time($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $time = explode(':', $val);
        switch (count($time)) {
            case 3 :
                $checker = array('h', 'i', 's');
                break;
            case 2 :
                $checker = array('h', 'i');
                break;
            case 1 :
                $checker = array('h');
                break;
            default :
                $checker = array();
        }
        foreach ($checker as $key => $check) {
            $t = $time[$key];
            switch ($check) {
                case 'h' :
                    if (! ($t >= 0 && $t <= 23)) {
                        return FALSE;
                    }
                    break;
                case 'i' :
                case 's' :
                    if (! ($t >= 0 && $t <= 59)) {
                        return FALSE;
                    }
                    break;
                default :
            }
        }
        return TRUE;
    }

    /**
     * Greater than min date
     * @param mixed  $val : value like (YYYY-mm-dd or YYYY/mm/dd)
     * @param string $min : YYYY-mm-dd or YYYY/mm/dd
     * @return boolean
     */
    public function _valid_mindate($val, $min)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $val_date = preg_split('/-|\//', $val);
        $min_date = preg_split('/-|\//', $min);
        if (count($val_date) != 3 || count($min_date) != 3) {
            return FALSE;
        }
        $val_stamp = mktime(0,0,0, $val_date[1], $val_date[2], $val_date[1]);
        $min_stamp = mktime(0,0,0, $min_date[1], $min_date[2], $min_date[1]);
        return $val_stamp >= $min_stamp;
    }

    /**
     * Less than max date
     * @param mixed  $val : value like (YYYY-mm-dd or YYYY/mm/dd)
     * @param string $max : YYYY-mm-dd or YYYY/mm/dd
     * @return boolean
     */
    public function _valid_maxdate($val, $max)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $val_date = preg_split('/-|\//', $val);
        $max_date = preg_split('/-|\//', $max);
        if (count($val_date) != 3 || count($max_date) != 3) {
            return FALSE;
        }
        $val_stamp = mktime(0,0,0, $val_date[1], $val_date[2], $val_date[1]);
        $max_stamp = mktime(0,0,0, $max_date[1], $max_date[2], $max_date[1]);
        return $val_stamp <= $max_stamp;
    }

    /**
     * Date in term
     * @param mixed  $val : value like (YYYY-mm-dd or YYYY/mm/dd)
     * @param string $min : YYYY-mm-dd or YYYY/mm/dd
     * @param string $max : YYYY-mm-dd or YYYY/mm/dd
     * @return boolean
     */
    public function _valid_termdate($val, $min, $max)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $val_date = preg_split('/-|\//', $val);
        $min_date = preg_split('/-|\//', $min);
        $max_date = preg_split('/-|\//', $max);
        if (count($val_date) != 3 || count($min_date) != 3 || count($max_date) != 3) {
            return FALSE;
        }
        $val_stamp = mktime(0,0,0, $val_date[1], $val_date[2], $val_date[0]);
        $min_stamp = mktime(0,0,0, $min_date[1], $min_date[2], $min_date[0]);
        $max_stamp = mktime(0,0,0, $max_date[1], $max_date[2], $max_date[0]);
        return $val_stamp >= $min_stamp && $val_stamp <= $max_stamp;
    }

    /**
     * Datetime in term
     * @param mixed  $val : value like (YYYY-mm-dd or YYYY/mm/dd with HH:ii:ss)
     * @param string $min : YYYY-mm-dd or YYYY/mm/dd with HH:ii:ss
     * @param string $max : YYYY-mm-dd or YYYY/mm/dd with HH:ii:ss
     * @return boolean
     */
    public function _valid_termdatetime($val, $min, $max)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        $val_datetime = explode(' ', $val);
        $min_datetime = explode(' ', $min);
        $max_datetime = explode(' ', $max);
        if (count($val_datetime) != 2 || count($min_datetime) != 2 || count($max_datetime) != 2) {
            return FALSE;
        }
        $val_date = preg_split('/-|\//', $val_datetime[0]);
        $min_date = preg_split('/-|\//', $min_datetime[0]);
        $max_date = preg_split('/-|\//', $max_datetime[0]);
        if (count($val_date) != 3 || count($min_date) != 3 || count($max_date) != 3) {
            return FALSE;
        }
        $val_time = explode(':', $val_datetime[1]);
        $min_time = explode(':', $min_datetime[1]);
        $max_time = explode(':', $max_datetime[1]);
        if (count($val_time) != 3 || count($min_time) != 3 || count($max_time) != 3) {
            return FALSE;
        }
        $val_stamp = mktime($val_time[0], $val_time[1], $val_time[2], $val_date[1], $val_date[2], $val_date[0]);
        $min_stamp = mktime($min_time[0], $min_time[1], $min_time[2], $min_date[1], $min_date[2], $min_date[0]);
        $max_stamp = mktime($max_time[0], $max_time[1], $max_time[2], $max_date[1], $max_date[2], $max_date[0]);
        return $val_stamp >= $min_stamp && $val_stamp <= $max_stamp;
    }
    // }}}

    // {{{ Validate for japanese string
    /**
     * Is japanese full hiragana
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_hiragana($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        switch (strtolower(mb_detect_encoding($val))) {
            //case 'euc-jp' :
            //    // no test
            //    return preg_match($this->config['regex']['hiragana']['euc-jp'], $val) > 0;
            //    // no test
            //case 'shift_jis' :
            //    return preg_match($this->config['regex']['hiragana']['shift_jis'], $val) > 0;
            case 'utf-8' :
                return preg_match($this->config['regex']['hiragana']['utf-8'], $val) > 0;
            default :
                return FALSE;
        }
    }

    /**
     * Is japanese full katakana
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_fullkana($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        switch (strtolower(mb_detect_encoding($val))) {
            //case 'euc-jp' :
            //    // no test
            //    return preg_match($this->config['regex']['fullkana']['euc-jp'], $val) > 0;
            //case 'shift_jis' :
            //    // no test
            //    return preg_match($this->config['regex']['fullkana']['shift_jis'], $val) > 0;
            case 'utf-8' :
                return preg_match($this->config['regex']['fullkana']['utf-8'], $val) > 0;
            default :
                return FALSE;
        }
    }

    /**
     * Is japanese half kana
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_halfkana($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        switch (strtolower(mb_detect_encoding($val))) {
            //case 'euc-jp' :
            //    // no test
            //    return preg_match($this->config['regex']['halfkana']['euc-jp'], $val) > 0;
            //case 'shift_jis' :
            //    // no test
            //    return preg_match($this->config['regex']['halfkana']['shift_jis'], $val) > 0;
            case 'utf-8' :
                return preg_match($this->config['regex']['halfkana']['utf-8'], $val) > 0;
            default :
                return FALSE;
        }
    }

    /**
     * Is japanese kanji
     * @param mixed $val : value
     * @return boolean
     */
    public function _valid_kanji($val)
    {
        if ($this->e($val)) {
            return TRUE;
        }
        if (! $this->ns($val)) {
            return FALSE;
        }
        switch (strtolower(mb_detect_encoding($val))) {
            //case 'euc-jp' :
            //    // no test
            //    return preg_match($this->config['regex']['kanji']['euc-jp'], $val) > 0;
            //case 'shift_jis' :
            //    // no test
            //    return preg_match($this->config['regex']['kanji']['shift_jis'], $val) > 0;
            case 'utf-8' :
                // no test. kanji range between e4 and e9, plus ef
                return preg_match($this->config['regex']['kanji']['utf-8'], $val) > 0;
            default :
                return FALSE;
        }
    }
    // }}}

}

