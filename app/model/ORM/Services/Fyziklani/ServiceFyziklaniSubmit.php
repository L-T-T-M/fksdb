<?php

namespace FKSDB\ORM\Services\Fyziklani;

use FKSDB\ORM\AbstractServiceSingle;
use FKSDB\ORM\DbNames;
use FKSDB\ORM\DeprecatedLazyDBTrait;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniSubmit;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniTask;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniTeam;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Tables\TypedTableSelection;
use Nette\Database\Context;
use Nette\Database\IConventions;

/**
 * @author Lukáš Timko <lukast@fykos.cz>
 * @method ModelFyziklaniSubmit createNewModel(array $data)
 */
class ServiceFyziklaniSubmit extends AbstractServiceSingle {
    use DeprecatedLazyDBTrait;

    /**
     * ServiceFyziklaniSubmit constructor.
     * @param Context $connection
     * @param IConventions $conventions
     */
    public function __construct(Context $connection, IConventions $conventions) {
        parent::__construct($connection, $conventions, DbNames::TAB_FYZIKLANI_SUBMIT, ModelFyziklaniSubmit::class);
    }

    /**
     * @param ModelFyziklaniTask $task
     * @param ModelFyziklaniTeam $team
     * @return ModelFyziklaniSubmit|null
     */
    public function findByTaskAndTeam(ModelFyziklaniTask $task, ModelFyziklaniTeam $team) {
        /** @var ModelFyziklaniSubmit $row */
        $row = $this->getTable()->where([
            'fyziklani_task_id' => $task->fyziklani_task_id,
            'e_fyziklani_team_id' => $team->e_fyziklani_team_id,
        ])->fetch();
        return $row ?: null;
    }

    public function findAll(ModelEvent $event): TypedTableSelection {
        return $this->getTable()->where('e_fyziklani_team_id.event_id', $event->event_id);
    }

    /**
     * @param ModelEvent $event
     * @param string|null $lastUpdated
     * @return array
     */
    public function getSubmitsAsArray(ModelEvent $event, $lastUpdated = null): array {
        $query = $this->getTable()->where('e_fyziklani_team.event_id', $event->event_id);
        $submits = [];
        if ($lastUpdated) {
            $query->where('modified >= ?', $lastUpdated);
        }
        foreach ($query as $row) {
            $submit = ModelFyziklaniSubmit::createFromActiveRow($row);
            $submits[$submit->fyziklani_submit_id] = $submit->__toArray();
        }
        return $submits;
    }
}
