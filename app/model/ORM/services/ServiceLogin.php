<?php

namespace FKSDB\ORM\Services;

use AbstractServiceSingle;
use FKSDB\ORM\DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceLogin extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_LOGIN;
    protected $modelClassName = 'FKSDB\ORM\Models\ModelLogin';
}
