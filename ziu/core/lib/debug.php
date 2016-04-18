<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Debug Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Debug
{

    /**
     * Variables
     */
    private static $is_cli;

    /**
     * Constructor
     */
    public function __construct()
    {
        static::$is_cli = (php_sapi_name() == 'cli' || defined('STDIN'));
    }

    /**
     * Init
     */
    public function init()
    {
    }

    public static function cmark($p1, $p2 = '', $decimals = 4)
    {
        static $mark = array();
        if ($p2 === '') {
            $mark[$p1] = microtime();
        } else {
            if (! isset($mark[$p2])) {
                $mark[$p2] = microtime();
            }
            list($sm, $ss) = explode(' ', $mark[$p1]);
            list($em, $es) = explode(' ', $mark[$p2]);
            $str = '' . $p1 . ' to ' . $p2 . ' : ';
            $str .=  number_format(($em + $es) - ($sm + $ss), $decimals);
            $str .= ' sec';
            static::dump($str);
        }
    }

    /**
     * Quick and nice way to output a mixed variable to the browser
     *
     * @static
     * @access  public
     * @return  string
     */
    public static function dump()
    {
        $backtrace = debug_backtrace();
        if (strpos($backtrace[0]['file'], 'debug.php') !== FALSE) {
            $info = $backtrace[1];
        } else {
            $info = $backtrace[0];
        }
        $label = $info['class'] . $info['type'] . $info['function'];
        $arguments = func_get_args();
        $count = count($arguments);
        if (static::$is_cli) {
            echo($label . "\n");
            echo($info['file'] . ' ---- line: ' . $info['line'] . "\n");
            for ($i = 1; $i <= $count; $i++) {
                echo('[Arguments: ' . $i . "]\n");
                var_dump($arguments[$i - 1]);
                echo("\n\n");
            }
        } else {
            echo('<div style="font-size: 13px;background: #ccc; border:1px solid gray; padding:5px; width: 70%; margin: 0 auto;">');
            echo('<strong>' . $label . '</strong><br />');
            echo('<h1 style="border-top: 1px solid gray; padding: 5px 0 0 0; margin: 0; font-weight: bold; font-size: 12px;">' . $info['file'] . ' ---- line: ' . $info['line'] . '</h1>');
            echo('<pre style="border: solid 1px gray; overflow:auto;font-size:11px;height:300px;background-color:white; padding: 10px 3px 3px 8px; margin: 0;">');
            for ($i = 1; $i <= $count; $i++) {
                echo('<strong>[Arguments: ' . $i . "]</strong>\n");
                var_dump($arguments[$i - 1]);
                echo("\n\n");
            }
            echo('</pre>');
            echo('</div>');
        }
    }

    /**
     * Prints a list of the configuration settings read from <i>php.ini</i>
     *
     * @access public
     * @static
     */
    public static function phpini($dump = TRUE)
    {
        if ( ! is_readable(get_cfg_var('cfg_file_path'))) {
            return FALSE;
        }
        $ini = parse_ini_file(get_cfg_var('cfg_file_path'), TRUE);
        if ($dump) {
            $str = '';
            foreach ($ini as $key => $val) {
                if (static::$is_cli) {
                    $str .= "[" . $key . "]\n";
                    foreach ($val as $k => $v) {
                        $str .= '  ' . str_pad($k, 40, ' ', STR_PAD_RIGHT) . $v . "\n";
                    }
                    $str .= "\n";
                } else {
                    $str .= "\n<strong style=\"color: blue;\">[" . $key . "]</strong>\n";
                    $str .= '<table style="width: 100%">';
                    foreach ($val as $k => $v) {
                        $str .= '<tr>';
                        $str .= '<td style="width: 300px; border-top: solid 1px #ddd;">' . $k . '</td><td style="border-top: solid 1px #ddd;">' . $v . '</td>';
                        $str .= '</tr>';
                    }
                    $str .= '</table>';
                }
            }
            static::dump($str);
        } else {
            return $ini;
        }
    }

    public static function imark($callable, array $params = array())
    {
        $res = static::ibenchmark($callable, $params);
        $str = "\n";
        foreach ($res as $key => $val) {
            $str .= "$key\t$val\n";
        }
        static::dump($str);
    }

    /**
     * Benchmark anything that is callable
     *
     * @access public
     * @static
     */
    public static function ibenchmark($callable, array $params = array())
    {
        // get the before-benchmark time
        if (function_exists('getrusage')) {
            $dat = getrusage();
            $utime_before = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec'] / 1000000, 4);
            $stime_before = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec'] / 1000000, 4);
        } else {
            list($usec, $sec) = explode(' ', microtime());
            $utime_before = ((float)$usec + (float)$sec);
            $stime_before = 0;
        }
        // call the function to be benchmarked
        $result = is_callable($callable) ? call_user_func_array($callable, $params) : NULL;
        // get the after-benchmark time
        if (function_exists('getrusage')) {
            $dat = getrusage();
            $utime_after = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec'] / 1000000, 4);
            $stime_after = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec'] / 1000000, 4);
        } else {
            list($usec, $sec) = explode(' ', microtime());
            $utime_after = ((float)$usec + (float)$sec);
            $stime_after = 0;
        }
        return array(
            'user' => sprintf('%1.6f', $utime_after - $utime_before),
            'system' => sprintf('%1.6f', $stime_after - $stime_before),
            'result' => $result
        );
    }

}

