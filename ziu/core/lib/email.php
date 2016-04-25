<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Database Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);


class Email
{

    // {{{ variable
    /**
     * Variables
     */
    private $config = array(
        'mailer_name'     => 'Ziu',
        'internal_enc'    => 'UTF-8', // internal encoding
        'template_dir'    => '',
        'template_ext'    => 'php',
        'debug_path'      => '',
        'debug_mode'      => FALSE,
        'engine'          => 'mail', // mail, sendmail, smtp, smtp-pop, smtp-plain, smtp-login, smtp-cram
        'sendmail'        => '/usr/sbin/sendmail -oi -f %s -t',
        'safe_mode'       => FALSE,
        'smtp_protocol'   => 'tcp', // tcp(25,587), ssl(465) or tls(587)
        'smtp_hostname'   => '',
        'smtp_host'       => '',
        'smtp_user'       => '',
        'smtp_pass'       => '',
        'smtp_port'       => 25,
        'smtp_pop_proto'  => 'tcp', // tcp(110), ssl(995) or tls(110)
        'smtp_pop_host'   => '',
        'smtp_pop_user'   => '',
        'smtp_pop_pass'   => '',
        'smtp_pop_port'   => 110,
        'smtp_pop_expire' => 300, // 300 sec (5 min)
        'smtp_pop_cache'  => '',  // pop cache file path. if no indication, pop authenticate in each process.
        'socket_timeout'  => 5,
        'stream_ssl_opts' => array(
            'verify_peer'       => TRUE, // check cert
            'allow_self_signed' => TRUE, // allow self cert
            'verify_peer_name'  => TRUE, // check domain
        ),
        'mime_charset'    => 'ISO-2022-JP',
        'mime_header_enc' => 'B', // B(Base64) or Q(Quoted-Printable)
        'mime_string_enc' => 'Q', // B(Base64) or Q(Quoted-Printable)
        'mime_7bitset'    => array('ISO-2022-JP', 'US-ASCII'),
        'mime_message'    => 'This mail is a multipart format.', // message for no support
        'html_multipart'  => TRUE, // use multipart for html
        'newline'         => "\r\n", // new line feed
        'separators'      => ';,', // indicate characters to split()
        'replacer'        => '##', // keyword replacer in subject, body
        'regex_email'     => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', // use _valid_address()
        'directives'      => array( // directive in headers 'name' => '1:text,2:single,3:multi'
            'return-path' => 1,
            'reply-to'    => 2,
            'from'        => 2,
            'to'          => 3,
            'cc'          => 3,
            'bcc'         => 3,
            'subject'     => 1,
        ),
    );

    private $maildata = array(
        'return-path' => '',   // Return-Path: default is using from
        'reply-to' => array(), // Reply-To: ['address', 'text']
        'from' => array(),     // From: ['address', 'text']
        'to'   => array(),     // To: [['address', 'text'], ...]
        'cc'   => array(),     // Cc: [['address', 'text'], ...]
        'bcc'  => array(),     // Bcc: [['address', 'text'], ...]
        'subject' => '',       // Subject: text
        'body' => array(),     // Body ['text', 'html']
    );

    private $additional = array(); // ['header directive', ...]
    private $attachment = array(); // [['path', 'attachment or inline'], ...]
    private $keyword = array();    // [['keyrword' => 'value'], ...]
    private $mailtype = FALSE;     // direct indication with text, text-attach, html or html-attach
    private $engine = FALSE;       // execute engine
    private $execute = array(
        'debug'      => '_exec_debug',
        'mail'       => '_exec_mail',
        'sendmail'   => '_exec_sendmail',
        'smtp'       => '_exec_smtp',
        'smtp-pop'   => '_exec_smtp_pop',
        'smtp-plain' => '_exec_smtp_plain',
        'smtp-login' => '_exec_smtp_login',
        'smtp-cram'  => '_exec_smtp_cram',
    );
    private $organized = array(
        'content-type' => '',      // Content-Type:
        'subject'      => '',      // Subject: text
        'body'         => array(), // Body ['text', 'html']
    );

    private $smtp_hold = FALSE; // TRUE: keep to open smtp socket
    private $smtp_connect = FALSE;
    private $pop_lasttime = 0;
    private $smtp_command = array(
        'helo'       => array(250, 'HELO %s'),
        'ehlo'       => array(250, 'EHLO %s'),
        'starttls'   => array(220, 'STARTTLS'),
        'from'       => array(250, 'MAIL FROM: <%s>'),
        'rcpt'       => array(250, 'RCPT TO: <%s>'),
        'data'       => array(354, 'DATA'),
        'data-put'   => array(250, ''),
        'quit'       => array(221, 'QUIT'),
        'auth-plain' => array(235, 'AUTH PLAIN %s'),
        'auth-login' => array(334, 'AUTH LOGIN'),
        'auth-cram'  => array(334, 'AUTH CRAM-MD5'),
        'user'       => array(334, '%s'),
        'pass'       => array(235, '%s'),
        'cram'       => array(235, '%s'),
    );
    // }}}

    // {{{ constructor
    /**
     * Constructor
     * @param array $config : config variables
     */
    public function __construct($config = array())
    {
        $this->config($config);
    }
    // }}}

    // {{{ config
    /**
     * Config
     * @param mixed $config  : list of config or name
     * @param array &$origin : original config
     * @return mixed setter(this) or getter(mixed)
     */
    public function config($config, &$origin = FALSE)
    {
        if ($origin === FALSE) {
            $origin = &$this->config;
        }
        if (is_array($config)) { // set
            foreach ($config as $name => $value) {
                if (isset($origin[$name])) {
                   if (is_array($value)) {
                        $this->config($value, $origin[$name]);
                    } else {
                        $origin[$name] = $value;
                    }
                }
            }
        } elseif (is_string($config)) { // get
            $node = explode('/', $config);
            foreach ($node as $key => $name) {
                if (isset($origin[$name])) {
                    return isset($node[$key + 1])
                        ? $this->config(implode('/', array_slice($node, 1)), $origin[$name])
                        : $origin[$name]
                        ; // end of return
                }
            }
        }
        return $this;
    }
    // }}}

