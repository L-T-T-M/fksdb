<?php

declare(strict_types=1);

namespace FKSDB\Components\Game;

use FKSDB\Components\Grids\FilterBaseGrid;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\EventModel;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;

class TaskGrid extends FilterBaseGrid
{
    private EventModel $event;

    public function __construct(EventModel $event, Container $container)
    {
        parent::__construct($container);
        $this->event = $event;
    }

    protected function setData(): void
    {
        $this->data = $this->event->getTasks();
    }

    protected function getFilterCallback(): void
    {
        $tokens = preg_split('/\s+/', $this->searchTerm['term']);
        foreach ($tokens as $token) {
            $this->data->where(
                'name LIKE CONCAT(\'%\', ? , \'%\') OR fyziklani_task_id LIKE CONCAT(\'%\', ? , \'%\')',
                $token,
                $token
            );
        }
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(Presenter $presenter): void
    {
        parent::configure($presenter);
        $this->addColumns(['fyziklani_task.fyziklani_task_id', 'fyziklani_task.label', 'fyziklani_task.name']);
    }
}
