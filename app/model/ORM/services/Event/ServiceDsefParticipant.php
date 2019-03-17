<?php

namespace FKSDB\ORM\Services\Events;

use AbstractServiceSingle;
use FKSDB\ORM\DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceDsefParticipant extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_E_DSEF_PARTICIPANT;
    protected $modelClassName = 'FKSDB\ORM\Models\Events\ModelDsefParticipant';

}

