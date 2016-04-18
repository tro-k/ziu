<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Function Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

if (! function_exists('websocket')) {
    function websocket()
    {
        global $loader;
        $loader->import('lib/websocket');
        return Websocket::getInstance();
    }
}

