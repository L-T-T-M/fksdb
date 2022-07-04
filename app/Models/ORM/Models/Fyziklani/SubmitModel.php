<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models\Fyziklani;

use FKSDB\Models\Fyziklani\Submit\AlreadyRevokedSubmitException;
use FKSDB\Models\Fyziklani\Submit\ClosedSubmittingException;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\ModelEvent;
use Nette\Database\Table\ActiveRow;
use Nette\Security\Resource;

/**
 * @property-read string state
 * @property-read int fyziklani_team_id
 * @property-read int|null points
 * @property-read bool|null skipped
 * @property-read int fyziklani_task_id
 * @property-read int fyziklani_submit_id
 * @property-read int task_id
 * @property-read ActiveRow fyziklani_team
 * @property-read ActiveRow fyziklani_task
 * @property-read \DateTimeInterface modified
 */
class SubmitModel extends Model implements Resource
{

    public const STATE_NOT_CHECKED = 'not_checked';
    public const STATE_CHECKED = 'checked';

    public const RESOURCE_ID = 'fyziklani.submit';

    public function getFyziklaniTask(): TaskModel
    {
        return TaskModel::createFromActiveRow($this->fyziklani_task);
    }

    public function getEvent(): ModelEvent
    {
        return $this->getFyziklaniTeam()->getEvent();
    }

    public function getFyziklaniTeam(): TeamModel2
    {
        return TeamModel2::createFromActiveRow($this->fyziklani_team);
    }

    public function isChecked(): bool
    {
        return $this->state === self::STATE_CHECKED;
    }

    public function __toArray(): array
    {
        return [
            'points' => $this->points,
            'teamId' => $this->fyziklani_team_id,
            'taskId' => $this->fyziklani_task_id,
            'created' => $this->modified->format('c'),
        ];
    }

    /**
     * @throws AlreadyRevokedSubmitException
     * @throws ClosedSubmittingException
     */
    public function canRevoke(bool $throws = true): bool
    {
        if (is_null($this->points)) {
            if ($throws) {
                throw new AlreadyRevokedSubmitException();
            }
            return false;
        } elseif (!$this->getFyziklaniTeam()->hasOpenSubmitting()) {
            if ($throws) {
                throw new ClosedSubmittingException($this->getFyziklaniTeam());
            }
            return false;
        }
        return true;
    }

    public function getResourceId(): string
    {
        return self::RESOURCE_ID;
    }
}
