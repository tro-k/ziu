<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Delegate Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

abstract class Delegate
{

    /**
     * Variables
     */
    private $super;

    /**
     * Constructor
     */
    public final function __construct($parent)
    {
        $this->super = $parent;
    }

    /**
     * Get super object
     * @return object
     */
    private function super()
    {
        if (is_object($this->super)) {
            return $this->super;
        } else {
            throw new Exception('No super class for delegate.');
        }
    }

    /**
     * Getter
     * @param string $name : property name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->super()->$name;
    }

    /**
     * Setter
     * @param string $name  : property name
     * @param mixed  $value : property value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->super()->$name = $value;
    }

    /**
     * Isset
     * @param string $name : property name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->super()->$name);
    }

    /**
     * Unset
     * @param string $name : property name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->super()->$name);
    }

    /**
     * Caller
     * @param string $name : method name
     * @param array  $args : method arguments
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->super(), $name), $args);
    }

}

