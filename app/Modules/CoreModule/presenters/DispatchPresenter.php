<?php

namespace FKSDB\Modules\CoreModule;

use FKSDB\Modules\Core\AuthenticatedPresenter;
use FKSDB\ORM\Models\ModelContest;
use FKSDB\ORM\Models\ModelLogin;
use FKSDB\ORM\Models\ModelRole;
use FKSDB\UI\PageTitle;
use Nette\Application\UI\InvalidLinkException;

/**
 * Class DispatchPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
class DispatchPresenter extends AuthenticatedPresenter {

    /**
     * @throws InvalidLinkException
     */
    public function renderDefault() {
        /**
         * @var ModelLogin $login
         */
        $login = $this->getUser()->getIdentity();
        $query = $this->getServiceContest()->getTable();
        $result = [];
        /** @var ModelContest $contest */
        foreach ($query as $contest) {
            $symbol = $contest->getContestSymbol();
            $allowed = [];
            foreach ([ModelRole::ORG, ModelRole::CONTESTANT] as $role) {
                $allowed[$role] = $this->check($login, $contest, $role);
            }
            $result[$symbol] = ['data' => $allowed, 'contest' => $contest];
        }
        $this->template->contests = $result;
    }

    /**
     * @param ModelLogin $login
     * @param ModelContest $contest
     * @param string $role
     * @return array
     * @throws InvalidLinkException
     */
    private function check(ModelLogin $login, ModelContest $contest, string $role) {
        switch ($role) {
            case ModelRole::ORG:
                foreach ($login->getActiveOrgs($this->getYearCalculator()) as $contestId => $org) {
                    if ($contest->contest_id == $contestId) {
                        return [
                            'link' => $this->link(':Org:Dashboard:default', [
                                'contestId' => $contest->contest_id,
                            ]),
                            'active' => true,
                            'label' => $this->getLabel($contest, $role),
                        ];
                    }
                }
                return [
                    'link' => null,
                    'active' => false,
                    'label' => $this->getLabel($contest, $role),
                ];
            default:
            case ModelRole::CONTESTANT:
                $person = $login->getPerson();
                if ($person) {
                    foreach ($person->getActiveContestants($this->getYearCalculator()) as $contestId => $org) {
                        if ($contest->contest_id == $contestId) {
                            return [
                                'link' => $this->link(':Public:Dashboard:default', [
                                    'contestId' => $contestId,
                                ]),
                                'active' => true,
                                'label' => $this->getLabel($contest, $role),
                            ];
                        }
                    }
                }
                return [
                    'link' => $this->link(':Public:Register:year', [
                        'contestId' => $contest->contest_id,
                    ]),
                    'active' => true,
                    'label' => $this->getLabel($contest, 'register'),
                ];
        }
    }

    private function getLabel(ModelContest $contest, string $role): string {
        return $contest->name . ' - ' . _($role);
    }

    public function titleDefault() {
        $this->setPageTitle(new PageTitle(_('Rozcestník'), 'fa fa-home'));
    }

    protected function beforeRender() {
        $this->getPageStyleContainer()->navBarClassName = 'bg-dark navbar-dark';
        parent::beforeRender();
    }
}
