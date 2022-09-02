<?php

declare(strict_types=1);

namespace FKSDB\Modules\OrgModule;

use FKSDB\Components\Controls\Inbox\PointPreview\PointsPreviewComponent;
use FKSDB\Components\Controls\Inbox\PointsForm\PointsFormComponent;
use FKSDB\Models\ORM\DbNames;
use FKSDB\Models\ORM\Models\{ContestModel, LoginModel, TaskContributionType, TaskModel};
use FKSDB\Models\ORM\Services\TaskContributionService;
use FKSDB\Models\Results\SQLResultsCache;
use FKSDB\Models\Submits\SeriesTable;
use Fykosak\Utils\Logging\Message;
use Fykosak\Utils\UI\PageTitle;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;
use Tracy\Debugger;

class PointsPresenter extends BasePresenter
{

    /**
     * Show all tasks?
     * @persistent
     */
    public ?bool $all = null;
    private SQLResultsCache $resultsCache;
    private SeriesTable $seriesTable;
    private TaskContributionService $taskContributionService;

    final public function injectQuarterly(
        SQLResultsCache $resultsCache,
        SeriesTable $seriesTable,
        TaskContributionService $taskContributionService
    ): void {
        $this->resultsCache = $resultsCache;
        $this->seriesTable = $seriesTable;
        $this->taskContributionService = $taskContributionService;
    }

    public function titleEntry(): PageTitle
    {
        return new PageTitle(null, sprintf(_('Grade series %d'), $this->getSelectedSeries()), 'fas fa-pen');
    }

    public function titlePreview(): PageTitle
    {
        return new PageTitle(null, _('Points list'), 'fas fa-clipboard-list');
    }

    public function authorizedEntry(): void
    {
        $this->setAuthorized($this->contestAuthorizator->isAllowed('points', 'entry', $this->getSelectedContest()));
    }

    public function authorizedPreview(): void
    {
        $this->setAuthorized($this->contestAuthorizator->isAllowed('points', 'detail', $this->getSelectedContest()));
    }

    public function actionEntry(): void
    {
        $this->seriesTable->taskFilter = $this->all ? null : $this->getGradedTasks();
    }

    private function getGradedTasks(): array
    {
        /**@var LoginModel $login */
        $login = $this->getUser()->getIdentity();
        $person = $login->person;
        if (!$person) {
            return [];
        }
        $gradedTasks = $this->taskContributionService->getTable()
            ->where(
                [
                    'person_id' => $person->person_id,
                    'task_id' => (clone $this->seriesTable->getTasks())->select('task_id'),
                    'type' => TaskContributionType::GRADE,
                ]
            )->fetchPairs('task_id', 'task_id');
        return array_values($gradedTasks);
    }

    final public function renderEntry(): void
    {
        $this->getTemplate()->showAll = (bool)$this->all;
        if ($this->getSelectedContest()->contest_id === ContestModel::ID_VYFUK && $this->getSelectedSeries() > 6) {
            $this->getTemplate()->hasQuizTask = true;
        } else {
            $this->getTemplate()->hasQuizTask = false;
        }
    }

    public function handleInvalidate(): void
    {
        try {
            $this->resultsCache->invalidate($this->getSelectedContestYear());
            $this->flashMessage(_('Points invalidated.'), Message::LVL_INFO);
        } catch (\Throwable $exception) {
            $this->flashMessage(_('Error during invalidation.'), Message::LVL_ERROR);
            Debugger::log($exception);
        }

        $this->redirect('this');
    }

    /**
     * @throws BadRequestException
     */
    public function handleRecalculateAll(): void
    {
        try {
            $years = $this->getSelectedContestYear()->contest->related(DbNames::TAB_TASK)
                ->select('year')
                ->group('year');
            /** @var TaskModel $year */
            foreach ($years as $year) {
                // TODO WTF -1 year
                $contestYear = $this->getSelectedContest()->getContestYear($year->year);
                if ($contestYear) {
                    $this->resultsCache->recalculate($contestYear);
                }
            }

            $this->flashMessage(_('Points recounted.'), Message::LVL_INFO);
        } catch (InvalidArgumentException $exception) {
            $this->flashMessage(_('Error while recounting.'), Message::LVL_ERROR);
            Debugger::log($exception);
        }

        $this->redirect('this');
    }

    public function handleCalculateQuizPoints(): void
    {
        try {
            $this->resultsCache->calculateQuizPoints($this->getSelectedContestYear(), $this->getSelectedSeries());
            $this->flashMessage(_('Calculate quiz points.'), Message::LVL_INFO);
        } catch (\Throwable $exception) {
            $this->flashMessage(_('Error during calculation.'), Message::LVL_ERROR);
            Debugger::log($exception);
        }
    }

    protected function startup(): void
    {
        parent::startup();
        $this->seriesTable->contestYear = $this->getSelectedContestYear();
        $this->seriesTable->series = $this->getSelectedSeries();
    }

    protected function createComponentPointsForm(): PointsFormComponent
    {
        return new PointsFormComponent(
            fn() => $this->resultsCache->recalculate($this->getSelectedContestYear()),
            $this->getContext(),
            $this->seriesTable,
        );
    }

    protected function createComponentPointsTableControl(): PointsPreviewComponent
    {
        return new PointsPreviewComponent($this->getContext(), $this->seriesTable);
    }

    protected function beforeRender(): void
    {
        $this->getPageStyleContainer()->setWidePage();
        parent::beforeRender();
    }
}
