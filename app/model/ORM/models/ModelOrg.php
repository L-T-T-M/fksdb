<?php

namespace FKSDB\ORM;

use AbstractModelSingle;
use ModelContest;
use Nette\Database\Table\ActiveRow;
use Nette\Security\IResource;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 * @property ActiveRow contest
 * @property ActiveRow person
 */
class ModelOrg extends AbstractModelSingle implements IResource {

    /**
     * @return ModelContest
     */
    public function getContest() {
        $data = $this->contest;
        return ModelContest::createFromTableRow($data);
    }

    /**
     * @return ModelPerson
     */
    public function getPerson() {
        $data = $this->person;
        return ModelPerson::createFromTableRow($data);
    }

    public function getResourceId() {
        return 'org';
    }

}
