<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Cache class
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2014 Topazos, Inc.
 * @since File available since Release 1.0.0
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

class Cache
{

    /**
     * Path of cache directory
     * @var string
     */
    private $path = FALSE;

    /**
     * Seconds of cache limit
     * @var integer
     */
    private $limit = 3600; // 1 hour

    /**
     * Functional directory name
     * @var string
     */
    private $target = 'default';

    /**
     * Flag of cache action (on: TRUE, off: FALSE)
     * @var boolean
     */
    private $flag = TRUE;

    /**
     * Constructor
     */
    public function __construct($p = array())
    {
        $this->config($p);
    }

    /**
     * Configure
     */
    public function config($p)
    {
        if (isset($p['flag'])) {
            $this->flag = (bool)$p['flag'];
        }
        if (isset($p['target'])) {
            $this->target = $p['target'];
        }
        if (isset($p['path'])) {
            $this->path = $p['path'];
        }
        if (isset($p['limit'])) {
            $this->limit = $p['limit'];
        }
        if (isset($p['hierarchy'])) {
            $this->hierarchy = $p['hierarchy'];
        }
    }

    /**
     * Get cache directory path
     */
    private function basepath()
    {
        if (empty($this->path)) {
            throw new Exception('cache path error: not set cache directory path');
        } elseif (! is_dir($this->path)) {
            throw new Exception('cache path error: not exists cache directory path');
        }
        return $this->path;
    }

    /**
     * Get cache file real path
     */
    private function filepath($word)
    {
        $hash = md5($word);
        $path = $this->basepath() . $this->target . DS;
        for ($i = 0; $i < $this->hierarchy; $i++) {
            // coution: use [<] , not [<=]
            $path .= $hash{$i} . DS;
        }
        $path .= $hash;
        return $path;
    }

    /**
     * Prepare hierarchy directory to cache
     */
    private function prepare($path)
    {
        $dir = $path;
        $dirs = array();
        for ($i = 0; $i <= $this->hierarchy; $i++) {
            $dir = dirname($dir);
            if (is_dir($dir)) {
                break;
            }
            $dirs[] = $dir;
        }
        while (($p = array_pop($dirs)) !== NULL) {
            mkdir($p, 0777);
        }
    }

    /**
     * Crean cache
     */
    public function clean()
    {
        if (! $this->flag) { return; }
        $path = $this->basepath();
        $real = realpath($path);
        $rlen = strlen($real);
        $dirs = array($real);
        for (;;) {
            // infinity of loop
            $dir = rtrim(array_shift($dirs), DS) . DS;
            if (is_dir($dir)) {
                $res = glob($dir . '*');
                $cnt = count($res);
                foreach ($res as $p) {
                    if (is_dir($p)) {
                        $dirs[] = $p;
                    } elseif (! $this->valid($p)) {
                        // clean
                        unlink($p);
                        $cnt--;
                    }
                }
                if ($cnt === 0 && $real !== realpath($dir)) {
                    rmdir($dir);
                    if ($real !== ($parent = dirname($dir)) && $rlen < strlen($parent)) {
                        // add parent dir, to be continued under $real dir
                        $dirs[] = $parent;
                    }
                }
            }
            if (empty($dirs)) {
                // done and break loop
                break;
            }
        }
    }

    /**
     * Check vaild cache or not
     */
    private function valid($path)
    {
        return (is_readable($path) && (time() - filemtime($path)) < $this->limit);
    }

    /**
     * exists
     */
    public function exist($word)
    {
        $path = $this->filepath($word);
        return $this->valid($path);
    }

    /**
     * Get data from cache
     */
    public function pull($word)
    {
        if (! $this->flag) { return; }
        $path = $this->filepath($word);
        if ($this->valid($path)) {
            return unserialize(file_get_contents($path));
        } else {
            return FALSE;
        }
    }

    /**
     * Put data to cache
     */
    public function push($word, $data)
    {
        if (! $this->flag) { return; }
        $pre = umask();
        umask(0);
        $path = $this->filepath($word);
        $this->prepare($path);
        if (($fp = fopen($path, 'w')) !== FALSE) {
            flock($fp, LOCK_EX);
            fwrite($fp, serialize($data));
            flock($fp, LOCK_UN);
            chmod($path, 0666);
        }
        fclose($fp);
        umask($pre);
    }

    /**
     * Automatic cache action
     */
    public function auto($word, $func)
    {
        if (! $this->flag) { return; }
        if (($res = $this->pull($word)) === FALSE) {
            $res = $func($this);
            if (! is_null($res)) {
                $this->push($word, $res);
            }
        }
        return $res;
    }

}

