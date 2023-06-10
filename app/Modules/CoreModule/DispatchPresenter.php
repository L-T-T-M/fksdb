<?php

declare(strict_types=1);

namespace FKSDB\Modules\CoreModule;

use FKSDB\Models\ORM\Models\ContestantModel;
use FKSDB\Models\ORM\Models\ContestModel;
use FKSDB\Models\ORM\Models\LoginModel;
use FKSDB\Models\ORM\Models\PersonModel;
use Fykosak\Utils\UI\Navigation\NavItem;
use Fykosak\Utils\UI\PageTitle;
use Fykosak\Utils\UI\Title;

class DispatchPresenter extends BasePresenter
{

    private array $contestsProperty;

    public function titleDefault(): PageTitle
    {
        return new PageTitle(null, _('Home'), 'fa fa-home');
    }

    final public function renderDefault(): void
    {
        /** @var LoginModel $login */
        $login = $this->getUser()->getIdentity();
        $person = $this->getLoggedPerson();
        $this->template->contestants = $person ? $this->getAllContestants($person) : [];
        $this->template->orgs = $this->getAllOrganisers($login);
        $this->template->contestsProperty = $this->getContestsProperty();
    }
    private function getAllContestants(PersonModel $person): array
    {
        $result = [];
        /** @var ContestantModel $contestant */
        foreach ($person->getContestants() as $contestant) {
            $result[$contestant->contest_id] = $result[$contestant->contest_id] ?? [];
            $acYear = $contestant->getContestYear()->ac_year;
            $result[$contestant->contest_id][] = new NavItem(
                new Title(null, sprintf(_('Contestant in %d year (%d/%d)'), $contestant->year, $acYear, $acYear + 1)),
                ':Public:Dashboard:default',
                [
                    'contestId' => $contestant->contest_id,
                    'year' => $contestant->year,
                ]
            );
        }
        return $result;
    }

    private function getAllOrganisers(LoginModel $login): array
    {
        $results = [];
        foreach ($login->person->getActiveOrgs() as $contestId => $org) {
            $results[$contestId] = new NavItem(
                new Title(null, sprintf(_('Organizer %s'), $org->contest->name)),
                ':Org:Dashboard:default',
                [
                    'contestId' => $contestId,
                ]
            );
        }
        return $results;
    }

    private function getContestsProperty(): array
    {
        if (!isset($this->contestsProperty)) {
            $this->contestsProperty = [];
            $query = $this->contestService->getTable();
            /** @var ContestModel $contest */
            foreach ($query as $contest) {
                $this->contestsProperty[$contest->contest_id] = [
                    'symbol' => $contest->getContestSymbol(),
                    'model' => $contest,
                    'icon' => 'fa fa-' . $contest->getContestSymbol(),
                ];
            }
        }
        return $this->contestsProperty;
    }

    protected function beforeRender(): void
    {
        $this->getPageStyleContainer()->setNavBarClassName('bg-dark navbar-dark');
        $this->getPageStyleContainer()->setNavBrandPath('/images/logo/white.svg');
        parent::beforeRender();
    }
}
