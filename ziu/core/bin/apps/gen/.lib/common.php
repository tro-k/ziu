<?php

class Common
{

    private $rollback = FALSE;
    private $skip_columns = array('inserted_at', 'updated_at');

    public function args($argv)
    {
        $params = array();
        foreach ($argv as $param) {
            if (preg_match('/^--([a-z-_]+)=(.*)$/', $param, $match)) {
                $params[$match[1]] = $match[2];
            }
        }
        return $params;
    }

    private function user_apps_path($type, $app = FALSE)
    {
        switch ($type) {
            case 'conf' :
                $dir = ZIU_USER_CONF_DIR;
                break;
            case 'model' :
                $dir = ZIU_USER_MODEL_DIR;
                break;
            case 'unit' :
                $dir = ZIU_USER_UNIT_DIR;
                break;
            case 'lib' :
                $dir = ZIU_USER_LIB_DIR;
                break;
            case 'init' :
                $dir = ZIU_USER_INIT_DIR;
                break;
            case 'help' :
                $dir = ZIU_USER_HELP_DIR;
                break;
            case 'vendor' :
                $dir = ZIU_USER_VENDOR_DIR;
                break;
            default :
                return FALSE;
        }
        $conf = array(conf('core/user_apps_path') . $dir . DS);
        if ($app) {
            $conf[] = conf('core/user_apps_path') . $app . DS . $dir . DS;
        }
        return $conf;
    }

    public function config($params)
    {
        static $config = NULL;
        if (! is_null($config)) { return $config; }
        $pname = 'app,user,pass,db,table';
        try {
            // [method: 2]
            // php -q script/gen [target] --app=APP_NAME --user=USER --pass=PASS --db=DBNAME --table=TABLE
            // check arguments
            foreach (explode(',', $pname) as $var) {
                if (! isset($params[$var])) {
                    throw new Exception('next to app table format');
                }
            }
            // check app dir
            if (! $this->is_app($params['app'])) {
                throw new Exception('no app directory');
            }
            $params['host'] = isset($params['host']) ? $params['host'] : 'localhost';
            $config = $params;
            return $config;
        } catch (Exception $e) {
            // [method: 1]
            // php -q script/gen [target] APP_NAME TABLE
            try {
                $args = array_slice($_SERVER['argv'], 2);
                // check arguments
                if (count($args) < 2) {
                    throw new Exception('give up and go to error');
                }
                list($app, $table) = $args;
                // check app dir
                if (! $this->is_app($app)) {
                    throw new Exception('no app directory');
                }
                // load user help for database_connection_name()
                $help = $this->user_apps_path('help', $app);
                foreach ($help as $path) {
                    $path = str_replace('/', DS, $path);
                    $file = $path . 'core.php';
                    if (is_readable($file)) {
                        $data = include_once $file;
                    }
                }
                // load user conf for database_connection()
                $conf = $this->user_apps_path('conf', $app);
                $tmp = array();
                foreach ($conf as $path) {
                    $path = str_replace('/', DS, $path);
                    $file = $path . 'db.php';
                    if (is_readable($file)) {
                        $data = include_once $file;
                        if (is_array($data)) {
                            $tmp = $data + $tmp;
                        }
                    }
                }
                if (isset($tmp['connection'][$tmp['connection_name']])) {
                    $con = $tmp['connection'][$tmp['connection_name']];
                    if (isset($con['master'])) {
                        // use master, when master/slave defined
                        $con = $con['master'];
                    }
                    list($sche, $info) = explode(':', $con['dsn']);
                    $dsn = array();
                    foreach (explode(';', $info) as $str) {
                        list($key, $val) = explode('=', $str);
                        $dsn[$key] = $val;
                    }
                    if (isset($dsn['dbname']) && isset($dsn['host'])) {
                        $config = array(
                            'user' => $con['user'],
                            'pass' => $con['pass'],
                            'db'   => $dsn['dbname'],
                            'host' => $dsn['host'],
                            'table' => $table,
                            'app'   => $app,
                        );
                        return $config;
                    }
                }
            } catch (Exception $e) {
                $this->usage('Error: no database connection information. (' . $e->getMessage() . ')');
            }
        }
    }

    public function usage($error = 'Error...')
    {
        $usage = "
$error

You can use 2 method to connect database below.
[method: 1]
use db.php configuration file in apps/.conf or apps/APP_NAME/.conf.
----------------------------------------------
php -q script/gen [target] APP_NAME TABLE

[method: 2]
indicate configuration info with command.
----------------------------------------------
php -q script/gen [target] --app=APP_NAME --user=USER --pass=PASS --db=DBNAME --table=TABLE


[target] is...
    create scaffold name for program file

    scaffold      : all create automatically in app directory !! only [method: 1] !!
    model         : model php class
    logic         : logic php class
    view/index    : index.view.php
    view/input    : input.view.php
    view/confirm  : confirm.view.php
    view/complete : complete.view.php
    view/detail   : detail.view.php
    unit/index    : unit php class for logic::index() method
    unit/default  : unit php class for common logic class

";
        die($usage);
    }

