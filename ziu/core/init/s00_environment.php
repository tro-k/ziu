<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Initialize Environment.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

/**
 * Forcely clean and turn off output buffering
 * insted of php.ini of system or php_value per dir
 * ----------------------
 * output_buffering = Off
 * output_handler = None
 * ----------------------
 * for all of php environment
 */
if (ob_get_status()) {
    ob_end_clean();
}

