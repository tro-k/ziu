<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Unit_#Table#_Index for #table#.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Unit_#Table#_Index
{

    /**
     * request
     */
    public function request()
    {
        $req = lib('request');
        return array(
            'action'  => $req->get('action', ''),
            '#table_id#' => $req->get('#table_id#', ''),
        );
    }

}

