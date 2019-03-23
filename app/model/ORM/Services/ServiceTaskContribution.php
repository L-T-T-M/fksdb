<?php

namespace FKSDB\ORM\Services;

use FKSDB\ORM\AbstractServiceSingle;
use FKSDB\ORM\DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceTaskContribution extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_TASK_CONTRIBUTION;
    protected $modelClassName = 'FKSDB\ORM\Models\ModelTaskContribution';

}
