<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Links;

use FKSDB\Models\ORM\Models\EventModel;
use Fykosak\NetteORM\Model;

/**
 * @phpstan-extends LinkFactory<EventModel>
 */
class ParticipantListLink extends LinkFactory
{

    public function getText(): string
    {
        return _('List of applications');
    }

    /**
     * @param EventModel $model
     */
    protected function getDestination(Model $model): string
    {
        if ($model->isTeamEvent()) {
            return ':Event:TeamApplication:list';
        } else {
            return ':Event:Application:list';
        }
    }

    /**
     * @param EventModel $model
     * @phpstan-return array{eventId:int}
     */
    protected function prepareParams(Model $model): array
    {
        return [
            'eventId' => $model->event_id,
        ];
    }
}
