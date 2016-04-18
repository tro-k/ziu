<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu boot strap php
 * 
 * execution of ziu framework
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

/**
 * Number of layer for ziu directory
 * 
 * ex.
 * same layer is '0'
 * 1 layer above from this index.php is '1'
 * 2 layer above from this index.php is '2'
 * ...
 * !! you must indicate more than '0' number. !!
 * !! recommend that ziu directory cannot access directly under document-root. !!
 */
define('ZIU_HIERARCHY_NUM', 1);

/**
 * Name of app directory name
 * 
 * /path/to/ziu/apps/default
 *                   ^^^^^^^
 * default name is 'default'
 */
define('ZIU_APP_DIRNAME', 'default');

define('ZIU_DISPATCH_PATH',  dirname(__FILE__));

/**
 * Flag of starting ticker profiling
 * 
 * default is 0
 */
define('ZIU_TICKER_PROFILING', 0);

/**
 * Flag of starting total cost profiling
 * 
 * default is 0
 */
define('ZIU_TOTAL_COST_PROFILING', 0);

/**
 * Rewrite $_SERVER['PATH_INFO'], if $_GET['_ZIU_PATH_INFO'] indicated
 */
if (isset($_GET['_ZIU_PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = $_GET['_ZIU_PATH_INFO'];
    $_GET['_ZIU_PATH_INFO'] = NULL;
    unset($_GET['_ZIU_PATH_INFO']);
}

/**
 * Boot execution
 */
require_once str_repeat('..' . DIRECTORY_SEPARATOR, ZIU_HIERARCHY_NUM)
                             . 'ziu' . DIRECTORY_SEPARATOR
                             . 'core' . DIRECTORY_SEPARATOR . 'boots.php';


