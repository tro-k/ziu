<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Upload Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

class Upload
{

    // {{{ const
    /**
     * Constant
     */
    // Standard error code
    const UPLOAD_ERR_OK         = UPLOAD_ERR_OK;
    const UPLOAD_ERR_INI_SIZE   = UPLOAD_ERR_INI_SIZE;
    const UPLOAD_ERR_FORM_SIZE  = UPLOAD_ERR_FORM_SIZE;
    const UPLOAD_ERR_PARTIAL    = UPLOAD_ERR_PARTIAL;
    const UPLOAD_ERR_NO_FILE    = UPLOAD_ERR_NO_FILE;
    const UPLOAD_ERR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;
    const UPLOAD_ERR_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;
    const UPLOAD_ERR_EXTENSION  = UPLOAD_ERR_EXTENSION;
    // Extend error code
    const UPLOAD_ERR_VALID_SIZE = 101;
    const UPLOAD_ERR_VALID_NAME = 102;
    const UPLOAD_ERR_VALID_EXT  = 103;
    const UPLOAD_ERR_VALID_TYPE = 104;
    const UPLOAD_ERR_VALID_MIME = 105;
    const UPLOAD_ERR_MOVE_FILE  = 201;
    const UPLOAD_ERR_DUPLICATE  = 202;
    const UPLOAD_ERR_MKDIR      = 203;
    // }}}

    // {{{ variable
    /**
     * Variables
     */
    private $config = array(
                // save
                'save_path'   => '',
                'save_subdir' => '',
                'save_tmpdir' => '',
                'save_tmpdir_prefix' => '_tmp_',
                'auto_clean_tmpdir'  => TRUE, // boolean
                'clean_limit_time'   => 3600, // second
                'save_overwrite' => TRUE, // boolean
                'path_chmod'     => 0777, // octet
                'file_chmod'     => 0666, // octet
                // save filename
                // :org  original value of $_FILES[$key]['name'][$id]
                // :key  $key indicated $_FILES[$key]
                // :id   $id indicated $_FILES[$key][...][$id]
                // :ext  extension of filename
                // :pid  real process id at execution php in linux
                // :date date('Ymd')
                // :time date('His')
                // :md5  md5_file($_FILES[$key]['tmp_name'][$id])
                // e.g. 'save_filename' => 'sample_:key_:id.:ext',
                // e.g. 'save_filename' => ':md5.:ext',
                'save_filename' => '',
                // validate
                'required'    => FALSE,
                'max_size'    => 0,
                'max_name'    => 0,
                'accept_ext'  => '',
                'except_ext'  => '',
                'accept_type' => '',
                'except_type' => '',
                'accept_mime' => '',
                'except_mime' => '',
                // message
                'error_message' => array(),
            );
    private $key   = NULL;
    private $label = NULL;
    private $files = array();
    private $error = array();
    private $message = array();
    // }}}

    // {{{ constructor
    /**
     * Constructor
     */
    public function __construct($config = NULL)
    {
        $this->config['error_message'] = array(
            static::UPLOAD_ERR_OK         => '',
            static::UPLOAD_ERR_INI_SIZE   => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_FORM_SIZE  => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_PARTIAL    => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_NO_FILE    => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_NO_TMP_DIR => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_CANT_WRITE => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_EXTENSION  => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_VALID_SIZE => 'ファイルサイズが超過しています。',
            static::UPLOAD_ERR_VALID_NAME => 'ファイル名が長すぎます。',
            static::UPLOAD_ERR_VALID_EXT  => '不正な拡張子です。',
            static::UPLOAD_ERR_VALID_TYPE => '不正なファイルタイプです。',
            static::UPLOAD_ERR_VALID_MIME => '不正なMIMEタイプです。',
            static::UPLOAD_ERR_MOVE_FILE  => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_DUPLICATE  => 'アップロードに失敗しました。',
            static::UPLOAD_ERR_MKDIR      => 'アップロードに失敗しました。',
        );
        $this->config($config);
    }
    // }}}

