<?php

namespace FKSDB\ORM;

use AbstractModelSingle;
use DbNames;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 * @property string email
 */
class ModelPersonInfo extends AbstractModelSingle {

    public function getPerson(): ModelPerson {
        return ModelPerson::createFromTableRow($this->ref(DbNames::TAB_PERSON, 'person_id'));
    }

}