    // {{{ operate
    /**
     * Clear
     * @param string $name : directive name
     * @return object this
     */
    public function clear($name = FALSE)
    {
        if ($name === FALSE) {
            foreach ($this->maildata as $key => $val) {
                $this->_clear($key);
            }
            $this->additional = array();
            $this->attachment = array();
            $this->keyword    = array();
        } elseif (isset($this->maildata[$name])) {
            $this->_clear($name);
        } elseif ($name == 'header') {
            $this->additional = array();
        } elseif ($name == 'attachment') {
            $this->attachment = array();
        } elseif ($name == 'keyword') {
            $this->keyword = array();
        }
        return $this;
    }

    /**
     * Clear maildata and organized maildata
     * @param string $name : directive name
     * @return void
     */
    private function _clear($name)
    {
        $this->maildata[$name] = is_array($this->maildata[$name]) ? array() : '';
        if (isset($this->organized[$name])) {
            $this->organized[$name] = is_array($this->organized[$name]) ? array() : '';
        }
    }

    /**
     * Set subject
     * @param string $str : address
     * @return object this
     */
    public function subject($str)
    {
        $this->maildata['subject'] = $this->_decode_mimeheader($str);
        return $this;
    }

    /**
     * Set body
     * @param string $text : text
     * @param string $html : html
     * @return object this
     */
    public function body($text, $html = FALSE)
    {
        if ($text !== FALSE) {
            $this->maildata['body'][0] = str_replace(array("\r\n", "\r"), "\n", $text);
        }
        if ($html !== FALSE) {
            $this->maildata['body'][1] = str_replace(array("\r\n", "\r"), "\n", $html);
        }
        return $this;
    }

    /**
     * Set return-path (envelope from)
     * @param string $str : address
     * @return object this
     */
    public function return_path($str)
    {
        $this->_set_address('return-path', $str);
        return $this;
    }

    /**
     * Set reply-to
     * @param string $str : address
     * @return object this
     */
    public function reply_to($str)
    {
        $this->_set_address('reply-to', $str);
        return $this;
    }

    /**
     * Set from
     * @param string $str   : address or addresses
     * @param string $label : text label
     * @return object this
     */
    public function from($str, $label = '')
    {
        $this->_set_address('from', $str, $label);
        return $this;
    }

    /**
     * Set to
     * @param string $str   : address or addresses
     * @param string $label : text label
     * @return object this
     */
    public function to($str, $label = '')
    {
        $this->_set_address('to', $str, $label);
        return $this;
    }

    /**
     * Set cc
     * @param string $str   : address or addresses
     * @param string $label : text label
     * @return object this
     */
    public function cc($str, $label = '')
    {
        $this->_set_address('cc', $str, $label);
        return $this;
    }

    /**
     * Set bcc
     * @param string $str   : address or addresses
     * @param string $label : text label
     * @return object this
     */
    public function bcc($str, $label = '')
    {
        $this->_set_address('bcc', $str, $label);
        return $this;
    }

    /**
     * Set attachment
     * @param mixed $file : file path (string or array)
     * @param string $disp : attachment or inline
     * @return object this
     */
    public function attachment($file, $disp = 'attachment')
    {
        if (is_array($file)) {
            foreach ($file as $val) {
                $this->attachment($val, $disp);
            }
        } else {
            $file_in_template_dir = rtrim($this->config('template_dir'), DS) . DS . $file;
            $path = is_readable($file) ? $file
                    : (is_readable($file_in_template_dir) ? $file_in_template_dir : '');
            if ($path === '') {
                throw new Exception("Email: attachment [$file] not exists");
            }
            $this->attachment[] = array($path, $disp);
        }
        return $this;
    }

    /**
     * Load template
     * @param string $name : template name
     * @param array  $vars : variables
     * @return object this
     */
    public function template($name, $vars = array())
    {
        $result = FALSE;
        if ($this->_parse_template($name, 'head', $vars)) {
            $result = TRUE;
        }
        if ($this->_parse_template($name, 'html', $vars)) {
            $result = TRUE;
        }
        if ($this->_parse_template($name, 'text', $vars)) {
            $result = TRUE;
        }
        if (! $result) {
            throw new Exception("Email: template [$name] not exists");
        }
        return $this;
    }

    /**
     * Set additional header
     * @param array $header : additional header
     * @return object this
     */
    public function header($header)
    {
        if (is_array($header)) {
            $this->additional = array_merge($this->additional, $header);
        } else {
            $this->additional[] = $header;
        }
        return $this;
    }
    // }}}

    // {{{ replacer
    /**
     * Set keyword
     * @param array $set : keyword set
     * @return object this
     */
    public function keyword(array $set)
    {
        $this->keyword = $set + $this->keyword;
        return $this;
    }

    /**
     * Replace keyword
     * @param array $set : keyword set
     * @return object this
     */
    public function replace(array $set = array())
    {
        if (! empty($set)) {
            $this->keyword($set);
        }
        $quote = $this->config('replacer');
        $keyset = $valset = array();
        foreach ($this->keyword as $key => $val) {
            $keyset[] = $quote . $key . $quote;
            $valset[] = $val;
        }
        // subject
        $this->organized['subject'] = str_replace($keyset, $valset, $this->maildata['subject']);
        // body
        if (! empty($this->maildata['body'][0])) {
            $this->organized['body'][0] = str_replace($keyset, $valset, $this->maildata['body'][0]);
        }
        if (! empty($this->maildata['body'][1])) {
            $this->organized['body'][1] = str_replace($keyset, $valset, $this->maildata['body'][1]);
        }
        return $this;
    }
    // }}}

    // {{{ send
    /**
     * Send mail
     * @param string $engine   : debug, mail, sendmail, smtp, smtp-pop,
     *                           smtp-plain, smtp-loign or smtp-cram
     * @param string $mailtype : text, html, text-attach, html-attach
     * @return mixed boolean or string(debug)
     */
    public function send($engine = FALSE, $mailtype = FALSE)
    {
        if (empty($this->maildata['from'][0])) {
            throw new Exception('Email: from not exists');
        }
        if (empty($this->maildata['to'][0][0])) {
            throw new Exception('Email: to not exists');
        }
        $this->engine   = $engine === FALSE ? $this->config('engine') : $engine;
        $this->mailtype = $mailtype;
        $data = empty($this->keyword) ? $this->maildata : $this->organized;
        if (isset($this->execute[$this->engine])) {
            $result = $this->{$this->execute[$this->engine]}($data['subject'], $data['body']);
        } else {
            $this->engine   = FALSE;
            $this->mailtype = FALSE;
            throw new Exception("Email: invalid engine [{$this->engine}]");
        }
        return $result;
    }

