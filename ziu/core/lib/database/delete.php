<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Database Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Database_Delete extends Database_Where
{

    /**
     * Varivables.
     */
    private $table = '';

    /**
     * Constractor
     * @param string $table : table name
     * @return object this
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Build
     * @return string completed query of delete
     */
    public function build()
    {
        $this->query = 'delete from ' . $this->table;
        $this->query .= $this->_where();
        $this->query .= ' ;';
        return $this->query;
    }

}

