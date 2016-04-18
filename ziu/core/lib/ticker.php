<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ticker Profiling Library Engine.
 * 
 *  Usage:
 *    Ticker start with ticks for profiling function.
 * 
 *    Ticker::init();
 *    declare(ticks=1);
 *    register_tick_function(array('Ticker', 'profile'));
 *    register_shutdown_function(array('Ticker', 'display'));
 * 
 *    Query profiling with Ticker.
 * 
 *    Ticker::query_profile_start($sql);
 *    (...execute sql query...)
 *    Ticker::query_profile_stop();
 * 
 *    Ticker stop
 * 
 *    unregister_tick_function(array('Ticker', 'profile'));
 *    Ticker::stop();
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Ticker
{

    // {{{ variable
    /**
     * Tiem info
     * @var float
     */
    static private $first_time;
    static private $last_time;

    /**
     * Memory info
     * @var integer
     */
    static private $first_memo;
    static private $last_memo;

    /**
     * Profile data
     * @var array
     */
    static private $profile = array();

    /**
     * Total calls
     * @var integer
     */
    static private $total_call = 0;

    /**
     * Total time
     * @var float
     */
    static private $total_time = 0;

    /**
     * Total memo
     * @var float
     */
    static private $total_memo = 0;

    /**
     * Total file
     * @var integer
     */
    static private $total_file = 0;

    /**
     * Total class
     * @var integer
     */
    static private $total_class = 0;

    /**
     * Flag of cli
     * @var boolean
     */
    static private $is_cli  = FALSE;

    /**
     * Flag of ajax
     * @var boolean
     */
    static private $is_ajax  = FALSE;

    /**
     * Query info
     * @var array
     */
    static private $query = array();

    /**
     * Float num
     * @var integer
     */
    static private $float = 5;

    /**
     * Files
     * @var array
     */
    static private $files = array();

    /**
     * Classes
     * @var array
     */
    static private $classes = array();

    /**
     * Stop flag
     * @var boolean
     */
    static private $stop = FALSE;
    // }}}

    /**
     * Init
     * @return void
     */
    static public function init($time = NULL, $memory = NULL)
    {
        static::$stop = FALSE;
        static::$is_cli = (php_sapi_name() == 'cli' || defined('STDIN'));
        if (! static::$is_cli) {
            static::$is_ajax =  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        }
        ob_start();
        static::$first_time = is_null($time) ? microtime(TRUE) : $time;
        static::$first_memo = is_null($memory) ? memory_get_usage() : $memory;
        static::$last_time = static::$first_time;
        static::$last_memo = static::$first_memo;
    }

    /**
     * Query profile start
     * @param array $sql : sql
     * @return void
     */
    static public function query_profile_start($sql)
    {
        if (static::$stop === FALSE) {
            static::$query['last'] = array(
                'sql' => $sql,
                'time' => microtime(TRUE),
            );
        }
    }

    /**
     * Query profile stop
     * @return void
     */
    static public function query_profile_stop()
    {
        if (static::$stop === FALSE) {
            if (isset(static::$query['last'])) {
                static::$query[] = array(
                    'sql' => static::$query['last']['sql'],
                    'time' => microtime(TRUE) - static::$query['last']['time'],
                );
                unset(static::$query['last']);
            }
        }
    }

    /**
     * Profile
     * @return void
     */
    static public function profile()
    {
        $trace = debug_backtrace();
        if (count($trace) <= 1) { return ; }
        $frame = $trace[1];
        unset($trace);
        $func = '';
        $func .= isset($frame['class']) ? $frame['class'] : '';
        $func .= isset($frame['type']) ? $frame['type'] : '';
        $func .= $frame['function'];
        if (! isset(static::$profile[$func])) {
            static::$profile[$func] = array(
                'call' => 0,
                'amount_time' => 0,
                'amount_memo' => 0,
            );
        }
        $this_time = microtime(TRUE);
        $this_memo = memory_get_usage();
        static::$profile[$func]['call']++;
        static::$profile[$func]['proc_time'] = $this_time - static::$first_time;
        static::$profile[$func]['proc_memo'] = $this_memo - static::$first_memo;
        static::$profile[$func]['amount_time'] += $this_time - static::$last_time;
        static::$profile[$func]['amount_memo'] += $this_memo - static::$last_memo;
        static::$last_time = $this_time;
        static::$last_memo = $this_memo;
    }

    /**
     * Stop
     * @return void
     */
    static public function stop()
    {
        static::$stop = TRUE;
        static::_parse_total();
    }

    /**
     * Display
     * @return void
     */
    static public function display()
    {
        if (static::$is_cli) {
            static::_show_text();
        } elseif (static::$is_ajax) {
            static::_show_ajax();
        } else {
            static::_show_html();
        }
    }

    // {{{ parse
    /**
     * Parse result
     * @return array
     */
    static private function _parse_result()
    {
        if (static::$stop !== TRUE) {
            static::_parse_total();
        }
        return array(
            'result' => array(
                'tick'  => static::_parse_function(),
                'query' => static::_parse_query(),
                'file'  => static::_parse_file(),
                'class' => static::_parse_class(),
                'get'   => static::_parse_get(),
                'post'  => static::_parse_post(),
                'session' => static::_parse_session(),
            ),
            'total_call' => static::$total_call,
            'total_time' => static::$total_time,
            'total_memo' => static::$total_memo,
            'total_file' => static::$total_file,
            'total_class' => static::$total_class,
        );
    }

    /**
     * Parse total
     * @return void
     */
    static private function _parse_total()
    {
        $float = static::$float;
        static::$files = get_included_files();
        static::$classes = get_declared_classes();
        static::$total_time = sprintf("%.{$float}f", (microtime(TRUE) - static::$first_time));
        static::$total_memo = number_format(memory_get_usage() / 1024);
        static::$total_file = count(static::$files);
        static::$total_class = count(static::$classes);
    }

    /**
     * Parse function
     * @return string
     */
    static private function _parse_function()
    {
        $tick = "proc time(sec)\tproc memo(KB)\tcalls\tamount(sec)\taverage(sec)\tamount(KB)\tfunction\n";
        $tick .= "-------------------------------------------------------------------------------------------------------------------\n";
        $float = static::$float;
        foreach (static::$profile as $func => $val) {
            $call = $val['call'];
            $proc_time = sprintf("%.{$float}f", $val['proc_time']);
            $proc_memo = number_format($val['proc_memo'] / 1024);
            $amount_time = sprintf("%.{$float}f", $val['amount_time']);
            $average_time = sprintf("%.{$float}f", ($val['amount_time'] / $val['call']));
            $amount_memo = number_format($val['amount_memo'] / 1024);
            if (! static::$is_cli && ! static::$is_ajax) {
                $color = 'white';
                if ($call > 100) { $color = 'yellow'; }
                if ($amount_time > 0.01) { $color = 'orange'; }
                if ($average_time > 0.01) { $color = 'red'; }
                $tick .= "<font color=\"$color\">";
            }
            $tick .= $proc_time . "\t\t";
            $tick .= $proc_memo . "\t\t";
            $tick .= $call . "\t";
            $tick .= $amount_time . "\t\t";
            $tick .= $average_time . "\t\t";
            $tick .= $amount_memo . "\t\t[" . $func . "]";
            if (! static::$is_cli && ! static::$is_ajax) {
                $tick .= "</font>";
            }
            $tick .= "\n";
            static::$total_call += $call;
        }
        return $tick;
    }

    /**
     * Parse query
     * @return string
     */
    static private function _parse_query()
    {
        $float = static::$float;
        $query = "time(sec)\tquery\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        foreach (static::$query as $data) {
            if (! static::$is_cli && ! static::$is_ajax) {
                $color = 'white';
                if ($data['time'] > 0.01) { $color = 'red'; }
                $query .= "<font color=\"$color\">";
            }
            $query .= sprintf("%.{$float}f", $data['time']) . "\t\t" . $data['sql'];
            if (! static::$is_cli && ! static::$is_ajax) {
                $query .= "</font>";
            }
            $query .= "\n";
        }
        return $query;
    }

    /**
     * Parse file
     * @return string
     */
    static private function _parse_file()
    {
        $query = "size(KB)\tfile\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        foreach (static::$files as $file) {
            $query .= number_format((filesize($file) / 1024), 2) . "\t\t" . $file. "\n";
        }
        return $query;
    }

    /**
     * Parse class
     * @return string
     */
    static private function _parse_class()
    {
        $name_pad = 30;
        $query = "type\tspace\t" . str_pad('name', $name_pad) . "\tfile\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        foreach (static::$classes as $class) {
            $ref = new ReflectionClass($class);
            $type = $ref->isUserDefined() ? 'User' : 'System';
            $space = $ref->getNamespaceName();
            $file = $ref->getFileName();
            $query .= $type . "\t" . $space . "\t" . str_pad($class, $name_pad) . "\t" . $file . "\n";
        }
        return $query;
    }

    /**
     * Parse get
     * @return string
     */
    static private function _parse_get()
    {
        $count = 0;
        $str = isset($_GET) ? static::_parse_array($_GET, $count) : '';
        $query = "get request (total : $count)\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        $query .= $str;
        return $query;
    }

    /**
     * Parse post
     * @return string
     */
    static private function _parse_post()
    {
        $count = 0;
        $str = isset($_POST) ? static::_parse_array($_POST, $count) : '';
        $query = "post request (total : $count)\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        $query .= $str;
        return $query;
    }

    /**
     * Parse session
     * @return string
     */
    static private function _parse_session()
    {
        $count = 0;
        $str = isset($_SESSION) ? static::_parse_array($_SESSION, $count) : '';
        $query = "session (total : $count)\n";
        $query .= "-------------------------------------------------------------------------------------------------------------------\n";
        $query .= $str;
        return $query;
    }

    /**
     * Parse array
     * @param array   $data   : array data
     * @param integer &$count : array counter
     * @return string
     */
    static private function _parse_array(array $data, &$count)
    {
        $str = '';
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $str .= "[$key]" . static::_parse_array($val, $count);
            } else {
                $str .= "[$key] = $val\n";
                $count++;
            }
        }
        return $str;
    }
    // }}}

    // {{{ show
    /**
     * Show ajax
     * @return void
     */
    static private function _show_ajax()
    {
        extract(static::_parse_result());
        $data = array(
            'TickerProfiling' => array(
                'Total' => "calls : $total_call / time : $total_time sec / memory : $total_memo KB / file : $total_file / class : $total_class",
                'Function' => explode("\n", $result['tick']),
                'Query' => explode("\n", $result['query']),
                'File' => explode("\n", $result['file']),
                'Class' => explode("\n", $result['class']),
                'Get' => explode("\n", $result['get']),
                'Post' => explode("\n", $result['post']),
                'Session' => explode("\n", $result['session']),
            ),
        );
        $ob = ob_get_clean();
        $res = json_decode($ob);
        if ($res !== NULL && json_last_error() == JSON_ERROR_NONE) {
            // Json
            if (is_object($res)) {
                $res->TickerProfiling = $data['TickerProfiling'];
            } elseif (is_array($res)) {
                $res = $data + $res;
            }
            $ob = json_encode($res);
        } elseif (strpos($ob, '<?xml ') !== FALSE) {
            // Xml
            $xml = new SimpleXMLElement($ob);
            foreach ($data as $key => $val) {
                $xmlticker = $xml->addChild($key);
                foreach ($val as $k => $v) {
                    if (is_array($v)) {
                        $xmltickerchild = $xmlticker->addChild($k);
                        foreach ($v as $i => $r) {
                            $xmltickerchild->addChild('item' . $i, htmlspecialchars($r, ENT_QUOTES));
                        }
                    } else {
                        $xmlticker->addChild($k, htmlspecialchars($v, ENT_QUOTES));
                    }
                }
            }
            $ob = $xml->asXML();
        } else {
            // Other
            $ob .= "\n" . print_r($data, TRUE);
        }
        echo($ob);
    }

    /**
     * Show text
     * @return void
     */
    static private function _show_text()
    {
        extract(static::_parse_result());
        $str = "[Function]\n" . $result['tick'] . "\n";
        $str .= "[Query]\n" . $result['query'] . "\n";
        $str .= "[File]\n" . $result['file'] . "\n";
        $str .= "[Class]\n" . $result['class'] . "\n";
        $str .= "[Get]\n" . $result['get'] . "\n";
        $str .= "[Post]\n" . $result['post'] . "\n";
        $str .= "[Session]\n" . $result['session'] . "\n";
        $text = <<<EOT
----------------
Ticker Profiling
----------------
total - calls : $total_call / time : $total_time sec / memory : $total_memo KB / file : $total_file / class : $total_class
$str
EOT;
        echo($text);
    }

    /**
     * Show html
     * @return void
     */
    static private function _show_html()
    {
        extract(static::_parse_result());
        $html = <<<EOB
<style type="text/css">
.ticker {
    background-color: #eee;
    font-size: 12px;
    border: 1px solid gray;
    padding: 5px;
    color: gray;
    width: 70%;
    margin: 0 auto;
}
.ticker .title {
    font-weight: bold;
    font-size: 13px;
    color: white;
    margin: 5px 2px;
    border-bottom: solid 1px gray;
    background-color: gray;
    padding: 2px 5px;
    text-align: left;
}
.ticker .sub {
    color: gray;
    margin: 5px 2px;
    font-weight: bold;
    text-align: left;
}
.ticker .menu {
    padding: 0;
    margin: 0;
}
.ticker .menu li {
    float: left;
    list-style: none;
    border: 1px solid gray;
    padding: 4px;
    margin: 2px;
    width: 80px;
    text-align: center;
    cursor: pointer;
    background-color: white;
}
.ticker .menu li:first-child {
    background-color: gray;
    color: white;
}
.ticker .menu li:last-child {
    float: right;
}
.ticker .menu li:hover {
    background-color: gray;
    color: white;
}
.ticker .result {
    clear: both;
}
.ticker .result pre {
    background-color: #333;
    font-size: 12px;
    color: #fff;
    border: 1px solid gray;
    padding: 10px 3px 3px 8px;
    margin: 0;
    line-height: 1;
    overflow: auto;
    height: 300px;
    display: none;
    cursor: text;
    text-align: left;
}
.ticker .result pre:first-child {
    display: block;
}
</style>
<div class="ticker" style="position: absolute; top: 100px; left: 200px; cursor: move;">
    <p class="title">Ticker Profiling</p>
    <p class="sub">
        total - calls : $total_call / time : $total_time sec / memory : $total_memo KB / file : $total_file / class : $total_class
    </p>
    <ul class="menu">
        <li onclick="ticker_tab(this)">Function</li>
        <li onclick="ticker_tab(this)">Query</li>
        <li onclick="ticker_tab(this)">File</li>
        <li onclick="ticker_tab(this)">Class</li>
        <li onclick="ticker_tab(this)">Get</li>
        <li onclick="ticker_tab(this)">Post</li>
        <li onclick="ticker_tab(this)">Session</li>
        <li onclick="ticker_tab(this)">Shade</li>
    </ul>
    <div class="result">
        <pre name="Function"><code>{$result['tick']}</code></pre>
        <pre name="Query"><code>{$result['query']}</code></pre>
        <pre name="File"><code>{$result['file']}</code></pre>
        <pre name="Class"><code>{$result['class']}</code></pre>
        <pre name="Get"><code>{$result['get']}</code></pre>
        <pre name="Post"><code>{$result['post']}</code></pre>
        <pre name="Session"><code>{$result['session']}</code></pre>
    <div>
</div>
<script type="text/javascript">
var ticker = document.getElementsByClassName('ticker').item(0);
var obj;
var offsetX;
var offsetY;
onload=function() {
    ticker.onmousedown   = onMouseDown;
    document.onmousemove = onMouseMove;
    document.onmouseup   = onMouseUp;
}
function onMouseDown(e)
{
    if (e.target.className == '') { return; }
    obj = this;
    if (document.all) {
        offsetX = event.offsetX + 2;
        offsetY = event.offsetY + 2;
    } else if (obj.getElementsByTagName) {
        offsetX = e.pageX - parseInt(obj.style.left);
        offsetY = e.pageY - parseInt(obj.style.top);
    }
    return false;
}
function onMouseMove(e)
{
   if (! obj) { return true; }
   if (document.all) {
      obj.style.left = event.clientX - offsetX + document.body.scrollLeft;
      obj.style.top = event.clientY - offsetY + document.body.scrollTop;
   } else if (obj.getElementsByTagName) {
      obj.style.left = e.pageX - offsetX;
      obj.style.top = e.pageY - offsetY;
   }
   return false;
}
function onMouseUp(e)
{
   obj = null;
}
function ticker_tab(o)
{
    var menu   = ticker.childNodes[5].childNodes;
    var result = ticker.childNodes[7].childNodes;
    for (var i = 0; i < menu.length; i++) {
        if (menu.item(i).tagName == 'LI') {
            menu.item(i).style.backgroundColor = 'white';
            menu.item(i).style.color = 'gray';
        }
    }
    for (var i = 0; i < result.length; i++) {
        if (result.item(i).tagName == 'PRE') {
            var name = result.item(i).getAttribute('name');
            if (name == o.innerText) {
                result.item(i).style.display = 'block';
                o.style.backgroundColor = 'gray';
                o.style.color = 'white';
            } else {
                result.item(i).style.display = 'none';
            }
        }
    }
}
</script>
EOB;
        $ob = ob_get_clean();
        if (strpos($ob, '</body>') !== FALSE) {
            $ob = str_replace('</body>', $html . '</body>', $ob);
        } else {
            $ob .= $html;
        }
        echo($ob);
    }
    // }}}

}

