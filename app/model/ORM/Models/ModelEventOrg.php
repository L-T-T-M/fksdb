<?php

namespace FKSDB\ORM\Models;

use FKSDB\ORM\AbstractModelSingle;
use Nette\Database\Table\ActiveRow;
use Nette\InvalidStateException;
use Nette\Security\IResource;

/**
 * Class FKSDB\ORM\Models\ModelEventOrg
 * @property-read ActiveRow person
 * @property-read ActiveRow event
 * @property-read string note
 * @property-read int e_org_id
 */
class ModelEventOrg extends AbstractModelSingle implements IResource, IEventReferencedModel, IContestReferencedModel, IPersonReferencedModel {
    public const RESOURCE_ID = 'eventOrg';

    public function getPerson(): ModelPerson {
        return ModelPerson::createFromActiveRow($this->person);
    }

    public function getEvent(): ModelEvent {
        return ModelEvent::createFromActiveRow($this->event);
    }

    public function getContest(): ModelContest {
        return $this->getEvent()->getContest();
    }

    public function getResourceId(): string {
        return self::RESOURCE_ID;
    }

    /**
     * @return string
     * @throws InvalidStateException
     */
    public function __toString(): string {
        if (!$this->getPerson()) {
            throw new InvalidStateException("Missing person in application ID '" . $this->getPrimary(false) . "'.");
        }
        return $this->getPerson()->__toString();
    }
}
