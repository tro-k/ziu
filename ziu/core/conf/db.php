<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Database Setting.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

return array(

    // Cache mode flag TRUE or FALSE
    'cache' => FALSE,

    // Sql debug log flag TRUE or FALSE
    'debug' => FALSE,

    // MySQL sql_mode. use only that debug is TRUE. other database needs to indicate blank.
    'debug_mysql_sql_mode' => 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES',

    // Connect function name
    'connecting' => array(
        'read'  => 'database_connect',
        'write' => 'database_connect',
    ),

    // Debug method group name
    'debug_method_group' => 'normal',

    // Call method group
    'call_method_group' => array(
        'execution' => 'fetch,query,execute',
        'normal'    => 'fetch,query,execute,begin,commit,rollback,transaction',
        'all'       => '', // empty is all of method
    ),

    // Indicate connection name there are 'development', 'staging' or 'production'
    'connection_name' => 'development',

    // Connection information for PDO
    'connection' => array(
        'development' => array(
            'dsn' => 'mysql:host=localhost;dbname=sample_dev',
            'user' => 'gaga',
            'pass' => '123456',
            'pooling' => FALSE,
        ),
        'staging' => array(
            'dsn' => 'mysql:host=localhost;dbname=sample_stg',
            'user' => 'gaga',
            'pass' => '123456',
            'pooling' => FALSE,
        ),
        'production' => array(
            'dsn' => 'mysql:host=localhost;dbname=sample',
            'user' => 'gaga',
            'pass' => '123456',
            'pooling' => FALSE,
        ),
    ),

    // Default model super class name
    'default_model_super_class' => 'Model_Super',

);

