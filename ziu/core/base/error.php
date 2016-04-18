<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Base Error Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ziu_Error
{

    /**
     * Variables.
     */
    // Error levels
    private $levels = array(
        0                  => 'Error',
        E_ERROR            => 'Error',
        E_WARNING          => 'Warning',
        E_PARSE            => 'Parsing Error',
        E_NOTICE           => 'Notice',
        E_CORE_ERROR       => 'Core Error',
        E_CORE_WARNING     => 'Core Warning',
        E_COMPILE_ERROR    => 'Compile Error',
        E_COMPILE_WARNING  => 'Compile Warning',
        E_USER_ERROR       => 'User Error',
        E_USER_WARNING     => 'User Warning',
        E_USER_NOTICE      => 'User Notice',
        E_STRICT           => 'Runtime Notice'
    );
    // Catching error levels through error_handler()
    private $through = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);

    private $flush = TRUE;

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
        $this->flush = $this->loader->conf('env/debug_mode') === FALSE;
    }

    /**
     * Shutdown handler
     * @return void
     */
    public function shutdown_handler()
    {
        if (! function_exists('error_get_last')) {
            return; // for php < 5.2
        }
        $er = error_get_last();
        if (! empty($er) && in_array($er['type'], $this->through)) {  
            $this->exception_handler(new ErrorException($er['message'], $er['type'], 0, $er['file'], $er['line']));
        }
    }

    /**
     * Error handler
     * @param string $severity : Severity
     * @param string $message  : Message
     * @param string $path     : Error file path
     * @param string $line     : Error line
     * @return void
     */
    public function error_handler($severity, $message, $path, $line)
    {
        $this->exception_handler(new ErrorException($message, $severity, 0, $path, $line));
    }

    /**
     * Exception handler
     * @param object $e : Exception
     * @return void
     */
    public function exception_handler(Exception $e)
    {
        if ($this->loader->core('env')->is_dev()) {  
            $res = $this->_errorbox($e);
        } else {  
            $res = $this->_errorpage($e);
        }
        if ($this->flush) {
            echo($res);
            exit(1);
        } else {
            return $res;
        }
    }

    /**
     * Get error page
     * @param object $e : Exception
     * @return void
     */
    private function _errorpage(Exception $e)
    {
        $message = $this->_prepare_error_text($this->_prepare_exception($e));
        $this->loader->core('log')->error($message, __CLASS__ . '::' . __FUNCTION__);
        if ($this->loader->core('env')->is_cli()) {
            $res = $message . "\n";
        } else {
            if (! headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            $_500_ = $this->loader->conf('routes/_500_');
            if ($this->loader->core('router')->is_module($_500_)) {
                $res = $this->loader->core('view')->render($_500_, array(), FALSE);
            } else {
                $res = <<<EOP
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>500 Internal Server Error</title>
    <style type="text/css">
        * { margin: 0; padding: 0; }
        #error { border: 1px solid gray; padding: 30px;  color: #333; margin: 50px auto; width: 70%; }
        h1 { font-size: 30px; padding-bottom: 10px; line-height: 1em; }
        .message { font-size: 18px; color: gray; padding: 18px 0 20px; border-top: 1px solid gray; }
        p { margin: 0 0 15px; line-height: 22px;}
    </style>
</head>
<body>
    <div id="error">
        <h1>500 Internal Server Error</h1>
        <p class="message">An unexpected error has occurred.</p>
    </div>
</body>
</html>
EOP;
            }
        }
        return $res;
    }

    /**
     * Get error box
     * @param object $e : Exception 
     * @return  void
     */
    private function _errorbox(Exception $e)
    {
        $data = $this->_prepare_exception($e);
        $message = $this->_prepare_error_text($data);
        $this->loader->core('log')->error($message, __CLASS__ . '::' . __FUNCTION__);
        if ($this->loader->core('env')->is_cli()) {
            $res = $message . "\n";
        } else {
            $res = $this->_prepare_error_html($data);
        }
        return $res;
    }

    /**
     * Prepare error text
     * @param array $data : error data
     * @return string
     */
    private function _prepare_error_text($data)
    {
        $text = $data['severity'] . ' - ' . $data['message'];
        $text .=  ' in ' . $data['filepath'] . ' on line ' . $data['error_line'];
        return $text;
    }

    /**
     * Prepare error html
     * @param array $data : error data
     * @return string
     */
    private function _prepare_error_html($data)
    {
        extract($data);
        $code = '';
        if (is_array($debug_lines)) {
            $key = $error_line - 1; // for file() start array key 0;
            $band = 10;
            $keys = array($key);
            for ($i = 1; $i <= $band; $i++) {
                $keys[] = $key - $i;
                $keys[] = $key + $i;
            }
            sort($keys);
            $len = strlen($keys[count($keys) - 1]);
            foreach ($keys as $key) {
                $rownum = ($key + 1);
                if ($rownum > 0 && isset($debug_lines[$key])) {
                    $line = str_pad($rownum, $len, ' ', STR_PAD_LEFT) . ": " . htmlspecialchars($debug_lines[$key], ENT_QUOTES) . "\n";
                    if ($rownum == $error_line) {
                        $code .= "<strong style=\"color: yellow;\">$line</strong>\n";
                    } else {
                        $code .= $line . "\n";
                    }
                }
            }
        }
        $html = <<<EOB
<div style="border: 1px solid gray; padding: 5px; color: gray; width: 70%; margin: 0 auto;">
    <p style="font-size: 13px; color: gray; margin: 5px 2px; border-bottom: solid 1px gray;"><strong>$severity</strong></p>
    <p style="font-size: 12px; color: gray; margin: 5px 2px;">
        $type - $severity : <span style="font-style: italic; color: blue;">$message</span><br />
        <strong>$filepath ---- line $error_line</strong>
    </p>
    <pre style="background-color: #333; font-size: 11px; color: #fff; border: 1px solid gray; padding: 10px 3px 3px 8px; margin: 0; line-height: 0.5; overflow: auto; height: 200px;"><code>$code</code></pre>
</div>
EOB;
        return $html;
    }

    /**
     * Prepare exception
     * @param object $e : Exception
     * @return array
     */
    private function _prepare_exception(Exception $e)
    {
        $data = array();
        $data['type']       = get_class($e);
        $data['message']    = $e->getMessage();
        $data['filepath']   = $e->getFile();
        $data['error_line'] = $e->getLine();
        $data['backtrace']  = $e->getTrace();
        // severity
        $code = $e->getCode();
        if (! isset($this->levels[$code])) {
            $data['severity'] = 'Unknown Error';
        } else {
            $data['severity'] = $this->levels[$code];
        }
        $data['severity'] .= " [code:$code]";
        // backtrace
        foreach ($data['backtrace'] as $key => $trace) {
            if ( ! isset($trace['file'])) {
                unset($data['backtrace'][$key]);
            } elseif ($trace['file'] == ZIU_CORE_PATH . 'core/base/error.php') {
                unset($data['backtrace'][$key]);
            }
        }
        $data['debug_lines'] = is_file($data['filepath']) ? file($data['filepath']) : $data['filepath'];
        return $data;
    }

}

