<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Form Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Form
{

    // {{{ variable
    /**
     * Variables
     */
    private $config = array(
                'except_values'     => '',
                'file_extend_mode'  => TRUE,
                'file_preview_text' => '[Preview]',
                'separator'         => '&nbsp;&nbsp;',
                'preset_values'     => array(),
            );
    private $values = array();
    private $html   = '';
    private $inputs = array();
    // }}}

    /**
     * Constructor
     * @param array $config : config variables
     */
    public function __construct($config = NULL)
    {
        $this->config($config);
    }

    // {{{ config
    /**
     * Config
     * @param array $config : config variables
     * @return object this
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }

    /**
     * Values
     * @param array $values : value variables
     * @return object this
     */
    public function values(array $values)
    {
        $excepts = explode(',', $this->config['except_values']);
        foreach ($excepts as $name) {
            if (isset($values[$name])) {
                unset($values[$name]);
            }
        }
        $this->values = array_merge($this->values, $this->_security($values));
        return $this;
    }

    /**
     * Sanitize for security
     * @param mixed $val : string or array
     * @return mixed escaped data
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
    // }}}

    /**
     * Return html
     * return string
     */
    public function __toString()
    {
        $html = $this->html;
        $this->html = '';
        $this->inputs = array();
        return $html;
    }

    /**
     * Wrap html
     * @param string $pre : prefix string
     * @param string $suf : suffix string
     * @return object this
     */
    public function wrap($pre, $suf)
    {
        if (! empty($this->inputs)) {
            $tmp = array();
            foreach ($this->inputs as $val) {
                $tmp[] = $pre . $val . $suf;
            }
            $this->html = implode($this->config['separator'], $tmp);
        } else {
            $this->html = $pre . $this->html . $suf;
        }
        return $this;
    }

    /**
     * Outline html for checkbox or radio
     * @param integer $rows : number of rows
     * @param integer $cols : number of cols
     * @param array $attr : attributes of table tag
     * @return object this
     */
    public function outline($rows = 1, $cols = 1, $attr = array())
    {
        if (! empty($this->inputs)) {
            $inputs = $this->inputs;
            $tmp = empty($attr) ? '<table>' : '<table ' . $this->_attr_to_str($attr) . '>';
            for ($row_idx = 1; $row_idx <= $rows; $row_idx++) {
                $tmp .= '<tr>';
                for ($col_idx = 1; $col_idx <= $cols; $col_idx++) {
                    $tag = array_shift($inputs);
                    $tmp .= '<td>' . (empty($tag) ? '&nbsp;' : $tag) . '</td>';
                }
                $tmp .= '</tr>';
            }
            $tmp .= '</table>';
            $this->html = $tmp;
        }
        return $this;
    }

    /**
     * Form open
     * @param string $action : url
     * @param string $method : post or get
     * @param mixed  $hidden : array or TRUE
     * @return object this
     */
    public function open($action, $method = 'post', $hidden = FALSE)
    {
        $attr = array('action' => $action, 'method' => $method);
        if ($method == 'file') {
            $attr['method']  = 'post';
            $attr['enctype'] = 'multipart/form-data';
        }
        $html = '<form ' . $this->_attr_to_str($attr) . '>';
        if ($hidden !== FALSE) {
            $html .= $this->hidden($hidden);
        }
        $this->html = $html;
        return $this;
    }

    /**
     * Form close
     * @return object this
     */
    public function close()
    {
        $this->html = '</form>';
        return $this;
    }

    /**
     * Input hidden
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function hidden($name = TRUE, $value = '')
    {
        if ($name === TRUE) {
            $this->html = $this->_input('hidden', $this->values);
        } else {
            $this->html = $this->_input('hidden', $name, $value);
        }
        return $this;
    }

    /**
     * Fetch value
     * @param mixed  $name      : name or list
     * @param string $value     : value
     * @param boolean $sanitize : flag to sanitize
     * @return object this
     */
    public function fetch($name, $value = '', $sanitize = TRUE)
    {
        $this->html = $this->_fetch_value($name, $value);
        $this->html = (bool)$sanitize ? htmlspecialchars($this->html, ENT_QUOTES) : $this->html;
        return (string)$this;
    }

    /**
     * Fetch preset value
     * @param string  $name     : name
     * @param string $value     : value
     * @param boolean $sanitize : flag to sanitize
     * @return object this
     */
    public function preset($name, $value = '', $sanitize = TRUE)
    {
        if (is_string($name) && isset($this->config['preset_values'][$name])) {
            $key = $this->_fetch_value($name, $value);
            if (isset($this->config['preset_values'][$name][$key])) {
                $this->html = $this->config['preset_values'][$name][$key];
                $this->html = (bool)$sanitize ? htmlspecialchars($this->html, ENT_QUOTES) : $this->html;
            }
        }
        return $this;
    }

    /**
     * Input text
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function text($name, $value = '')
    {
        $this->html = $this->_input('text', $name, $value);
        return $this;
    }

    /**
     * Input tel
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function tel($name, $value = '')
    {
        $this->html = $this->_input('tel', $name, $value);
        return $this;
    }

    /**
     * Input email
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function email($name, $value = '')
    {
        $this->html = $this->_input('email', $name, $value);
        return $this;
    }

    /**
     * Input password
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function password($name, $value = '')
    {
        $this->html = $this->_input('password', $name, $value);
        return $this;
    }

    /**
     * Input submit
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function submit($name, $value = '')
    {
        $this->html = $this->_input('submit', $name, $value);
        return $this;
    }

    /**
     * Input reset
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function reset($name, $value = '')
    {
        $this->html = $this->_input('reset', $name, $value);
        return $this;
    }

    /**
     * Input file
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function file($name, $value = '')
    {
        if ($this->config['file_extend_mode'] === TRUE) {
            $this->html = $this->_input('file', 'upl_' . $name, $value);
            $this->html .= $this->_input('hidden', 'tmp_' . $name, $value);
            $this->html .= $this->_input('hidden', $name, $value);
        } else {
            $this->html = $this->_input('file', $name, $value);
        }
        return $this;
    }

    /**
     * File image for preview
     * @param string $type : html tag name
     * @param string $name : name of image
     * @param string $real : name of real uri directory path
     * @param string $temp : name of temp uri directory path
     * @return object this
     */
    public function file_preview($type, $name, $real, $temp = '')
    {
        $html = '';
        switch ($type) {
            case 'img' :
            case 'a' :
                $method = '_file_preview_' . $type . '_tag';
                break;
            default :
                return $this;
        }
        if ($this->_isset_values('tmp_' . $name, $file) && ! empty($file)) {
            if ($this->_isset_values($temp, $path) && ! empty($path)) {
                $html = $this->$method('/' . trim($path, '/') . '/' . $file);
            }
        } elseif ($this->_isset_values($name, $file) && ! empty($file)) {
            if ($this->_isset_values($real, $path) && ! empty($path)) {
                $html = $this->$method('/' . trim($path, '/') . '/' . $file);
            }
        }
        $this->html = $html;
        return $this;
    }

    /**
     * File preview <img> tag
     * @param string $uri : image uri
     * @return string
     */
    private function _file_preview_img_tag($uri)
    {
        $attr = array('src' => $uri . '?_t=' . time());
        if (! empty($this->config['file_preview_text'])) {
            $attr['alt'] = $this->config['file_preview_text'];
        }
        return '<img ' . $this->_attr_to_str($attr) . ' />';
    }

    /**
     * File preview <a> tag
     * @param string $uri : image uri
     * @return string
     */
    private function _file_preview_a_tag($uri)
    {
        $attr = array('href' => $uri . '?_t=' . time(), 'target' => '_blank');
        $text = empty($this->config['file_preview_text']) ? '[Preview]' : $this->config['file_preview_text'];
        return '<a ' . $this->_attr_to_str($attr) . '>' . $text . '</a>';
    }

    /**
     * Input image, indicate src/alt with attributes()
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function image($name, $value = '')
    {
        $this->html = $this->_input('image', $name, $value);
        return $this;
    }

    /**
     * Input button
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @return object this
     */
    public function button($name, $value = '')
    {
        $this->html = $this->_input('button', $name, $value);
        return $this;
    }

    /**
     * Button
     * @param mixed  $name  : name
     * @param string $value : value
     * @return object this
     */
    public function buttonquote($name, $value = '')
    {
        $value = $this->_value($name, $value);
        $attr = array('name' => $name);
        $this->html = '<button ' . $this->_attr_to_str($attr) . '>' . $value . '</button>';
        return $this;
    }

    /**
     * Input radio
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @param string $label : label
     * @return object this
     */
    public function radio($name, $value = '', $label = '')
    {
        if (is_string($name) && isset($this->config['preset_values'][$name])) {
            $name = array($name => $this->config['preset_values'][$name]);
        }
        $this->html = $this->_input_check('radio', $name, $value, $label);
        return $this;
    }

    /**
     * Input checkbox
     * @param mixed  $name  : name or list
     * @param string $value : value
     * @param string $label : label
     * @return object this
     */
    public function checkbox($name, $value = '', $label = '')
    {
        if (is_string($name) && isset($this->config['preset_values'][$name])) {
            $name = array($name => $this->config['preset_values'][$name]);
        }
        $this->html = $this->_input_check('checkbox', $name, $value, $label);
        return $this;
    }

    /**
     * Select
     * @param string $name  : name
     * @param string $value : value
     * @return object this
     */
    public function select($name, array $option = array(), $value = '')
    {
        if (empty($option) && isset($this->config['preset_values'][$name])) {
            $option = $this->config['preset_values'][$name];
        }
        $opt = '';
        if (isset($option['optgroup'])) {
            $tmp = array();
            foreach ($option['optgroup'] as $group) {
                $opt .= '<optgroup label="' . $group['label'] . '">';
                foreach ($group['option'] as $key => $val) {
                    $tmp[$key] = $val;
                    $opt .= '<option value="' . $key . '">' . $val . '</option>';
                }
                $opt .= '</optgroup>';
            }
            $option = $tmp;
        } else {
            foreach ($option as $key => $val) {
                $opt .= '<option value="' . $key . '">' . $val . '</option>';
            }
        }
        $value = $this->_value($name, $value);
        if (isset($option[$value])) {
            $pre = '<option value="' . $value . '">';
            $rep = '<option value="' . $value . '" selected="selected">';
            $opt = str_replace($pre, $rep, $opt);
        }
        $attr = array('name' => $name);
        $this->html = '<select ' . $this->_attr_to_str($attr) . '>' . $opt . '</select>';
        return $this;
    }

    /**
     * Textarea
     * @param string $name  : name
     * @param string $value : value
     * @return object this
     */
    public function textarea($name, $value = '')
    {
        $value = $this->_value($name, $value);
        $attr = array('name' => $name);
        $this->html = '<textarea ' . $this->_attr_to_str($attr) . '>' . $value . '</textarea>';
        return $this;
    }

    /**
     * Attributes
     * @param array $attr : attributes
     * @return object this
     */
    public function attributes(array $attr)
    {
        $html = $this->html;
        if (preg_match('/^<([^ \/>]+)/', $html, $match)) {
            $str = $this->_attr_to_str($attr);
            switch ($match[1]) {
                case 'select' :
                    $html = preg_replace('/<select([^>]*)>/', '<select$1 ' . $str . '>', $html);
                    break;
                case 'form' :
                case 'textarea' :
                case 'button' :
                case 'a' : // for file_preview()
                    $html = str_replace('>', ' ' . $str . '>', $html);
                    break;
                case 'input' :
                case 'img' : // for file_preview()
                    $html = str_replace(' />', ' ' . $str . ' />', $html);
                    break;
                default :
            }
            $this->html = $html;
        }
        return $this;
    }

    /**
     * Input
     * @param string $type  : input type
     * @param mixed  $name  : name or list of input data
     * @param string $value : value
     * @return string
     */
    private function _input($type, $name, $value = '')
    {
        $html = '';
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $html .= $this->_input($type, $key, $val);
            }
        } else {
            $value = $this->_value($name, $value);
            if (is_array($value)) {
                // dimension value
                foreach ($value as $key => $val) {
                    $html .= $this->_input($type, $name . "[$key]", $val);
                }
            } else {
                // single(= string) value
                $attr = array(
                    'type'  => $type,
                    'name'  => $this->_security($name),
                    'value' => $this->_value($name, $value),
                );
                $html .= '<input ' . $this->_attr_to_str($attr) . ' />';
            }
        }
        return $html;
    }

    /**
     * Input check (checkbox or radio)
     * @param string $type  : input type
     * @param mixed  $name  : name or list of input data
     * @param string $value : value
     * @return string
     */
    private function _input_check($type, $name, $value = '', $label = '')
    {
        $html = '';
        if (is_array($name)) {
            $tmp = array();
            foreach ($name as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $v => $l) {
                        $tmp[] = $this->_input_check($type, $key, $v, $l);
                    }
                } else {
                    $tmp[] = $this->_input_check($type, $key, $val);
                }
            }
            $this->inputs = $tmp;
            $html .= implode($this->config['separator'], $tmp);
        } else {
            $attr = array(
                'type'  => $type,
                'name'  => $name . ($type == 'checkbox' ? '[]' : ''),
                'value' => $value,
            );
            if (isset($this->values[$name])) {
                switch ($type) {
                    case 'checkbox' :
                        if (in_array($value, (array)$this->values[$name])) {
                            $attr['checked'] = 'checked';
                        }
                        break;
                    case 'radio' :
                        if ($this->values[$name] == $value) {
                            $attr['checked'] = 'checked';
                        }
                        break;
                    default :
                }
            }
            $html .= '<input ' . $this->_attr_to_str($attr) . ' />';
            if (! empty($label)) {
                $html = "<label>{$html}{$label}</label>";
            }
        }
        return $html;
    }

    /**
     * Fetch value
     * @param mixed  $name  : name or list of input data
     * @param string $value : value
     * @return string
     */
    private function _fetch_value($name, $value = '')
    {
        $text = '';
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $text .= $this->_fetch_value($key, $val);
            }
        } else {
            $value = $this->_value($name, $value);
            if (is_array($value)) {
                // dimension value
                foreach ($value as $key => $val) {
                    $text .= $this->_fetch_value($name . "[$key]", $val);
                }
            } else {
                // single(= string) value
                $text .= $this->_value($name, $value);
            }
        }
        return $text;
    }

    /**
     * Value
     * @param string $name  : name
     * @param string $value : value
     * @return string
     */
    private function _value($name, $value)
    {
        $retval = '';
        return $this->_isset_values($name, $retval) && $value === '' ? $retval : $value;
    }

    /**
     * Isset values
     * @param string $name   : key (including blacket[])
     * @param mixed  &$value : value
     * @return boolean
     */
    private function _isset_values($name, &$value)
    {
        if (strpos($name, '[') !== FALSE) {
            // multiple
            if (preg_match_all('/([^[]+)?(?:\[([^]]*)\])+?/', $name, $match)) {
                $list = array_merge(array($match[1][0]), $match[2]); // name + dimension
                $values = $this->values;
                foreach ($list as $key => $val) {
                    if (($values = $this->_isget_in_list($val, $values)) === FALSE) {
                        return FALSE;
                    }
                }
            }
            $value = $values;  // overwrite as target value
            return TRUE;
        } else {
            // single
            if (! isset($this->values[$name])) {
                return FALSE;
            }
            $value = $this->values[$name];  // overwrite as target value
            return TRUE;
        }
    }

    /**
     * Isset, get list
     * @param string $name : key name
     * @param array  $list : list array
     * @return mixed FALSE or data
     */
    private function _isget_in_list($name, $list)
    {
        return isset($list[$name]) ? $list[$name] : FALSE;
    }

    /**
     * Attr tot string
     * @param array $attr : attributes
     * @return string
     */
    private function _attr_to_str(array $attr)
    {
        $res = array();
        foreach ($attr as $key => $val) {
            $res[] = $key . '="' . $val . '"';
        }
        return implode(' ', $res);
    }

    /**
     * Undefined method call
     * @param string $type : called method name
     * @param array  $args : arguments for called method
     * @return mixed this or void
     */
    public function __call($type, $args)
    {
        switch ($type) {
            case 'email' :
            case 'tel' :
            case 'url' :
            case 'number' :
                $name  = $args[0];
                $value = isset($args[1]) ? $args[1] : '';
                $this->html = $this->_input($type, $name, $value);
                return $this;
            default :
        }
    }

}

