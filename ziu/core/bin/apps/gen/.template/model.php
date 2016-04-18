<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Model for #table#.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Model_#Table# extends Model_Super
{

    // {{{ select
    /**
     * Get list
     * @param integer $limit  : Limit
     * @param integer $offset : Offset
     * @param array   $keys   : Condition
     * @return array
     */
    public function getAll($limit = NULL, $offset = NULL, array $keys = array())
    {
        $sn = $this->select('SQL_CALC_FOUND_ROWS *')->from('#table#');
        #get_list#
        $sn = $sn->order('#table_id# desc');
        if (! is_null($limit) && ! is_null($offset)) {
            $sn = $sn->limit($limit)->offset($offset);
        }
        return $this->fetch($sn)->all();
    }

    /**
     * Found rows for MySQL
     */
    public function foundRows()
    {
        $sn = $this->select('found_rows()');
        return $this->fetch($sn)->one();
    }

    /**
     * Get data
     * @param integer $id : #table_id#
     * @return array
     */
    public function getRow($id)
    {
        $sn = $this->select()->from('#table#')
                ->where(array('#table_id#', $id))
                ; // end of $sn
        return $this->fetch($sn)->row();
    }
    // }}}

    // {{{ operate
    /**
     * Insert data
     * @param array $data : insert data
     * @return boolean
     */
    public function insert(array $data)
    {
        #insert#
        $sn = parent::insert('#table#')->values($data);
        return $this->execute($sn);
    }

    /**
     * Update data
     * @param array   $data : insert data
     * @param integer $id   : #table_id#
     * @return boolean
     */
    public function update(array $data, $id)
    {
        #update#
        $sn = parent::update('#table#')->set($data)->where(array('#table_id#', $id));
        return $this->execute($sn);
    }

    /**
     * Delete data
     * @param integer $id : #table_id#
     * @return boolean
     */
    public function delete($id)
    {
        $sn = parent::delete('#table#')->where(array('#table_id#', $id));
        return $this->execute($sn);
    }
    // }}}

    // {{{ validate
    /**
     * Validate data
     * @param array   $data   : validate data
     * @param array   &$valid : valid data
     * @param string  &$error : error message
     * @param integer $id     : #table_id#
     * @return boolean
     */
    public function validate(array $data, &$valid, &$error, $id = FALSE)
    {
        $vd = lib('validate')->init('#table#');
        #validate#
        if (($res = $vd->execute($data)) === FALSE) {
            $error = $vd->get_errors('[:label] :state');
        } else {
            $valid = $vd->valid();
        }
        return $res;
    }
    // }}}

}

