<?php
/* vim:se et st=4 sw=4 sts=4 */
/**
 * Curl multi class
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2014 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Curlmulti
{
    
    private $opt = array(
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => TRUE,
        CURLOPT_FOLLOWLOCATION => FALSE, // no fallow location
    );

    private $url = array();

    private $running = NULL;

    public function __construct($param = array())
    {
        $this->config($param);
    }

    public function config($param = array())
    {
        if (is_array($param)) {
            $this->opt = $param + $this->opt;
        }
    }

    public function url(array $url = array(), $init = FALSE)
    {
        $tmp = array();
        foreach ($url as $val) {
            $tmp[md5($val)] = $val;
        }
        $this->url = $init ? $tmp : ($tmp + $this->url);
    }

    private function &prepare()
    {
        $mh = curl_multi_init();
        foreach ($this->url as $val) {
            $ch = curl_init();
            curl_setopt_array($ch, array(CURLOPT_URL => $val) + $this->opt);
            curl_multi_add_handle($mh, $ch);
        }
        do {
            $stat = curl_multi_exec($mh, $this->running); // multi request
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        if (! $this->running || $stat !== CURLM_OK) {
            throw new RuntimeException('Can not start request.');
        }
        return $mh;
    }

    public function drive()
    {
        $mh = $this->prepare();
        $result = array();
        do {
            switch (curl_multi_select($mh, $this->opt[CURLOPT_TIMEOUT])) {
                case -1 : // failure to select
                    usleep(10); // wait a bit.
                    do {
                        // retry process
                        $stat = curl_multi_exec($mh, $this->running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);
                case 0 : // timeout to select
                    continue 2; // retry
                default : // done
                    do {
                        // update process
                        $stat = curl_multi_exec($mh, $this->running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);

                    do {
                        if ($raised = curl_multi_info_read($mh, $remains)) {
                            // get response
                            $info = curl_getinfo($raised['handle']);
                            $info['response'] = curl_multi_getcontent($raised['handle']);
                            curl_multi_remove_handle($mh, $raised['handle']);
                            curl_close($raised['handle']);
                            $result[] = $info;
                        }
                    } while ($remains);
            }
        } while ($this->running);
        curl_multi_close($mh);
        $this->running = NULL;
        return $result;
    }

}

