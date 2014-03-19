<?php

namespace ORM\Services\Events;

use AbstractServiceSingle;
use DbNames;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceSousParticipant extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_E_SOUS_PARTICIPANT;
    protected $modelClassName = 'ORM\Models\Events\ModelSousParticipant';

}

