<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Constants.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

/**
 * Ziu Common Directory path
 */
// Main path of ziu directory.
define('ZIU_MAIN_PATH',        dirname(dirname(dirname(__FILE__))) . DS);
// Core path in ziu.
define('ZIU_CORE_PATH',        ZIU_MAIN_PATH . 'core' . DS);
// Common path in core.
define('ZIU_CORE_BASE_PATH',   ZIU_CORE_PATH . 'base' . DS);
define('ZIU_CORE_CONF_PATH',   ZIU_CORE_PATH . 'conf' . DS);
define('ZIU_CORE_INIT_PATH',   ZIU_CORE_PATH . 'init' . DS);
define('ZIU_CORE_HELP_PATH',   ZIU_CORE_PATH . 'help' . DS);
define('ZIU_CORE_BIN_PATH',    ZIU_CORE_PATH . 'bin' . DS);
define('ZIU_CORE_LIB_PATH',    ZIU_CORE_PATH . 'lib' . DS);
define('ZIU_CORE_LAYOUT_PATH', ZIU_CORE_PATH . 'layout' . DS);
define('ZIU_CORE_MODEL_PATH',  ZIU_CORE_PATH . 'model' . DS);
define('ZIU_CORE_VENDOR_PATH', ZIU_CORE_PATH . 'vendor' . DS);
// Functional file and directory prefix.
define('ZIU_FUNC_PREFIX',      '_');
// Apps path in ziu.
define('ZIU_APPS_PATH',        ZIU_MAIN_PATH . 'apps' . DS);
// App path named depends on dispatcher like 'default' in ziu.
define('ZIU_APP_PATH',         rtrim(ZIU_APPS_PATH . ZIU_APP_DIRNAME, DS) . DS);
// View path same as app path in ziu.
define('ZIU_VIEW_PATH',        ZIU_APP_PATH);
// Directory name of common place for apps and app
define('ZIU_CONF_DIR',         ZIU_FUNC_PREFIX . 'conf');
define('ZIU_INIT_DIR',         ZIU_FUNC_PREFIX . 'init');
define('ZIU_HELP_DIR',         ZIU_FUNC_PREFIX . 'help');
define('ZIU_BIN_DIR',          ZIU_FUNC_PREFIX . 'bin');
define('ZIU_LIB_DIR',          ZIU_FUNC_PREFIX . 'lib');
define('ZIU_LAYOUT_DIR',       ZIU_FUNC_PREFIX . 'layout');
define('ZIU_MODEL_DIR',        ZIU_FUNC_PREFIX . 'model');
define('ZIU_UNIT_DIR',         ZIU_FUNC_PREFIX . 'unit');
define('ZIU_VENDOR_DIR',       ZIU_FUNC_PREFIX . 'vendor');

/**
 * Erro Level
 */
define('ZIU_LOG_LEVEL_INFO',    4);
define('ZIU_LOG_LEVEL_DEBUG',   3);
define('ZIU_LOG_LEVEL_WARNING', 2);
define('ZIU_LOG_LEVEL_ERROR',   1);

