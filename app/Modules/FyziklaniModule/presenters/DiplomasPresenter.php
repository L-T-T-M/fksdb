<?php

namespace FKSDB\Modules\FyziklaniModule;

use FKSDB\Components\Controls\Fyziklani\FinalResults;
use FKSDB\Events\EventNotFoundException;
use FKSDB\Fyziklani\Ranking\NotClosedTeamException;
use FKSDB\Fyziklani\Ranking\RankingStrategy;
use FKSDB\UI\PageTitle;
use Nette\Application\AbortException;
use Nette\Utils\Html;

/**
 * Class DiplomasPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
class DiplomasPresenter extends BasePresenter {
    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function titleResults() {
        $this->setPageTitle(new PageTitle(_('Final results'), 'fa fa-trophy'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function titleDefault() {
        $this->setPageTitle(new PageTitle(_('Calculate ranking'), 'fa fa-check'));
    }

    /**
     * @throws EventNotFoundException
     */
    public function authorizedResults() {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.diplomas', 'results'));
    }

    /**
     * @throws EventNotFoundException
     */
    public function authorizeDefault() {
        $this->setAuthorized($this->isContestsOrgAuthorized('fyziklani.diplomas', 'calculate'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function renderDefault() {
        $items = [];
        foreach (['A', 'B', 'C'] as $category) {
            $items[$category] = [
                'closed' => $this->getServiceFyziklaniTeam()
                    ->findParticipating($this->getEvent())
                    ->where('category', $category)
                    ->where('points IS NOT NULL')
                    ->count(),
                'opened' => $this->getServiceFyziklaniTeam()
                    ->findParticipating($this->getEvent())
                    ->where('category', $category)
                    ->where('points IS NULL')
                    ->count(),
            ];
        }
        $this->template->items = $items;
    }

    /**
     * @param string|null $category
     * @throws AbortException
     * @throws EventNotFoundException
     * @throws NotClosedTeamException
     */
    public function handleCalculate(string $category = null) {
        $closeStrategy = new RankingStrategy($this->getEvent(), $this->getServiceFyziklaniTeam());
        $log = $closeStrategy($category);
        $this->flashMessage(Html::el()->addHtml(Html::el('h3')->addHtml('Rankin has been saved.'))->addHtml(Html::el('ul')->addHtml($log)), \FKSDB\Modules\Core\BasePresenter::FLASH_SUCCESS);
        $this->redirect('this');
    }

    /**
     * @param string|null $category
     * @return bool
     * @throws EventNotFoundException
     */
    public function isReadyAllToCalculate(string $category = null): bool {
        return $this->getServiceFyziklaniTeam()->isCategoryReadyForClosing($this->getEvent(), $category);
    }

    /**
     * @return FinalResults
     * @throws EventNotFoundException
     */
    protected function createComponentResults(): FinalResults {
        return new FinalResults($this->getContext(), $this->getEvent());
    }
}
