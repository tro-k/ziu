<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Unit_#Table#_Default for #table#.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Unit_#Table#_Default
{

    /**
     * request
     */
    public function request()
    {
        $r = lib('request');
        #default#
        $req['action'] = $r->post('action', '');
        $req['refer']  = $r->post('refer', '');
        return $req;
    }

}

