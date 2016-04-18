<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Environment Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Env
{

    /**
     * Flag of development
     * @var boolean
     */
    private $is_dev = TRUE;

    /**
     * Flag of cli
     * @var boolean
     */
    private $is_cli = FALSE;

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
        $this->conf = $this->loader->conf('env');
        switch ($this->conf['decision_of_development']) {
            case 'main_path' :
                $this->_parse_main_path();
                break;
            case 'indication' :
                $this->_parse_indication();
                break;
            case 'env_var' :
                $this->_parse_env_var();
                break;
            default :
        }
        switch ($this->conf['debug_mode']) {
            case 'cli' :
                $this->is_cli = TRUE;
                break;
            case 'web' :
                $this->is_cli = FALSE;
                break;
            default :
                $this->is_cli = (substr(php_sapi_name(), 0, 3) == 'cli' || defined('STDIN'));
        }
    }

    // {{{ Judge CLI
    /**
     * Is cli?
     * @return boolean
     */
    public function is_cli()
    {
        return $this->is_cli;
    }
    // }}}

    // {{{ Judge development
    /**
     * Is development?
     * @return boolean
     */
    public function is_dev()
    {
        return $this->is_dev;
    }

    /**
     * Parse main path
     * @return void
     */
    private function _parse_main_path()
    {
        $dev = $this->conf['main_path_for_development'];
        $pro = $this->conf['main_path_for_production'];
        $env = $this->loader->conf('core/main_path');
        $is_dev = FALSE;
        if (empty($dev) || empty($pro)) {
            $is_dev = TRUE;
        } elseif ($dev === $env || $pro !== $env) {
            $is_dev = TRUE;
        }
        $this->is_dev = $is_dev;
    }

    /**
     * Parse indication
     * @return void
     */
    private function _parse_indication()
    {
        $env = $this->conf['indication_of_environment'];
        $is_dev = FALSE;
        if (empty($env)) {
            $is_dev = TRUE;
        } elseif ('development' === $env || 'production' !== $env) {
            $is_dev = TRUE;
        }
        $this->is_dev = $is_dev;
    }

    /**
     * Parse env variable
     * @return void
     */
    private function _parse_env_var()
    {
        $var = $this->conf['env_var_name'];
        $dev = $this->conf['env_var_for_development'];
        $pro = $this->conf['env_var_for_production'];
        $env = getenv($var);
        $is_dev = FALSE;
        if (empty($var) || empty($dev) || empty($pro) || empty($env)) {
            $is_dev = TRUE;
        } elseif ($dev === $env || $pro !== $env) {
            $is_dev = TRUE;
        }
        $this->is_dev = $is_dev;
    }
    // }}}

}

