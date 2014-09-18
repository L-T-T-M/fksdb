<?php

namespace ORM\Services\Events;

use AbstractServiceSingle;
use DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceTsafParticipant extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_E_TSAF_PARTICIPANT;
    protected $modelClassName = 'ORM\Models\Events\ModelTsafParticipant';

}