    /**
     * Open smtp
     * @param string $engine   : debug, mail, sendmail, smtp, smtp-pop,
     *                           smtp-plain, smtp-loign or smtp-cram
     * @param string $mailtype : text, html, text-attach, html-attach
     * @return mixed void
     */
    public function open($engine = FALSE, $mailtype = FALSE)
    {
        $this->smtp_hold = TRUE;
        $this->engine    = $engine === FALSE ? $this->config('engine') : $engine;
        $this->mailtype  = $mailtype;
        if (strpos($this->engine, 'smtp') === 0 && isset($this->execute[$this->engine])) {
            $result = $this->{$this->execute[$this->engine]}('', array());
        } else {
            $this->engine   = FALSE;
            $this->mailtype = FALSE;
            throw new Exception("Email: invalid smtp open engine [{$this->engine}]");
        }
    }

    /**
     * Push smtp
     * @return void
     */
    public function push()
    {
        if ($this->smtp_hold === FALSE || ! is_resource($this->smtp_connect)) {
            throw new Exception('Email: smtp not open');
        }
        if (empty($this->maildata['from'][0])) {
            throw new Exception('Email: from not exists');
        }
        if (empty($this->maildata['to'][0][0])) {
            throw new Exception('Email: to not exists');
        }
        $data = empty($this->keyword) ? $this->maildata : $this->organized;
        $result = $this->_exec_smtp($data['subject'], $data['body']);
        $this->clear('header');
        return $result;
    }

    /**
     * Close smtp
     * @return void
     */
    public function close()
    {
        if (! is_resource($this->smtp_connect)) {
            throw new Exception('Email: nothing to close smtp connection');
        }
        $this->_smtp_command('quit');
        $this->_smtp_disconnect();
        $this->engine    = FALSE;
        $this->mailtype  = FALSE;
        $this->smtp_hold = FALSE;
    }
    // }}}

    // {{{ exec
    /**
     * Execute debug mail source
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return string
     */
    public function _exec_debug($subject, $body)
    {
        $this->config['debug_mode'] = TRUE;
        $body   = $this->_build_body($body);
        $header = $this->_build_header_all($subject);
        return str_replace("\n", $this->config('newline'), "$header\n\n$body");
    }

    /**
     * Execute mail function
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return boolean
     */
    private function _exec_mail($subject, $body)
    {
        $to      = $this->_data_directive('to');
        $body    = $this->_build_body($body);
        $subject = $this->_encode_mimeheader($subject);
        $header  = $this->_build_header_mail();
        if ($this->config('safe_mode')) {
            return mail($to, $subject, $body, $header);
        } else {
            $envelope = $this->_get_return_path();
            return mail($to, $subject, $body, $header, "-f{$envelope}");
        }
    }

