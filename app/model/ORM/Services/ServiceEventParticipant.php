<?php

namespace FKSDB\ORM\Services;

use DuplicateApplicationException;
use FKSDB\ORM\AbstractServiceSingle;
use FKSDB\ORM\DbNames;
use FKSDB\ORM\IModel;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Models\ModelEventParticipant;
use FKSDB\Exceptions\ModelException;
use FKSDB\ORM\Tables\TypedTableSelection;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceEventParticipant extends AbstractServiceSingle {

    public function getModelClassName(): string {
        return ModelEventParticipant::class;
    }

    protected function getTableName(): string {
        return DbNames::TAB_EVENT_PARTICIPANT;
    }

    /**
     * @param ModelEventParticipant|IModel $model
     */
    public function save(IModel &$model) {
        try {
            parent::save($model);
        } catch (ModelException $exception) {
            if ($exception->getPrevious() && $exception->getPrevious()->getCode() == 23000) {
                throw new DuplicateApplicationException($model->getPerson(), $exception);
            }
            throw $exception;
        }
    }

    /**
     * @param IModel|ModelEventParticipant $model
     * @param array $data
     * @param bool $alive
     * @return void
     * @deprecated
     */
    public function updateModel(IModel $model, $data, $alive = true) {
        parent::updateModel($model, $data, $alive);
        if (!$alive && !$model->isNew()) {
            $person = $model->getPerson();
            if ($person) {
                $person->removeScheduleForEvent($model->event_id);
            }
        }
    }

    public function findPossiblyAttending(ModelEvent $event): TypedTableSelection {
        return $this->getTable()->where('status', ['participated', 'approved', 'spare', 'applied'])->where('event_id', $event->event_id);
    }
}
