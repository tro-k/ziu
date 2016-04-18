<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Config Setting.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

return array(

    // Path
    'main_path'        => ZIU_MAIN_PATH,
    'core_path'        => ZIU_CORE_PATH,
    'base_path'        => ZIU_CORE_BASE_PATH,
    'conf_path'        => ZIU_CORE_CONF_PATH,
    'init_path'        => ZIU_CORE_INIT_PATH,
    'help_path'        => ZIU_CORE_HELP_PATH,
    'bin_path'         => ZIU_CORE_BIN_PATH,
    'lib_path'         => ZIU_CORE_LIB_PATH,
    'layout_path'      => ZIU_CORE_LAYOUT_PATH,
    'model_path'       => ZIU_CORE_MODEL_PATH,
    'vendor_path'      => ZIU_CORE_VENDOR_PATH,
    'apps_path'        => ZIU_APPS_PATH,
    'apps_conf_path'   => ZIU_APPS_PATH . ZIU_CONF_DIR . DS,
    'apps_init_path'   => ZIU_APPS_PATH . ZIU_INIT_DIR . DS,
    'apps_help_path'   => ZIU_APPS_PATH . ZIU_HELP_DIR . DS,
    'apps_bin_path'    => ZIU_APPS_PATH . ZIU_BIN_DIR . DS,
    'apps_lib_path'    => ZIU_APPS_PATH . ZIU_LIB_DIR . DS,
    'apps_layout_path' => ZIU_APPS_PATH . ZIU_LAYOUT_DIR . DS,
    'apps_model_path'  => ZIU_APPS_PATH . ZIU_MODEL_DIR . DS,
    'apps_vendor_path' => ZIU_APPS_PATH . ZIU_VENDOR_DIR . DS,
    'app_path'         => ZIU_APP_PATH,
    'app_conf_path'    => ZIU_APP_PATH . ZIU_CONF_DIR . DS,
    'app_init_path'    => ZIU_APP_PATH . ZIU_INIT_DIR . DS,
    'app_help_path'    => ZIU_APP_PATH . ZIU_HELP_DIR . DS,
    'app_bin_path'     => ZIU_APP_PATH . ZIU_BIN_DIR . DS,
    'app_lib_path'     => ZIU_APP_PATH . ZIU_LIB_DIR . DS,
    'app_layout_path'  => ZIU_APP_PATH . ZIU_LAYOUT_DIR . DS,
    'app_model_path'   => ZIU_APP_PATH . ZIU_MODEL_DIR . DS,
    'app_unit_path'    => ZIU_APP_PATH . ZIU_UNIT_DIR . DS,
    'app_vendor_path'  => ZIU_APP_PATH . ZIU_VENDOR_DIR . DS,
    'view_path'        => ZIU_VIEW_PATH,

    // File ident name
    'suffix_logic' => 'logic',
    'suffix_prep'  => 'prep',
    'suffix_view'  => 'view',
    'suffix_join'  => 'logic,prep,view',

    // Timezone for php 5.1+
    'date_timezone' => 'Asia/Tokyo',

    // Internal encoding
    'internal_encoding' => 'UTF-8',

    // {{{ base/log.php
    // Log directory of path (ex. ./logs/)
    'log_dir_path' => ZIU_MAIN_PATH . 'logs' . DS,
    // }}}

    // {{{ base/view.php
    // View layout for default
    // indicate view name like 'default' which named `default.view.php` in layout dir.
    // loading order is core/layout, apps/.layout, app(default)/.layout.
    // '' indicate disable.
    'view_layout_default'  => 'default',

    // Buffering with callback
    // indicate callable with `string` or array(object, method).
    // '' indicate disable.
    'view_buffer_callback' => '',
    // }}}

);

