<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Log Loader Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Log
{

    /**
     * Log level
     */
    private $level = array(
        1 => 'Error',
        2 => 'Warning',
        3 => 'Debug',
        4 => 'Info',
    );

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
        $this->_path = $this->loader->conf('core/log_dir_path');
    }

    /**
     * Info
     * @param string $msg    : message
     * @param string $method : method name
     * @return void
     */
    public function info($msg, $method = NULL)
    {
        return $this->write(ZIU_LOG_LEVEL_INFO, $msg, $method);
    }

    /**
     * Debug
     * @param string $msg    : message
     * @param string $method : method name
     * @return void
     */
    public function debug($msg, $method = NULL)
    {
        return $this->write(ZIU_LOG_LEVEL_DEBUG, $msg, $method);
    }

    /**
     * Warning
     * @param string $msg    : message
     * @param string $method : method name
     * @return void
     */
    public function warning($msg, $method = NULL)
    {
        return $this->write(ZIU_LOG_LEVEL_WARNING, $msg, $method);
    }

    /**
     * Error
     * @param string $msg    : message
     * @param string $method : method name
     * @return void
     */
    public function error($msg, $method = NULL)
    {
        return $this->write(ZIU_LOG_LEVEL_ERROR, $msg, $method);
    }

    /**
     * Write
     * @param integer $level  : Error level see. ZIU_LOG_LEVEL_(INFO|DEBUG|WARNING|ERROR)
     * @param string  $msg    : message
     * @param string  $method : method name
     * @return void
     */
    public function write($level, $msg, $method = NULL)
    {
        $level = $this->level[$level];
        if (empty($level)) { return; }
        $path = $this->_path . date('Y/m') . DS;
        $file = $path . date('ymd') . '.php';
        if (! is_dir($path)) {
            $pre = umask(0);
            mkdir($path, 0777, TRUE);
            umask($pre);
        }
        $message  = '';
        if (! file_exists($file)) {
            touch($file);
            $pre = umask(0);
            @chmod($file, 0666);
            umask($pre);
        }
        if (! $fp = @fopen($file, 'a')) {
            return FALSE;
        }
        $message .= $level . ' - ' . date('Y-m-d H:i:s');
        $message .= ' ' . (empty($method) ? '' : $method . ' - ') . $msg . PHP_EOL;
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
        return TRUE;
    }

}

