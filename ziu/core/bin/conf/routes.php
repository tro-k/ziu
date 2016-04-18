<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Ziu Core Route Setting.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

return array(

    // Module of default. this indication is required.
    '_def_' => 'notfound',

    // Module of http status 404 and 500. this indication is required.
    '_404_' => 'notfound',
    '_500_' => 'notfound',

    // {{{ Example for indication
    //
    // 1. Dispatch uri to other module.
    // 'foo/bar' => 'hoge/fuga',
    //
    // 2. Dispatch uri with a part of any complement
    // 'foo/bar_(:any)_(:any)' => 'hoge/fuga/hoga/$1/$2',
    //
    // 3. Dispatch uri with a part of number complement
    // 'foo/bar/p_(:num)' => 'foo/bar/$1',
    //
    // 4. Dispatch uri with segment's complement
    // 'foo/(:seg)/(:seg)' => 'hoge/fuga/$1/$2',
    //
    // }}}

    '(:seg)(:any)' => '$1/main$2',

);

