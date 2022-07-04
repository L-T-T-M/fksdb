<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\Fyziklani;

use FKSDB\Models\ORM\Models\Fyziklani\TeamCategory;
use Fykosak\Utils\BaseComponent\BaseComponent;
use FKSDB\Components\Grids\Fyziklani\ResultsCategoryGrid;
use FKSDB\Components\Grids\Fyziklani\ResultsTotalGrid;
use FKSDB\Models\ORM\Models\ModelEvent;
use Nette\DI\Container;

class FinalResultsComponent extends BaseComponent
{

    private ModelEvent $event;

    public function __construct(Container $container, ModelEvent $event)
    {
        parent::__construct($container);
        $this->event = $event;
    }

    public function isClosedCategory(TeamCategory $category): bool
    {
        $count = $this->event->getParticipatingFyziklaniTeams()
            ->where('category', $category->value)
            ->where('rank_category IS NULL')
            ->count();
        return $count === 0;
    }

    public function isClosedTotal(): bool
    {
        $count = $this->event->getParticipatingFyziklaniTeams()
            ->where('rank_total IS NULL')
            ->count();
        return $count === 0;
    }

    protected function createComponentResultsCategoryAGrid(): ResultsCategoryGrid
    {
        return new ResultsCategoryGrid($this->event, TeamCategory::tryFrom('A'), $this->getContext());
    }

    protected function createComponentResultsCategoryBGrid(): ResultsCategoryGrid
    {
        return new ResultsCategoryGrid($this->event, TeamCategory::tryFrom('B'), $this->getContext());
    }

    protected function createComponentResultsCategoryCGrid(): ResultsCategoryGrid
    {
        return new ResultsCategoryGrid($this->event, TeamCategory::tryFrom('C'), $this->getContext());
    }

    protected function createComponentResultsTotalGrid(): ResultsTotalGrid
    {
        return new ResultsTotalGrid($this->event, $this->getContext());
    }

    final public function render(): void
    {
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'layout.finalResults.latte');
    }
}
