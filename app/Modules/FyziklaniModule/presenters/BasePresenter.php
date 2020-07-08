<?php

namespace FKSDB\Modules\FyziklaniModule;

use FKSDB\Events\EventNotFoundException;
use FKSDB\Modules\EventModule\BasePresenter as EventBasePresenter;
use FKSDB\Components\Controls\Choosers\FyziklaniChooser;
use FKSDB\ORM\Models\ModelEventType;
use FKSDB\ORM\Services\Fyziklani\ServiceFyziklaniSubmit;
use FKSDB\ORM\Services\Fyziklani\ServiceFyziklaniTeam;

/**
 *
 * @author Michal Červeňák
 * @author Lukáš Timko
 */
abstract class BasePresenter extends EventBasePresenter {

    /**
     * @var ServiceFyziklaniTeam
     */
    private $serviceFyziklaniTeam;

    /**
     * @var ServiceFyziklaniSubmit
     */
    private $serviceFyziklaniSubmit;

    /**
     * @param ServiceFyziklaniSubmit $serviceFyziklaniSubmit
     * @return void
     */
    public function injectServiceFyziklaniSubmit(ServiceFyziklaniSubmit $serviceFyziklaniSubmit) {
        $this->serviceFyziklaniSubmit = $serviceFyziklaniSubmit;
    }

    protected function getServiceFyziklaniSubmit(): ServiceFyziklaniSubmit {
        return $this->serviceFyziklaniSubmit;
    }

    /**
     * @param ServiceFyziklaniTeam $serviceFyziklaniTeam
     * @return void
     */
    public function injectServiceFyziklaniTeam(ServiceFyziklaniTeam $serviceFyziklaniTeam) {
        $this->serviceFyziklaniTeam = $serviceFyziklaniTeam;
    }

    protected function getServiceFyziklaniTeam(): ServiceFyziklaniTeam {
        return $this->serviceFyziklaniTeam;
    }

    /**
     * @return FyziklaniChooser
     * @throws EventNotFoundException
     */
    protected function createComponentFyziklaniChooser(): FyziklaniChooser {
        return new FyziklaniChooser($this->getContext(), $this->getEvent());
    }

    /**
     * @return bool
     * @throws EventNotFoundException
     */
    protected function isEnabled(): bool {
        return $this->getEvent()->event_type_id === ModelEventType::FYZIKLANI;
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     * @return string[]
     */
    protected function getNavRoots(): array {
        return ['Fyziklani.Dashboard.default'];
    }
}