    /**
     * Execute sendmail
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return boolean
     */
    private function _exec_sendmail($subject, $body)
    {
        $body   = $this->_build_body($body, TRUE);
        $header = $this->_build_header_all($subject);
        $data = str_replace("\n", $this->config('newline'), "$header\n\n$body");
        $fp = popen(sprintf($this->config('sendmail'), $this->_get_return_path()), 'w');
        if (! is_resource($fp)) {
            throw new Exception('Email: sendmail popen error');
        } else {
            if (fwrite($fp, $data) === FALSE) {
                throw new Exception('Email: sendmail fwrite error');
            } elseif (($stat = pclose($fp)) !== 0) {
                throw new Exception("Email: sendmail pclose error [status: $stat]");
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Execute SMTP with pop before smtp
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return void
     */
    private function _exec_smtp_pop($subject, $body)
    {
        $this->_check_smtp_config('smtp_pop');
        $this->_smtp_pop_auth(); // pop before smtp
        $this->_smtp_connect();
        if ($this->smtp_hold === FALSE) {
            return $this->_exec_smtp($subject, $body);
        }
    }

    /**
     * Execute SMTP-AUTH PLAIN
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return void
     */
    private function _exec_smtp_plain($subject, $body)
    {
        $this->_check_smtp_config('smtp');
        $this->_smtp_connect();
        $user = $this->config('smtp_user');
        $pass = $this->config('smtp_pass');
        $this->_smtp_command('auth-plain', base64_encode("$user\0$user\0$pass"));
        if ($this->smtp_hold === FALSE) {
            return $this->_exec_smtp($subject, $body);
        }
    }

    /**
     * Execute SMTP-AUTH LOGIN
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return void
     */
    private function _exec_smtp_login($subject, $body)
    {
        $this->_check_smtp_config('smtp');
        $this->_smtp_connect();
        $this->_smtp_command('auth-login');
        $this->_smtp_command('user', base64_encode($this->config('smtp_user')));
        $this->_smtp_command('pass', base64_encode($this->config('smtp_pass')));
        if ($this->smtp_hold === FALSE) {
            return $this->_exec_smtp($subject, $body);
        }
    }

    /**
     * Execute SMTP-AUTH CRAM-MD5
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return void
     */
    private function _exec_smtp_cram($subject, $body)
    {
        $this->_check_smtp_config('smtp');
        $this->_smtp_connect();
        $recv = $this->_smtp_command('auth-cram');
        $salt = base64_decode(substr($recv[1], 4));
        $hash = hash_hmac('md5', $salt, $this->config('smtp_pass'));
        $this->_smtp_command('cram', base64_encode($this->config('smtp_user') . ' ' . $hash));
        if ($this->smtp_hold === FALSE) {
            return $this->_exec_smtp($subject, $body);
        }
    }

    /**
     * Execute SMTP
     * @param string $subject : subject
     * @param array  $body    : body data [0 => text, 1 => html]
     * @return string
     */
    private function _exec_smtp($subject, $body)
    {
        if (! is_resource($this->smtp_connect)) {
            $this->_smtp_connect();
        }
        $body   = $this->_build_body($body, TRUE);
        $header = $this->_build_header_smtp($subject);
        $data = str_replace("\n", $this->config('newline'), "$header\n\n$body");
        $rcpt = array_merge($this->maildata['to'], $this->maildata['cc'], $this->maildata['bcc']);
        // command
        $this->_smtp_command('from', $this->_get_return_path());
        foreach ($rcpt as $val) {
            $this->_smtp_command('rcpt', $val[0]);
        }
        $this->_smtp_command('data');
        $this->_smtp_command('data-put', $data);
        if ($this->smtp_hold === FALSE) {
            $this->close();
        }
        return TRUE;
    }
    // }}}

    // {{{ debug
    /**
     * Debug
     * @param string $str : message
     * @return void
     */
    private function _debug($str)
    {
        if (! $this->config('debug_mode')) {
            return;
        }
        $msg = date('Y-m-d H:i:s') . " [debug] $str\n";
        if ($this->config('debug_path') !== '') {
            error_log($msg, 3, $this->config('debug_path'));
        } else {
            echo($msg);
        }
    }
    // }}}

    // {{{ smtp
    /**
     * Check SMTP configure
     * @param string $target : smtp or smtp_pop
     */
    private function _check_smtp_config($target)
    {
        foreach (array('host', 'port', 'user', 'pass') as $val) {
            if ($this->config("{$target}_{$val}") === '') {
                throw new Exception("Email: {$target}_{$val} config error");
            }
        }
    }

    /**
     * Get socket connect information
     * @param string $protocol : protocol with tcp, ssl or tls
     * @param string $host     : connect host
     * @return array
     */
    private function _smtp_socket_info($protocol, $host)
    {
        switch ($protocol) {
            case 'ssl' :
                $target = "ssl://$host";
                $option = array('ssl' => $this->config('stream_ssl_opts'));
                break;
            case 'tls' :
                $target = "tcp://$host";
                $option = array('ssl' => $this->config('stream_ssl_opts'));
                break;
            default :
                $target = "tcp://$host";
                $option = array();
        }
        return array('target' => $target, 'option' => $option);
    }

    /**
     * Authenticate pop before smtp Connect
     * @return void
     */
    private function _smtp_pop_auth()
    {
        // check cache time
        $cache_path = $this->config('smtp_pop_cache');
        $cache_time = 0;
        if (! empty($cache_path)) {
            if (is_file($cache_path)) {
                $cache_time = trim(file_get_contents($cache_path));
            }
        } else {
            $cache_time = $this->pop_lasttime;
        }
        if (time() - $cache_time <= $this->config('smtp_pop_expire')) {
            return; // noop in cache time
        }
        $protocol = $this->config('smtp_pop_proto');
        $host = $this->config('smtp_pop_host');
        $port = $this->config('smtp_pop_port');
        $time = $this->config('socket_timeout');
        $info = $this->_smtp_socket_info($protocol, $host);

        $this->_debug("smtp_pop_connect: {$info['target']}:$port");
        if (! empty($info['option'])) {
            $context = stream_context_create($info['option']);
            if (! is_resource($context)) {
                throw new Exception('Email: smtp_pop_auth socket option error');
            }
            $con = stream_socket_client("{$info['target']}:$port", $code, $msg, $time, STREAM_CLIENT_CONNECT, $context);
        } else {
            $con = fsockopen($info['target'], $port, $code, $msg, $time);
        }
        if (! is_resource($con)) {
            throw new Exception("Email: smtp_pop_auth connect error [$code $msg]");
        }
        $recv = function($cmd) use($con) {
            $recv = trim(fgets($con, 512));
            $this->_debug("pop($cmd): $recv");
            if (strtoupper(substr($recv, 0, 3)) != '+OK') {
                throw new Exception("Email: smtp_pop_auth recv $cmd error [$recv]");
            }
        };
        $send = function($cmd, $val = '') use($con, $recv) {
            $data = strtoupper($cmd) . (empty($val) ? '' : (' ' . $val));
            if (! fwrite($con, $data . $this->config('newline'))) {
                throw new Exception("Email: smtp_pop_auth send $cmd error [$data]");
            }
            $recv($cmd);
        };
        $recv('open');
        if ($protocol == 'tls') {
            $send('stls');
            if (! stream_socket_enable_crypto($con, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Email: smtp_pop_auth socket tls enable error');
            }
        }
        $send('user', $this->config('smtp_pop_user'));
        $send('pass', $this->config('smtp_pop_pass'));
        $send('quit');
        fclose($con);
        // set cache time
        if (! empty($cache_path)) {
            if ($fp = fopen($cache_path, 'w')) {
                flock($fp, LOCK_EX);
                fwrite($fp, time());
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        } else {
            $this->pop_lasttime = time();
        }
    }

    /**
     * Connect SMTP Socket
     * @return string
     */
    private function _smtp_connect()
    {
        $protocol = $this->config('smtp_protocol');
        $host = $this->config('smtp_host');
        $port = $this->config('smtp_port');
        $time = $this->config('socket_timeout');
        $info = $this->_smtp_socket_info($protocol, $host);
        $this->_debug("smtp_connect: {$info['target']}:$port");
        if (! empty($info['option'])) {
            $context = stream_context_create($info['option']);
            if (! is_resource($context)) {
                throw new Exception('Email: smtp_connect socket option error');
            }
            $this->smtp_connect = stream_socket_client("{$info['target']}:$port", $code, $msg, $time, STREAM_CLIENT_CONNECT, $context);
        } else {
            $this->smtp_connect = fsockopen($info['target'], $port, $code, $msg, $time);
        }
        if (! is_resource($this->smtp_connect)) {
            throw new Exception("Email: smtp_connect socket open error [$code $msg]");
        }
        $recv = $this->_smtp_recv();
        $this->_debug($recv[1]);
        $this->_smtp_command('hello');
        if ($protocol == 'tls') {
            $this->_smtp_command('starttls');
            if (! stream_socket_enable_crypto($this->smtp_connect
                , TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Email: smtp_connect socket tls enable error');
            }
        }
    }

    /**
     * Disconnect SMTP Socket
     * @return void
     */
    private function _smtp_disconnect()
    {
        if (! fclose($this->smtp_connect)) {
            throw new Exception('Email: smtp_disconnect error');
        }
    }

    /**
     * Send SMTP command
     * @param string $cmd : command
     * @param string $val : value
     * @return void
     */
    private function _smtp_command($cmd, $val = '')
    {
        switch ($cmd) {
            case 'hello' :
                $host = $this->config('smtp_hostname');
                if (in_array($this->engine, array('smtp-plain', 'smtp-login', 'smtp-cram')) || $this->_mime_encodebit() == '8bit') {
                    $talk = sprintf($this->smtp_command['ehlo'][1], $host);
                    $code = $this->smtp_command['ehlo'][0];
                } else {
                    $talk = sprintf($this->smtp_command['helo'][1], $host);
                    $code = $this->smtp_command['helo'][0];
                }
                break;
            case 'data-put' :
                $code = $this->smtp_command[$cmd][0];
                $talk = $val . $this->config('newline') . '.';
                break;
            default :
                if (isset($this->smtp_command[$cmd])) {
                    $code = $this->smtp_command[$cmd][0];
                    $talk = sprintf($this->smtp_command[$cmd][1], $val);
                } else {
                    throw new Exception("Email: smtp_command not exists [$cmd]");
                }
        }
        $this->_smtp_send($talk);
        $recv = $this->_smtp_recv();
        $this->_debug("$cmd: {$recv[1]}");
        if ($recv[0] != $code) {
            throw new Exception("Email: smtp_command error [{$recv[1]}]");
        }
        return $recv;
    }

    /**
     * Send SMTP data
     * @param string $data : data
     * @return void
     */
    private function _smtp_send($data)
    {
        if (! fwrite($this->smtp_connect, $data . $this->config('newline'))) {
            throw new Exception("Email: smtp_send error [$data]");
        }
    }

    /**
     * Receive SMTP data
     * @return array [code, data]
     */
    private function _smtp_recv()
    {
        $data = '';
        while (($str = fgets($this->smtp_connect, 512)) !== FALSE) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') { break; }
        }
        return array((int)substr($data, 0, 3), trim($data));
    }
    // }}}

    // {{{ builder
    /**
     * Build header for debug and sendmail
     * @return string
     */
    private function _build_header_all($subject)
    {
        $header = array();
        $header[] = $this->_header_directive('return-path', '<' . $this->_get_return_path() . '>');
        $header[] = $this->_header_directive('from');
        $header[] = $this->_header_directive('to');
        if (! empty($this->maildata['cc'])) {
            $header[] = $this->_header_directive('cc');
        }
        if (! empty($this->maildata['bcc'])) {
            $header[] = $this->_header_directive('bcc');
        }
        if (! empty($this->maildata['reply-to'])) {
            $header[] = $this->_header_directive('reply-to');
        }
        $header[] = $this->_header_directive('subject', $subject);
        return $this->_build_header($header);
    }

    /**
     * Build header for mail function
     * @return string
     */
    private function _build_header_mail()
    {
        $header = array();
        $header[] = $this->_header_directive('from');
        if (! empty($this->maildata['cc'])) {
            $header[] = $this->_header_directive('cc');
        }
        if (! empty($this->maildata['bcc'])) {
            $header[] = $this->_header_directive('bcc');
        }
        if (! empty($this->maildata['reply-to'])) {
            $header[] = $this->_header_directive('reply-to');
        }
        return $this->_build_header($header);
    }

    /**
     * Build header for smtp
     * @return string
     */
    private function _build_header_smtp($subject)
    {
        $header = array();
        $header[] = $this->_header_directive('return-path', '<' . $this->_get_return_path() . '>');
        $header[] = $this->_header_directive('from');
        $header[] = $this->_header_directive('to');
        if (! empty($this->maildata['cc'])) {
            $header[] = $this->_header_directive('cc');
        }
        if (! empty($this->maildata['reply-to'])) {
            $header[] = $this->_header_directive('reply-to');
        }
        $header[] = $this->_header_directive('subject', $subject);
        return $this->_build_header($header);
    }

    /**
     * Build header
     * @param array $header : header directive list
     * @return string
     */
    private function _build_header($header)
    {
        $header = array_merge($header, $this->organized['content-type'], $this->additional);
        $header[] = 'X-Mailer: ' . $this->config('mailer_name');
        $header[] = 'X-Sender: ' . $this->maildata['from'][0];
        $header[] = 'Mime-Version: 1.0';
        $header[] = 'Message-ID: <' . $this->_gen_message_id() . '>';
        return implode($this->config('newline'), $header);
    }

    /**
     * Build body
     * @param array   $body   : body data [0 => text, 1 => html]
     * @param boolean $escape : escape head '.' character of each line in body for smtp
     * @return string
     */
    private function _build_body($body, $escape = FALSE)
    {
        switch ($this->_mailtype()) {
            case 'text' :
                $result = $this->_build_body_text($body);
                break;
            case 'html' :
                $result = $this->_build_body_html($body);
                break;
            case 'text-attach' :
                $result = $this->_build_body_text_attach($body);
                break;
            case 'html-attach' :
                $result = $this->_build_body_html_attach($body);
                break;
            default :
                $result = '';
        }
        if ($escape) {
            $result = preg_replace('/^\./m', '..$1', $result);
        }
        return $result;
    }

    /**
     * Build body for text
     * @param array $body : body data [0 => text, 1 => html]
     * @return string
     */
    private function _build_body_text($body)
    {
        $text = $this->_prepare_content_text($body[0]);
        $this->organized['content-type'] = $text['head'];
        return $text['data'];
    }

    /**
     * Build body for html
     * @param array $body : body data [0 => text, 1 => html]
     * @return string
     */
    private function _build_body_html($body)
    {
        if ($this->config('html_multipart') === FALSE) {
            $html = $this->_prepare_content_html($body[1]);
            $this->organized['content-type'] = $html['head'];
            return $html['data'];
        } else {
            $alt = $this->_build_content_alternative($body);
            $this->organized['content-type'] = $alt['head'];
            return $this->config('mime_message') . "\n\n" . $alt['data'];
        }
    }

    /**
     * Build body for text-attach
     * @param array $body : body data [0 => text, 1 => html]
     * @return string
     */
    private function _build_body_text_attach($body)
    {
        $bound = $this->_gen_boundary('mix');
        $this->organized['content-type'] = $this->_header_content_type_boundary('multipart/mixed', $bound);
        $text = $this->_prepare_content_text($body[0]);
        $str = $this->config('mime_message') . "\n\n";
        $str .= "--$bound\n";
        $str .= implode("\n", $text['head']) . "\n\n{$text['data']}\n\n";
        foreach ($this->attachment as $val) {
            $attach = $this->_prepare_content_attachment($val);
            $str .= "--$bound\n";
            $str .= implode("\n", $attach['head']) . "\n\n{$attach['data']}\n\n";
        }
        $str .= "--$bound--";
        return $str;
    }

    /**
     * Build body for html-attach
     * @param array $body : body data [0 => text, 1 => html]
     * @return string
     */
    private function _build_body_html_attach($body)
    {
        $bound = $this->_gen_boundary('mix');
        // firstly attachment for disposition of inline
        $att = '';
        $tags = array();
        foreach ($this->attachment as $val) {
            $attach = $this->_prepare_content_attachment($val);
            $att .= "--$bound\n";
            $att .= implode("\n", $attach['head']) . "\n\n{$attach['data']}\n\n";
            if (isset($attach['cid'])) {
                $tag = "cid:{$attach['cid']}";
                $body[1] = str_replace($val[0], $tag, $body[1]);
                $tags[$tag] = basename($val[0]);
            }
        }
        $this->organized['content-type'] = $this->_header_content_type_boundary('multipart/mixed', $bound);
        $alt = $this->_build_content_alternative($body, $tags);
        $str = $this->config('mime_message') . "\n\n";
        $str .= "--$bound\n";
        $str .= "{$alt['head']}\n\n{$alt['data']}\n\n";
        $str .= $att; // attachment
        $str .= "--$bound--";
        return $str;
    }

    /**
     * Prepare content data for text/plain
     * @param string $data : text body
     * @return array ['head' => array, 'data' => string]
     */
    private function _prepare_content_text($data)
    {
        return array(
            'head' => array(
                $this->_header_content_type_charset('text/plain'),
                $this->_header_content_transfer_encoding($this->_mime_encodebit()),
            ),
            'data' => $this->_encode_mimestring($data),
        );
    }

    /**
     * Prepare content data for text/html
     * @param string $data : html body
     * @return array ['head' => array, 'data' => string]
     */
    private function _prepare_content_html($data)
    {
        return array(
            'head' => array(
                $this->_header_content_type_charset('text/html'),
                $this->_header_content_transfer_encoding($this->_mime_encodetxt()),
            ),
            'data' => $this->_encode_htmlstring($data),
        );
    }

    /**
     * Prepare content data for multipart/alternative
     * @param array $body : body data [0 => text, 1 => html]
     * @param array $tags : cid tags for inline
     * @return array ['head' => array, 'data' => string]
     */
    private function _build_content_alternative($body, $tags = array())
    {
        $bound = $this->_gen_boundary('alt');
        if (empty($body[0])) {
            $temp = $this->_strip($body[1]);
            if (! empty($tags)) {
                // (Image: cid:01234) to (Image: filename)
                $temp = str_replace(array_keys($tags), array_values($tags), $temp);
            }
        } else {
            $temp = $body[0];
        }
        $text = $this->_prepare_content_text($temp);
        $html = $this->_prepare_content_html($body[1]);
        return array(
            'head' => $this->_header_content_type_boundary('multipart/alternative', $bound),
            'data' => "--$bound\n"
                    . implode("\n", $text['head']) . "\n\n{$text['data']}\n\n"
                    . "--$bound\n"
                    . implode("\n", $html['head']) . "\n\n{$html['data']}\n"
                    . "--$bound--"
        );
    }

    /**
     * Prepare content data for attachment
     * @param array $data : file data [0 => path, 1 => disposition]
     * @return array ['head' => array, 'data' => string]
     */
    private function _prepare_content_attachment($data)
    {
        if ($fp = fopen($data[0], 'rb')) {
            $bin = fread($fp, filesize($data[0]));
            fclose($fp);
        } else {
            throw new Exception("Email: attachment [{$data[0]}] fopen error");
        }
        $filename = basename($data[0]);
        $result =  array(
            'head' => array(
                $this->_header_content_type_filename($this->_mime_type($data[0]), $filename),
                $this->_header_content_disposition($data[1], $filename),
                $this->_header_content_transfer_encoding('base64'),
            ),
            'data' => $this->_chunk(base64_encode($bin)),
        );
        if ($data[1] == 'inline') {
            $cid = md5($data[0]);
            $result['head'][] = $this->_header_content_id($cid);
            $result['cid'] = $cid;
        }
        return $result;
    }

    /**
     * Create boundary
     * @param string $pref : prefix
     * @return string
     */
    private function _gen_boundary($pref = '')
    {
        return $this->config('mailer_name') . '_' . $pref . '_' . uniqid();
    }

    /**
     * Build address
     * @param array $data : address data
     * @return string
     */
    private function _build_address($data)
    {
        if ($data[1] === '') {
            return $data[0];
        } else {
            return $this->_encode_mimeheader($data[1]) . " <{$data[0]}>";
        }
    }
    // }}}

    // {{{ header
    /**
     * Create Message-ID string
     * @return string
     */
    private function _gen_message_id()
    {
        return md5(microtime()) . '.' . uniqid() . strstr($this->_get_return_path(), '@');
    }

    /**
     * Get Return-Path address
     * @return string
     */
    private function _get_return_path()
    {
        return empty($this->maildata['return-path']) ? $this->maildata['from'][0] : $this->maildata['return-path'];
    }

    /**
     * Get header directive
     * @param string $name : directive name
     * @param string $data : directive data
     * @return string
     */
    private function _header_directive($name, $data = FALSE)
    {
        return implode('-', array_map(function($val){
            return ucwords($val);
        }, explode('-', $name))) . ': ' . $this->_data_directive($name, $data);
    }

    /**
     * Get header directive data string
     * @param string $name : directive name
     * @param string $data : directive data
     * @return string
     */
    private function _data_directive($name, $data = FALSE)
    {
        $data = $data === FALSE ? $this->maildata[$name] : $data;
        switch ($this->config['directives'][$name]) {
            case 1 : // text
                $result = $this->_encode_mimeheader($data);
                break;
            case 2 : // single
                $result = $this->_build_address($data);
                break;
            case 3 : // multi
                $addr = array();
                foreach ($data as $val) {
                    $addr[] =  $this->_build_address($val);
                }
                $result = implode(', ', $addr);
                break;
            default :
                $result = '';
        }
        return $result;
    }

    /**
     * Get Content-Type header directive with charset
     * @param string $mime : mime
     * @return string
     */
    private function _header_content_type_charset($mime)
    {
        return 'Content-Type: ' . $mime . '; charset="' . $this->config('mime_charset') . '"';
    }

    /**
     * Get Content-Type header directive with filename
     * @param string $mime : mime
     * @param string $name : filename
     * @return string
     */
    private function _header_content_type_filename($mime, $name)
    {
        return 'Content-Type: ' . $mime . '; name="' . $this->_encode_mimeheader($name) . '"';
    }

    /**
     * Get Content-Type header directive with boundary
     * @param string $mime     : mime
     * @param string $boundary : boundary
     * @return string
     */
    private function _header_content_type_boundary($mime, $boundary)
    {
        return 'Content-Type: ' . $mime . '; boundary="' . $boundary . '"';
    }

    /**
     * Get Content-Transfer-Encoding header directive
     * @param string $encode : encode
     * @return string
     */
    private function _header_content_transfer_encoding($encode)
    {
        return 'Content-Transfer-Encoding: ' . $encode;
    }

    /**
     * Get Content-Disposition header directive
     * @param string $disp     : disposition
     * @param string $filename : filename
     * @return string
     */
    private function _header_content_disposition($disp, $filename = '')
    {
        return 'Content-Disposition: ' . $disp . ';'
        . ($filename !== '' ? (' filename="' . $this->_encode_mimeheader($filename) . '"') : '');
    }

    /**
     * Get Content-ID header directive
     * @param string $id : contentid
     * @return string
     */
    private function _header_content_id($id)
    {
        return 'Content-ID: <' . $id . '>';
    }
    // }}}

    // {{{ parse directive
    /**
     * Set address
     * @param string $name  : directive name
     * @param string $str   : address or addresses
     * @param string $label : text label
     * @return object this
     */
    private function _set_address($name, $str, $label = '')
    {
        if ($label === '') {
            $this->_set_directive_string($name, $str);
        } else {
            $this->_set_directive_address($name, $str, $label);
        }
    }

    /**
     * Set directive address
     * @param string $name   : directive name
     * @param string $addr   : addresses
     * @param string $label  : text label
     * @return void
     */
    private function _set_directive_address($name, $addr, $label)
    {
        if (! $this->_valid_address($addr)) {
            throw new Exception('Email: ' . $name . ' is invalid [' . $addr . ']');
        }
        switch ($this->config['directives'][$name]) {
            case 1 : // text
                $this->maildata[$name] = $addr;
                break;
            case 2 : // single
                $this->maildata[$name] = array($addr, $label);
                break;
            case 3 : // multi
                $append = TRUE;
                foreach ($this->maildata[$name] as $key => $val) {
                    if ($val[0] == $addr) {
                        $this->maildata[$name][$key] = array($addr, $label);
                        $append = FALSE;
                        break;
                    }
                }
                if ($append) {
                    $this->maildata[$name][] = array($addr, $label);
                }
                break;
            default :
        }
    }

    /**
     * Set directive string
     * @param string $name : directive name
     * @param string $str  : addresses
     * @return void
     */
    private function _set_directive_string($name, $str)
    {
        $list = $this->_split_addresses($str);
        if ($this->config['directives'][$name] == 3) {
            // multi
            foreach ($list as $item) {
                $res = $this->_parse_address(trim($item));
                $this->_set_directive_address($name, $res[0], $res[1]);
            }
        } else {
            // single
            $res = $this->_parse_address(trim($list[0]));
            $this->_set_directive_address($name, $res[0], $res[1]);
        }
    }
    // }}}

    // {{{ parse template
    /**
     * Parse template
     * @param string $name : file name
     * @param string $type : head, text or html
     * @param array  $vars : variables
     * @return boolean
     */
    private function _parse_template($name, $type, $vars)
    {
        $dir = rtrim($this->config('template_dir'), DS);
        $path = $dir . DS . pathinfo($name, PATHINFO_FILENAME) . '.' . $type . '.' . $this->config('template_ext');
        if (! is_readable($path)) {
            return FALSE;
        }
        $temp = $this->_include_template($path, $vars);
        if ($type == 'head') {
            $items = $this->_parse_template_header(explode("\n", $temp));
        } else {
            $pos  = strpos($temp, "\n\n");
            $items = $this->_parse_template_header(explode("\n", substr($temp, 0, $pos)));
            if (count($items) > 0) {
                // with header
                $body = substr($temp, $pos + 2);
            } else {
                // no header
                $body = $temp;
            }
            if ($type == 'html') {
                $this->body(FALSE, $body);
            } else { // text
                $this->body($body);
            }
        }
        return TRUE;
    }

    /**
     * Parse template header
     * @param array $lines : header lines
     * @return array parsed headers
     */
    private function _parse_template_header($lines)
    {
        $items = array();
        foreach ($lines as $line) {
            if (strpos($line, ':') !== FALSE) {
                list($key, $val) = array_map('trim', explode(':', $line));
                $key = strtolower($key);
                if (isset($this->config['directives'][$key])) {
                    $items[$key][] = $val;
                    $method = str_replace('-', '_', $key);
                    $this->{$method}($val);
                }
            }
        }
        return $items;
    }

    /**
     * Include template
     * @param string $path : template file path
     * @param array  $vars : variables
     * @return string template
     */
    private function _include_template($path, array $vars = array())
    {
        extract($vars);
        ob_start();
        require $path;
        return str_replace(array("\r\n", "\r"), "\n", ob_get_clean());
    }
    // }}}

    // {{{ tool
    /**
     * Chunk string
     * @param string $str : string
     * @return string
     */
    private function _chunk($str)
    {
        return chunk_split($str, 76, "\n");
    }

    /**
     * Wrap string
     * @param string $str : string
     * @return string
     */
    private function _wrap($str)
    {
        return wordwrap($str, 76, "\n", FALSE);
    }

    /**
     * Get mail type
     * @return string mail type
     */
    private function _mailtype()
    {
        if ($this->mailtype === FALSE) {
            $type = empty($this->maildata['body'][1]) ? 'text' : 'html';
            $type .= empty($this->attachment) ? '' : '-attach';
        } else {
            switch ($this->mailtype) {
                case 'text' :
                case 'text-attach' :
                case 'html' :
                case 'html-attach' :
                    $type = $this->mailtype;
                    break;
                default :
                    $type = 'text';
            }
        }
        return $type;
    }

    /**
     * Strip html tags
     * @param string $html : html
     * @return string converted text
     */
    private function _strip($html)
    {
        if (preg_match('/<body.*?>(.*)<\/body>/si', $html, $match)) {
            $body = $match[1];
        } else {
            $body = $html;
        }
        $body = preg_replace('/<img .*src=["\']?([^ "\'\>]*).*?>/i', '(Image: $1)', $body);
        $body = preg_replace('/<div.*?>(.*)<\/div>/si', "$1\n", $body);
        $body = preg_replace('/<!--.*-->/s', '', $body);
        $body = str_replace(array('<br>', '<br/>', '<br />'), "\n", $body);
        $body = str_replace("\t", '', $body);
        $body = trim(strip_tags($body));
        for ($i = 20; $i >= 3; $i--) {
            $body = str_replace(str_repeat("\n", $i), "\n\n", $body);
        }
        return $body;
    }

    /**
     * Parse address
     * @param string $str : address
     * @return array array(address, text)
     */
    private function _parse_address($str)
    {
        if (preg_match('/^"?([^"]*?)"? ?<([^>]+?)>$/', $str, $match)) {
            $addr = $match[2];
            $name = $this->_decode_mimeheader(trim($match[1], '" '));
            return array($addr, $name);
        } elseif (($pos = strrpos($str, ' ')) !== FALSE) {
            $addr = trim(substr($str, $pos));
            $name = $this->_decode_mimeheader(trim(substr($str, 0, $pos), '" '));
            return array($addr, $name);
        } else {
            return array($str, '');
        }
    }

    /**
     * Split addresses
     * @param string $str : addresses
     * @return array address list
     */
    private function _split_addresses($str)
    {
        return array_map('trim', (array)preg_split('/[' . preg_quote($this->config('separators')) . ']/', $str));
    }

    /**
     * Check address
     * @param string $addr : address
     * @return boolean
     */
    private function _valid_address($addr)
    {
        return (bool)preg_match($this->config('regex_email'), $addr);
    }
    // }}}

    // {{{ mime
    /**
     * Get mime encode bit
     * @return string
     */
    private function _mime_encodebit()
    {
        return in_array($this->config('mime_charset'), $this->config('mime_7bitset')) ? '7bit' : '8bit';
    }

    /**
     * Get mime encode text
     * @return string
     */
    private function _mime_encodetxt()
    {
        return $this->config('mime_string_enc') == 'Q' ? 'quoted-printable' : 'base64';
    }

    /**
     * Encode html string
     * @param string $str : text
     * @return string
     */
    private function _encode_htmlstring($str)
    {
        $pre = array("\r\n", "\r", "\n", "\t", '  ');
        $pos = array(    '',   '',   '',  ' ',  ' ');
        $str = str_replace($pre, $pos, $str);
        $str = preg_replace('/<!--.*-->/s', '', $str);
        if ($this->config('mime_string_enc') == 'Q') {
            // quoted_printable_encode() : PHP 5.3+
            return quoted_printable_encode($this->_encode_mimestring($str, FALSE));
        } else {
            return $this->_chunk(base64_encode($this->_encode_mimestring($str, FALSE)));
        }
    }

    /**
     * Encode mime string
     * @param string $str : text
     * @return string
     */
    private function _encode_mimestring($str, $wrap = TRUE)
    {
        $str = mb_convert_encoding($str, $this->config('mime_charset'), $this->config('internal_enc'));
        return $wrap ? $this->_wrap($str) : $str;
    }

    /**
     * Encode mime header
     * @param string $str : text
     * @return string
     */
    private function _encode_mimeheader($str)
    {
        $system = strtoupper(mb_internal_encoding());
        $config = strtoupper($this->config('internal_enc'));
        if ($system != $config) {
            mb_internal_encoding($config);
            $result =  mb_encode_mimeheader($str, $this->config('mime_charset'), $this->config('mime_header_enc'));
            mb_internal_encoding($system);
        } else {
            $result =  mb_encode_mimeheader($str, $this->config('mime_charset'), $this->config('mime_header_enc'));
        }
        return $result;
    }

    /**
     * Decode mime header
     * @param string $str : encoded mime text
     * @return string
     */
    private function _decode_mimeheader($str)
    {
        if (preg_match('/^=\?[^?]+\?[BQ]\?[^?]+\?=$/', $str)) {
            $str = mb_decode_mimeheader($str);
        }
        return $str;
    }

    /**
     * Get mime type
     * @param string $path : file path
     * @return string mime-type
     */
    private function _mime_type($path)
    {
        // use if the fileinfo extension, if available (>= 5.3 supported).
        if ((float)substr(phpversion(), 0, 3) >= 5.3 && function_exists('finfo_file')) {
            if (($finfo = new finfo(FILEINFO_MIME_TYPE)) !== FALSE) {
                $file_type = $finfo->file($path);
                if (strlen($file_type) > 1) {
                    return $file_type;
                }
            }
        }
        // fall back to the deprecated mime_content_type(), if available.
        if (function_exists('mime_content_type')) {
            return @mime_content_type($path);
        }
        // use linux file command, if available.
        if (DS !== '\\' && function_exists('exec')) {
            $out = array();
            @exec('file --brief --mime-type ' . escapeshellarg($path), $out, $ret);
            if ($ret === 0 && strlen($out[0]) > 0) {
                return rtrim($out[0]);
            }
        }
        // at last, unknown
        return 'application/x-unknown-content-type';
    }
    // }}}

}