    // {{{ config
    /**
     * Config
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->config['save_path'] = str_replace('/', DS, $this->config['save_path']);
        $this->config['save_subdir'] = str_replace('/', DS, $this->config['save_subdir']);
        $this->config['save_tmpdir'] = str_replace('/', DS, $this->config['save_tmpdir']);
        return $this;
    }
    // }}}

    // {{{ files (set files)
    /**
     * Files
     * @param string $name  : $_FILES[name]
     * @param string $label : form label name
     * @return object $this
     */
    public function files($name, $label)
    {
        if (! isset($_FILES[$name])) {
            return $this;
        }
        $files = $_FILES[$name];
        if (is_array($files['name'])) {
            $flist = array();
            foreach ($files['name'] as $id => $val) {
                $file = array();
                $file['name']      = $files['name'][$id];
                $file['type']      = $files['type'][$id];
                $file['tmp_name']  = $files['tmp_name'][$id];
                $file['error']     = $files['error'][$id];
                $file['size']      = $files['size'][$id];
                if (empty($file['tmp_name'])) {
                    $file['extension'] = '';
                    $file['mime']      = '';
                } else {
                    $file['extension'] = $this->_get_ext_of($files['name'][$id]);
                    $file['mime']      = $this->_mime_type($file);
                }
                $flist[$id] = $file;
            }
            $this->files = $flist;
        } else {
            if (empty($files['tmp_name'])) {
                $files['extension'] = '';
                $files['mime']      = '';
            } else {
                $files['extension'] = $this->_get_ext_of($files['name']);
                $files['mime']      = $this->_mime_type($files);
            }
            $this->files = array($files);
        }
        $this->key   = $name;
        $this->label = $label;
        if (! empty($this->config['save_tmpdir_prefix']) && $this->config['auto_clean_tmpdir']) {
            // clean tmpdir automatically
            $this->clean_subdir_of($this->config['save_tmpdir_prefix'] . '*');
        }
        return $this;
    }
    // }}}

