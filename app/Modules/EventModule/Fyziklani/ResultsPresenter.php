<?php

declare(strict_types=1);

namespace FKSDB\Modules\EventModule\Fyziklani;

use FKSDB\Components\Controls\Fyziklani\ResultsAndStatistics\ResultsAndStatisticsComponent;
use FKSDB\Models\Events\Exceptions\EventNotFoundException;
use FKSDB\Models\UI\PageTitle;

class ResultsPresenter extends BasePresenter
{

    public function titleCorrelationStatistics(): PageTitle
    {
        return new PageTitle(_('Correlation statistics'), 'fas fa-chart-pie');
    }

    public function titleList(): PageTitle
    {
        return new PageTitle(_('Results and statistics'), 'fas fa-chart-area');
    }

    public function titleTable(): PageTitle
    {
        return new PageTitle(_('Detailed results'), 'fas fa-info');
    }

    public function titlePresentation(): PageTitle
    {
        return new PageTitle(_('Results presentation'), 'fas fa-chalkboard');
    }

    public function titleTeamStatistics(): PageTitle
    {
        return new PageTitle(_('Teams statistics'), 'fas fa-chart-line');
    }

    public function titleTaskStatistics(): PageTitle
    {
        return new PageTitle(_('Tasks statistics'), 'fas fa-chart-bar');
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedList(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'list'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedResultsTable(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'table'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedTaskStatistics(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'taskStatistics'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedTeamStatistics(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'teamStatistics'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedCorrelationStatistics(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'correlation'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedPresentation(): void
    {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.results', 'presentation'));
    }

    /**
     * @return ResultsAndStatisticsComponent
     * @throws EventNotFoundException
     */
    protected function createComponentTable(): ResultsAndStatisticsComponent
    {
        return new ResultsAndStatisticsComponent($this->getContext(), $this->getEvent(), 'fyziklani.results.table');
    }

    /**
     * @return ResultsAndStatisticsComponent
     * @throws EventNotFoundException
     */
    protected function createComponentPresentation(): ResultsAndStatisticsComponent
    {
        return new ResultsAndStatisticsComponent(
            $this->getContext(),
            $this->getEvent(),
            'fyziklani.results.presentation'
        );
    }

    /**
     * @return ResultsAndStatisticsComponent
     * @throws EventNotFoundException
     */
    protected function createComponentTeamStatistics(): ResultsAndStatisticsComponent
    {
        return new ResultsAndStatisticsComponent($this->getContext(), $this->getEvent(), 'fyziklani.statistics.team');
    }

    /**
     * @return ResultsAndStatisticsComponent
     * @throws EventNotFoundException
     */
    protected function createComponentTaskStatistics(): ResultsAndStatisticsComponent
    {
        return new ResultsAndStatisticsComponent($this->getContext(), $this->getEvent(), 'fyziklani.statistics.task');
    }

    /**
     * @return ResultsAndStatisticsComponent
     * @throws EventNotFoundException
     */
    protected function createComponentCorrelationStatistics(): ResultsAndStatisticsComponent
    {
        return new ResultsAndStatisticsComponent(
            $this->getContext(),
            $this->getEvent(),
            'fyziklani.statistics.correlation'
        );
    }

    protected function beforeRender(): void
    {
        switch ($this->getAction()) {
            case 'table':
                $this->getPageStyleContainer()->setWidePage();
        }
        parent::beforeRender();
    }

    /**
     * @return string[]
     */
    protected function getNavRoots(): array
    {
        $roots = parent::getNavRoots();
        $roots[] = 'Fyziklani.Results.default';
        return $roots;
    }
}
