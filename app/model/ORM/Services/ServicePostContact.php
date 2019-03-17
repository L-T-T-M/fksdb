<?php

namespace FKSDB\ORM\Services;

use FKSDB\ORM\AbstractServiceSingle;
use FKSDB\ORM\DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServicePostContact extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_POST_CONTACT;
    protected $modelClassName = 'FKSDB\ORM\Models\ModelPostContact';

}