    public function schema($p)
    {
        $host = empty($p['host']) ? 'localhost' : $p['host'];
        $conf = array(
            'dsn' => "mysql:host={$host};dbname={$p['db']}",
            'user' => $p['user'],
            'pass' => $p['pass'],
        );
        $db = lib('database', $conf);
        $sch = $db->query('show full columns from ' . $p['table'])->all();
        $res = array('table_id' => '', 'columns' => array());
        foreach ($sch as $val) {
            if ($val['Key'] == 'PRI') {
                $res['table_id'] = $val['Field'];
            }
            $res['columns'][] = array(
                'field' => $val['Field'],
                'type'  => $val['Type'],
                'null'  => $val['Null'],
                'key'   => $val['Key'],
                'comment' => $val['Comment'],
            );
        }
        if (empty($res['table_id'])) {
            // set first column, if no primary key.
            $res['table_id'] = $res['columns'][0]['field'];
        }
        return $res;
    }

    public function filepath($params)
    {
        extract($params);
        $app_path = conf('core/user_apps_path') . $app . DS;
        $dir_path = $app_path . $table . DS;
        $model_path = $app_path . ZIU_USER_MODEL_DIR . DS;
        $unit_path  = $app_path . ZIU_USER_UNIT_DIR . DS;
        $paths = array(
            'logic' => $app_path . $table . '.' . conf('core/suffix_logic') . '.php',
            'model' => $model_path . $table . '.php',
            'view/index' => $dir_path . ZIU_USER_FUNC_PREFIX . 'index.' . conf('core/suffix_view') . '.php',
            'view/input' => $dir_path . ZIU_USER_FUNC_PREFIX . 'input.' . conf('core/suffix_view') . '.php',
            'view/confirm' => $dir_path . ZIU_USER_FUNC_PREFIX . 'confirm.' . conf('core/suffix_view') . '.php',
            'view/detail' => $dir_path . ZIU_USER_FUNC_PREFIX . 'detail.' . conf('core/suffix_view') . '.php',
            'view/complete' => $dir_path . ZIU_USER_FUNC_PREFIX . 'complete.' . conf('core/suffix_view') . '.php',
            'unit/index' => $unit_path . $table . DS . 'index.php',
            'unit/default' => $unit_path . $table . DS . ZIU_USER_FUNC_PREFIX . 'default.php',
        );
        return $paths;
    }

    public function is_app($app)
    {
        return is_dir(conf('core/user_apps_path') . $app);
    }

    public function validate($paths)
    {
        $viewdir = dirname($paths['view/index']);
        $unitdir = dirname($paths['unit/index']);
        $appdir  = dirname($paths['logic']);
        if (is_dir($viewdir)) {
            throw new Exception("Error: [$viewdir] already exists. then exit.\n");
        }
        if (is_dir($unitdir)) {
            throw new Exception("Error: [$unitdir] already exists. then exit.\n");
        }
        foreach ($paths as $key => $file) {
            if (is_file($file)) {
                throw new Exception("Error: [$file] already exists. then exit.\n");
            }
        }
    }

    public function create($template, $paths)
    {
        $this->rollback = TRUE;
        $viewdir = dirname($paths['view/index']);
        $unitdir = dirname($paths['unit/index']);
        $appdir  = dirname($paths['logic']);
        if (! is_dir($appdir)) {
            if (! mkdir($appdir)) {
                throw new Exception("Error: fail to create directory [$appdir]\n");
            }
            echo("Create directory: $appdir\n");
        }
        if (! mkdir($viewdir)) {
            throw new Exception("Error: fail to create directory [$viewdir]\n");
        }
        echo("Create directory: $viewdir\n");
        if (! mkdir($unitdir)) {
            throw new Exception("Error: fail to create directory [$unitdir]\n");
        }
        echo("Create directory: $unitdir\n");
        // create file
        foreach ($paths as $key => $file) {
            if (($fp = fopen($file, 'w')) !== FALSE) {
                flock($fp, LOCK_EX);
                fwrite($fp, $template[$key]);
                flock($fp, LOCK_UN);
                fclose($fp);
                echo("Create file: $file\n");
            } else {
                throw new Exception("Error: fail to create file [$viewdir]\n");
            }
        }
    }

    public function rollback($paths, $force = FALSE)
    {
        echo("Rollback...\n");
        if (! $this->rollback && $force === FALSE) {
            echo("...do nothing, cause no files and directories created...\n");
            return;
        }
        $viewdir = dirname($paths['view/index']);
        $unitdir = dirname($paths['unit/index']);
        foreach ($paths as $file) {
            if (is_file($file)) {
                unlink($file);
                echo("Remove file: $file\n");
            }
        }
        if (is_dir($viewdir)) {
            rmdir($viewdir);
            echo("Remove directory: $viewdir\n");
        }
        if (is_dir($unitdir)) {
            rmdir($unitdir);
            echo("Remove directory: $unitdir\n");
        }
    }

    public function invoke($name, $params = array())
    {
        ob_start();
        invoke($name, $params);
        return ob_get_clean();
    }


    public function template($name, $map, $ext = 'php')
    {
        $dir = dirname(dirname(__FILE__));
        $name = str_replace('/', DS, $name);
        $tmpl = file_get_contents($dir . DS . ZIU_FUNC_PREFIX . 'template' . DS . $name . '.' . $ext);
        foreach ($map as $key => $val) {
            $tmpl = str_replace($key, $val, $tmpl);
        }
        return $tmpl;
    }

    public function skip_column($name)
    {
        return in_array($name, $this->skip_columns);
    }

}

