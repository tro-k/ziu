<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Environment Setting.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

return array(

    // {{{ Path of php error_log
    'php_error_log' => ZIU_MAIN_PATH . 'logs' . DS . 'php-error_log.' . date('Ym') . '.php',
    // }}}

    // {{{ What is decision of development
    // You can indicate 'main_path', 'indication' or 'env_var'
    // 'env_var' is recommended
    'decision_of_development' => 'main_path',

    // Indication of Environment
    // indication_of_environment e.g. 'development' or 'production'
    'indication_of_environment' => 'development',

    // Main path
    // main_path_for_development e.g. /var/www/vhosts/dev.sample.jp/www/ziu/
    // main_path_for_production  e.g. /var/www/vhosts/sample.jp/www/ziu/
    // notice: need end of directory separator
    'main_path_for_development' => '',
    'main_path_for_production'  => '',

    // Environment variable
    // env_var_name e.g. ZIU_ENV
    // env_var_for_development e.g. development
    // env_var_for_production  e.g. production
    // how to setting is...
    // 1. case of apache, add [SetEnv ZIU_ENV production] in VirtualHost directive of conf
    // 2. case of batch, indicate [export ZIU_ENV=production; php -q batch/index.php hoge]
    'env_var_name'            => 'ZIU_ENV',
    'env_var_for_development' => 'development',
    'env_var_for_production'  => 'production',
    // }}}

    // {{{ Debug mode
    // You can indicate 'cli', 'web' or FALSE
    'debug_mode' => FALSE,
    // }}}

);