    // {{{ validate
    /**
     * Validate
     * @param array $config : config
     * @return boolean
     */
    public function validate(array $config = array())
    {
        $config = array_merge($this->config, $config);
        foreach ($this->files as $id => $file) {
            if ($config['required'] === FALSE && $file['error'] == UPLOAD_ERR_NO_FILE) {
                // skip validate, if required is FALSE and UPLOAD_ERR_NO_FILE
                continue;
            }
            if ($file['error'] > 0) {
                // php standard error
                $this->error[$id] = $file['error'];
            } else {
                // check size
                if ($config['max_size'] > 0) {
                    if (filesize($file['tmp_name']) > $config['max_size']) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_SIZE;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                // check filename length
                if ($config['max_name'] > 0) {
                    if (strlen($file['name']) > $config['max_name']) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_NAME;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                // check ext
                if (! empty($config['accept_ext'])) {
                    if (! in_array($file['extension'], explode(',', $config['accept_ext']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_EXT;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                if (! empty($config['except_ext'])) {
                    if (in_array($file['extension'], explode(',', $config['except_ext']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_EXT;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                // check type (e.g. image, application, ...)
                preg_match('|^(.*)/(.*)|', $file['mime'], $info);
                if (! empty($config['accept_type'])) {
                    if (! in_array($info[1], explode(',', $config['accept_type']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_TYPE;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                if (! empty($config['except_type'])) {
                    if (in_array($info[1], explode(',', $config['except_type']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_TYPE;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                // check mime (e.g. image/jpeg, application/octet-stream, ...)
                if (! empty($config['accept_mime'])) {
                    if (! in_array($file['mime'], explode(',', $config['accept_mime']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_MIME;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
                if (! empty($config['except_mime'])) {
                    if (in_array($file['mime'], explode(',', $config['except_mime']))) {
                        $this->error[$id] = static::UPLOAD_ERR_VALID_MIME;
                        $this->files[$id]['error'] = $this->error[$id];
                    }
                }
            }
        }
        if (empty($this->error)) {
            return TRUE;
        } else {
            $this->message = array();
            foreach ($this->error as $id => $code) {
                $this->message[$id] = $this->config['error_message'][$code];
            }
            return FALSE;
        }
    }
    // }}}

    // {{{ save
    /**
     * Save
     * @return void
     */
    public function save()
    {
        $files = array();
        // check error
        foreach ($this->files as $id => $file) {
            if ($file['error'] > 0) {
                continue;
            }
            $files[$id] = $file;
        }
        if (empty($files)) {
            //throw new Exception('No uploaded files are selected.');
            return;
        }
        // save dir path
        $path = rtrim($this->config['save_path'], DS);
        if ($this->config['save_tmpdir'] != '') {
            $path .= DS . rtrim($this->config['save_tmpdir'], DS);
        } elseif ($this->config['save_subdir'] != '') {
            $path .= DS . rtrim($this->config['save_subdir'], DS);
        }
        if (! is_dir($path)) {
            $oldumask = umask(0);
            @mkdir($path, $this->config['path_chmod'], TRUE);
            umask($oldumask);
        }
        if (! is_writable($path)) {
            throw new Exception('Can\'t move the uploaded file. Destination path specified does not exist.');
        }
        // save file
        $oldumask = umask(0);
        foreach ($files as $id => $file) {
            $filename = $this->_file_name($id, $file);
            $filepath = $path . DS . $filename;
            if ((bool)$this->config['save_overwrite'] === FALSE && is_file($filepath)) {
                // duplicate error.
                $this->error[$id] = static::UPLOAD_ERR_DUPLICATE;
                $this->files[$id]['error'] = $this->error[$id];
            } elseif (! @move_uploaded_file($file['tmp_name'], $filepath)) {
                // move error.
                $this->error[$id] = static::UPLOAD_ERR_MOVE_FILE;
                $this->files[$id]['error'] = $this->error[$id];
            } else {
                @chmod($filepath, $this->config['file_chmod']);
                $this->files[$id]['save_filename'] = $filename;
                $this->files[$id]['save_filepath'] = $filepath;
            }
        }
        umask($oldumask);
        // error message
        if (! empty($this->error)) {
            $this->message = array();
            foreach ($this->error as $id => $code) {
                $this->message[$id] = $this->config['error_message'][$code];
            }
            throw new Exception('Can\'t move the uploaded file with some errors.');
        }
    }
    // }}}

    // {{{ file info
    /**
     * Get file info
     * @param integer $id : index
     * @return array
     */
    public function get_file($id = NULL)
    {
        if (is_null($id)) {
            return $this->files;
        } elseif (isset($this->files[$id])) {
            return $this->files[$id];
        }
    }
    // }}}

    // {{{ subdir
    /**
     * Move subdir to
     * @param string  $subdir : subdir
     * @param boolean $remove : flag of removing after moveing
     * @return void
     */
    public function move_subdir_to($subdir, $remove = TRUE)
    {
        if (! empty($this->config['save_subdir'])) {
            $this->_move_dir($this->config['save_subdir'], $subdir, $remove);
        }
    }

    /**
     * Move tmpdir to
     * @param string  $subdir : subdir
     * @param boolean $remove : flag of removing after moveing
     * @return void
     */
    public function move_tmpdir_to($subdir, $remove = TRUE)
    {
        if (! empty($this->config['save_tmpdir'])) {
            $this->_move_dir($this->config['save_tmpdir'], $subdir, $remove);
        }
    }

    /**
     * Move dir
     * @param string  $fromdir : from subdir
     * @param string  $todir   : to subdir
     * @param boolean $remove  : flag of removing after moveing
     * @return void
     */
    protected function _move_dir($fromdir, $todir, $remove = TRUE)
    {
        $from = $this->config['save_path'] . DS . str_replace('/', DS, $fromdir);
        $to   = $this->config['save_path'] . DS . str_replace('/', DS, $todir);
        if (is_dir($from)) {
            if (is_dir($to)) {
                foreach (glob($from . DS . '*') as $file) {
                    rename($file, $to . DS . basename($file));
                }
            } else {
                rename($from, $to);
            }
        }
        if ($remove === TRUE && is_dir($from)) {
            $this->remove_for($from);
        }
    }

    /**
     * Remove subdir
     * @return void
     */
    public function remove_subdir()
    {
        $path = $this->config['save_path'] . DS . $this->config['save_subdir'];
        $this->remove_for($path);
    }

    /**
     * Clean subdir
     * @param string $subdir : subdir name with wild-card
     * @return void
     */
    public function clean_subdir_of($subdir, $time = NULL)
    {
        if (is_null($time)) {
            // default in method is 86400
            $time = empty($this->config['clean_limit_time'])
                         ? 86400 : (int)$this->config['clean_limit_time'];
        }
        $limit = time() - $time;
        foreach (glob($this->config['save_path'] . DS . $subdir, GLOB_ONLYDIR) as $dir) {
            if ($limit > filectime($dir)) {
                $this->remove_for($dir);
            }
        }
    }

    /**
     * Remove for
     * @param string $path   : directory path
     * @param string $prefix : target of prefix
     * @return void
     */
    public function remove_for($path, $prefix = '')
    {
        foreach (glob($path . DS . $prefix . '*') as $file) {
            $file = str_replace('/', DS, $file);
            if (is_dir($file)) {
                $this->remove_for($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }

    /**
     * Use save tmpdir
     * @param string $tmpdir : save_tmpdir
     * @return string
     */
    public function use_save_tmpdir($tmpdir = NULL)
    {
        if (is_null($tmpdir)) {
            $tmpdir = $this->config['save_tmpdir_prefix'] . md5(microtime());
        }
        $this->config['save_tmpdir'] = str_replace('/', DS, $tmpdir);
        return $tmpdir;
    }
    // }}}

    // {{{ error message
    /**
     * Get error message
     * @param integer $id : index
     * @return string
     */
    public function get_message($id = NULL)
    {
        if (is_null($id)) {
            return $this->message;
        } elseif (isset($this->message[$id])) {
            return $this->message[$id];
        }
    }

    /**
     * Get error message
     * @param integer $id     : file id
     * @param string  $format : format of message
     * @param string  $pre    : prefix quoute string
     * @param string  $suf    : suffix quoute string
     * @return string error message
     */
    public function get_error($id, $format, $pre = '', $suf = "\n")
    {
        return $pre . $this->_format_message($format, $id) . $suf;
    }

    /**
     * Get all error messages
     * @param string  $format : format of message
     * @param string  $pre    : prefix quoute string
     * @param string  $suf    : suffix quoute string
     * @return string error message
     */
    public function get_errors($format, $pre = '', $suf = "\n")
    {
        $msg = '';
        foreach ($this->get_message() as $id => $state) {
            $msg .= $pre . $this->_format_message($format, $id) . $suf;
        }
        return trim($msg);
    }

    /**
     * Format message
     * @param string  $format : format
     * @param integer $id     : index
     * @return string
     */
    protected function _format_message($format, $id)
    {
        $keys = array(':label', ':id', ':state', ':code');
        $repl = array($this->label, $id, $this->get_message($id), $this->files[$id]['error']);
        return str_replace($keys, $repl, $format);
    }
    // }}}

    // {{{ attributes
    /**
     * Get file name
     * @param integer $id   : index
     * @param array   $file : file info
     * @return string
     */
    protected function _file_name($id, $file)
    {
        if (! empty($this->config['save_filename'])) {
            $keys = array(':org', ':key', ':id', ':ext', ':pid', ':date', ':time', ':md5');
            $repl = array(
                $file['name'],
                $this->key,
                $id,
                $file['extension'],
                getmypid(),
                date('Ymd'),
                date('His'),
                md5_file($file['tmp_name']),
            );
            return str_replace($keys, $repl, $this->config['save_filename']);
        } elseif (! empty($file['extension'])) {
            $pos = strlen($file['name']) - (strlen($file['extension']) + 1);
            return substr($file['name'], 0, $pos) . '.' . $file['extension'];
        } else {
            return $file['name'];
        }
    }

    /**
     * Get extension
     * @param string $name : filename
     * @return string
     */
    protected function _get_ext_of($name)
    {
        return strtolower(ltrim(strrchr(ltrim($name, '.'), '.'),'.'));
    }

    /**
     * Get mime type
     * @param  array $file : single of $_FILES
     * @return string mime-type
     */
    protected function _mime_type($file)
    {
        // Use if the Fileinfo extension, if available (>= 5.3 supported).
        if ((float)substr(phpversion(), 0, 3) >= 5.3 && function_exists('finfo_file')) {
            if (($finfo = new finfo(FILEINFO_MIME_TYPE)) !== FALSE) {
                $file_type = $finfo->file($file['tmp_name']);
                if (strlen($file_type) > 1) {
                    return $file_type;
                }
            }
        }
        // Fall back to the deprecated mime_content_type(), if available.
        if (function_exists('mime_content_type')) {
            return @mime_content_type($file['tmp_name']);
        }
        // Use linux file command, if available.
        if (DS !== '\\' && function_exists('exec')) {
            $output = array();
            @exec('file --brief --mime-type ' . escapeshellarg($file['tmp_path'])
                    , $output, $return_code);
            if ($return_code === 0 && strlen($output[0]) > 0) {
                return rtrim($output[0]);
            }
        }
        // At last, use file['type'].
        return $file['type'];
    }
    // }}}

}

