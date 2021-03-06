<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu bootstrap
 * 
 * Note: Do not edit this file
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

/**
 * Define starting information
 */
define('ZIU_START_TIME',    microtime(TRUE));
define('ZIU_START_MEMORY',  memory_get_usage());

/**
 * Define DIRECTORY_SEPARATOR
 */
define('DS', DIRECTORY_SEPARATOR);

/**
 * Ticker Profiler
 */
if (defined('ZIU_TICKER_PROFILING') && ZIU_TICKER_PROFILING == 1) {
    require dirname(__FILE__) . DS . 'lib' . DS . 'ticker.php';
    Ticker::init(ZIU_START_TIME, ZIU_START_MEMORY);
    declare(ticks=1);
    register_tick_function(array('Ticker', 'profile'));
    register_shutdown_function(array('Ticker', 'display'));
}

/**
 * Start Loader
 */
require dirname(__FILE__) . DS . 'conf' . DS . 'constants.php';
require dirname(__FILE__) . DS . 'base' . DS . 'loader.php';

$loader = new Ziu_Loader;

// {{{ Timezone for date
/**
 * Set date default timezone for date()
 */
if (function_exists('date_default_timezone_set')) {
    $tz = $loader->conf('core/date_timezone');
    date_default_timezone_set((! empty($tz) ? $tz : 'Asia/Tokyo'));
}
// }}}

// {{{ Internal encoding
if ($enc = $loader->conf('core/internal_encoding')) {
    mb_internal_encoding($enc);
}
// }}}

// {{{ Error handle
/**
 * Set php error level and display
 */
error_reporting(E_ALL | E_STRICT);                        // all level of error
ini_set('log_errors', 1);                                 // log error on
ini_set('error_log', $loader->conf('env/php_error_log')); // path of error log
ini_set('display_errors', 0);                             // always hide error

/**
 * Set shutdown function
 */
register_shutdown_function(array($loader->core('error'), 'shutdown_handler'));

/**
 * Set error handler
 */
set_error_handler(array($loader->core('error'), 'error_handler'));

/**
 * Set exception handler
 */
set_exception_handler(array($loader->core('error'), 'exception_handler'));
// }}}

/**
 * Stop bootstrap to core/init for test
 */
if (defined('ZIU_TEST_DISPATCHER')) { return; }

/**
 * Init execute
 */
$loader->core('init')->execute();

/**
 * Total cost profile
 */
if (defined('ZIU_TOTAL_COST_PROFILING') && ZIU_TOTAL_COST_PROFILING == 1) {
    $loader->core('log')->info(sprintf("Cost time %.5f sec / Start memory %.3f KB / End memory %.3f KB"
        , (microtime(TRUE) - ZIU_START_TIME)
        , (ZIU_START_MEMORY / 1024)
        , (memory_get_usage() / 1024)
    ), 'ZIU_TOTAL_COST_PROFILING');
}

