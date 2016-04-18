<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu boot strap php
 * 
 * execution of ziu framework
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
 * Move here
 */
chdir(ZIU_DISPATCH_PATH);

/**
 * Flag of starting ticker profiling
 * 
 * default is 0
 */
define('ZIU_TICKER_PROFILING', 0);

/**
 * Boot execution
 */
require_once str_repeat('..' . DIRECTORY_SEPARATOR, ZIU_HIERARCHY_NUM)
                             . 'ziu' . DIRECTORY_SEPARATOR
                             . 'core' . DIRECTORY_SEPARATOR . 'boots.php';


