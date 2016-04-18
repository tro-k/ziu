<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Initial Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Init
{

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Run Action
     * @param object $router : Ziu_Router
     * @param array  $params : params to invoke
     * @return void
     */
    private function run(Ziu_Router $router, array $params = array())
    {
        $conf = $this->loader->conf('core');
        // keep view name and prep object before.
        $view_prep = $this->loader->core('view')->prep();
        $view  = $this->loader->core('view')->name();
        $this->loader->core('view')->name('');
        // get module info.
        $segments = $router->r_segments();
        $dir   = $segments['directory'];
        $class = $segments['class'];
        $func  = $segments['method'];
        $args  = $segments['args'];
        $args  = empty($params) ? $args : array_merge($params, $args);
        foreach (explode(',', $conf['suffix_join']) as $type) {
            switch ($type) {
                case $conf['suffix_logic'] :
                    // logic action...
                    $logic = $this->loader->logic($dir, $class, $params);
                    if (is_callable(array($logic, $func))) {
                        $logic->unit = $this->loader->unit($dir, $class, $func);
                        call_user_func_array(array($logic, $func), $args);
                        $params = $logic->params;
                    }
                    break;
                case $conf['suffix_prep'] :
                    // prep action...
                    $prep = $this->loader->prep($dir, $class, $params);
                    if (is_callable(array($prep, $func))) {
                        call_user_func_array(array($prep, $func), $args);
                        $params = $prep->params;
                    }
                    break;
                case $conf['suffix_view'] :
                    if (! $this->loader->core('view')->name()) {
                        // if still no view name, set $class as default
                        $this->loader->core('view')->name(trim($dir . DS . $class, DS));
                    }
                    $this->loader->core('view')->param($params);
                    $this->loader->core('view')->prep($prep);
                    $this->loader->core('view')->execute();
                    break;
                default :
            }
        }
        // reset view name and prep object before.
        $this->loader->core('view')->name($view);
        $this->loader->core('view')->prep($view_prep);
    }

    /**
     * Invoke Action
     * @param string $uri    : uri to route
     * @param array  $params : params to invoke
     * @param boolean $flush : flag to flush
     * @return void
     */
    public function invoke($uri, array $params = array(), $flush = TRUE)
    {
        if ($flush === FALSE) { ob_start(); }
        $this->run($this->loader->core('router')->routing($uri), $params);
        if ($flush === FALSE) { return ob_get_clean(); }
    }

    /**
     * Execute Initial Action
     * @return void
     */
    public function execute()
    {
        $this->loader->help('core');
        $this->loader->autoload('pre');
        $this->loader->core('view')->output('start');
        $this->run($this->loader->core('router')->routing(
                    $this->loader->core('uri')->parse()));
        $this->loader->core('view')->output('end');
        $this->loader->autoload('end');
    }

}

