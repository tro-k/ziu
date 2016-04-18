<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Validate Exception Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Validate_Exception extends Exception
{

    private $place = array();

    /**
     * Constructor
     * @param array $place : place folder
     */
    public function __construct($place)
    {
        $this->place = $place;
    }

    /**
     * Get Message
     * @param string $msg : message
     * @return string
     */
    public function message($msg = FALSE)
    {
        if ($msg == FALSE) {
            return 'Failed for ["' . implode('","', $this->place) . '"]';
        }
        // only parse when there's tags in the message
        return (strpos($msg, ':') === FALSE ? $msg : $this->_replace($msg));
    }

    /**
     * Replace templating tags with values
     * @param string $msg : message
     * @return string
     */
    protected function _replace($msg)
    {
        foreach ($this->place as $key => $val) {
            $msg = str_replace($key, $val, $msg);
        }
        return $msg;
    }

    /**
     * Generate the error message
     * @return  string
     */
    public function __toString()
    {
        return $this->message();
    }

}

