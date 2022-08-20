<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Services\Fyziklani;

use FKSDB\Models\ORM\Models\Fyziklani\TeamCategory;
use FKSDB\Models\ORM\Models\Fyziklani\TeamModel2;
use FKSDB\Models\ORM\Models\EventModel;
use Fykosak\NetteORM\Service;

/**
 * @method TeamModel2 findByPrimary(int $key)
 * @method TeamModel2 storeModel(array $data, ?TeamModel2 $model = null)
 */
class TeamService2 extends Service
{

    public function isReadyForClosing(EventModel $event, ?TeamCategory $category = null): bool
    {
        $query = $event->getParticipatingFyziklaniTeams();
        if ($category) {
            $query->where('category', $category->value);
        }
        $query->where('points', null);
        return $query->count() == 0;
    }

    /**
     * @return TeamModel2[]
     */
    public static function serialiseTeams(EventModel $event): array
    {
        $teams = [];
        /** @var TeamModel2 $team */
        foreach ($event->getPossiblyAttendingFyziklaniTeams() as $team) {
            $teams[] = $team->__toArray();
        }
        return $teams;
    }
}
