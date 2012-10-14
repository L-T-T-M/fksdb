<?php

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ModelOrg extends AbstractModelSingle {

    public static function createFromTableRow(NTableRow $row) {
        return new self($row->toArray(), $row->getTable());
    }

}

?>
