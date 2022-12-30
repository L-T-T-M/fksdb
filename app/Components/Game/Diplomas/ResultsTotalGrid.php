<?php

declare(strict_types=1);

namespace FKSDB\Components\Game\Diplomas;

use FKSDB\Components\Grids\BaseGrid;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\EventModel;
use Nette\DI\Container;

class ResultsTotalGrid extends BaseGrid
{
    private EventModel $event;

    public function __construct(EventModel $event, Container $container)
    {
        parent::__construct($container);
        $this->event = $event;
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(): void
    {
        $this->data = $this->event->getParticipatingTeams()->order('name');
        $this->paginate = false;

        $this->addColumns([
            'fyziklani_team.fyziklani_team_id',
            'fyziklani_team.name',
            'fyziklani_team.rank_total',
        ]);
    }
}
