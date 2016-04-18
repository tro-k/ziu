<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Validate Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Websocket
{

    // {{{ constant/property
    // SUCCESS
    const SUCCESS            = '000';
    // DATA BASE
    const DB_FAILURE         = '100';
    const DB_NODATA          = '101';
    const DB_DUPULICATION    = '102';
    // REQUEST
    const REQ_INVALID_PARAM  = '200';
    const REQ_VALIDATION_ERR = '201';
    // SERVICE
    const SRV_INVALID        = '300';
    const SRV_NOSSL          = '301';
    const SRV_NOACCEPTION    = '302';
    const SRV_NOACTIVATION   = '303';

    private static $instance = array();
    private $clients = array();
    private $binary_action = 'binary/data';
    private $opening = FALSE;
    private $closing = FALSE;
    // }}}

    // {{{ constructor
    protected function __construct() {}

    final private function __clone() {}
    
    final public static function getInstance()
    {
        $class = get_called_class();
        if (! isset(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }
    // }}}

    // {{{ event
    public function onConnect($client)
    {
        $this->clients[$client->getClientId()] = $client;
        if (gettype($this->opening) == 'object') {
            call_user_func_array($this->opening, array($client));
        }
    }

    public function onDisconnect($client)
    {
        unset($this->clients[$client->getClientId()]);
        if (gettype($this->closing) == 'object') {
            call_user_func_array($this->closing, array($client));
        }
    }

    public function onData($json, $client)
    {
        $res = $this->decode($json);
        if ($res === FALSE) {
            $this->send($client, 'exception', self::REQ_INVALID_PARAM);
        } else {
            invoke($res['action'], array(
                'client'  => $client,
                'request' => $res,
            ));
        }
    }

    public function onBinaryData($binary, $client)
    {
        invoke($this->binary_action, array(
            'client' => $client,
            'binary' => $binary,
        ));
    }
    // }}}

    // {{{ operation
    public function client($id)
    {
        return isset($this->clients[$id]) ? $this->clients[$id] : FALSE;
    }

    public function clients()
    {
        return $this->clients;
    }

    public function broadcast($action, $code, $msg = '', array $data = array())
    {
        $json = $this->encode($action, $code, $msg, $data);
        foreach ($this->clients as $client) {
            $client->send($json);
        }
    }

    public function send($client, $action, $code, $msg = '', array $data = array())
    {
        $json = $this->encode($action, $code, $msg, $data);
        $client->send($json);
    }

    public function opening($func)
    {
        $this->opening = $func;
    }

    public function closing($func)
    {
        $this->closing = $func;
    }

    public function binary_action($action)
    {
        $this->binary_action = $action;
    }
    // }}}

    // {{{ tool
    private function message($code)
    {
        switch ($code) {
            // 000
            case self::SUCCESS : // 000
                $msg = 'OK';
                break;
            // 100...
            case self::DB_FAILURE : // 100
                $msg = 'DB処理が失敗しました';
                break;
            case self::DB_NODATA : // 101
                $msg = '該当レコードが存在しませんでした';
                break;
            case self::DB_DUPULICATION : // 102
                $msg = '該当レコードが重複しています';
                break;
            // 200...
            case self::REQ_INVALID_PARAM : // 200
                $msg = '受信パラメーターが不正(invalid parameter)';
                break;
            case self::REQ_VALIDATION_ERR : // 201
                $msg = '受信データが不正(validation error)';
                break;
            // 300...
            case self::SRV_INVALID : // 300
                $msg = '不正なアクセスです(invalid access)';
                break;
            case self::SRV_NOSSL : // 301
                $msg = '不正なアクセスです(no ssl)';
                break;
            case self::SRV_NOACCEPTION : // 302
                $msg = '不正なアクセスです(no acception)';
                break;
            case self::SRV_NOACTIVATION : // 303
                $msg = '不正なアクセスです(no activation)';
                break;
            // others...
            default :
                $msg = '';
        }
        return $msg;
    }

	private function decode($json)
	{
		$res = json_decode($json, TRUE);
		return ! empty($res['action']) ? $res : FALSE;
	}
	
    private function encode($action, $code, $msg = '', array $data = array())
    {
        return json_encode(array(
			'action'  => $action,
			'code'    => $code,
			'message' => $msg === '' ? $this->message($code) : $msg,
			'data'    => empty($data) ? (object)$data : $data,
        ));
    }
    // }}}

}

