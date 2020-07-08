<?php

namespace FKSDB\Modules\EventModule;

use FKSDB\Components\Events\GraphComponent;
use FKSDB\Events\EventNotFoundException;
use FKSDB\UI\PageTitle;

/**
 * Class ModelPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
class ModelPresenter extends BasePresenter {

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function authorizedDefault() {
        $this->setAuthorized($this->isContestsOrgAuthorized('event.model', 'default'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function titleDefault() {
        $this->setPageTitle(new PageTitle(_('Model of event'), 'fa fa-cubes'));
    }

    /**
     * @return GraphComponent
     *
     *
     * @throws EventNotFoundException
     */
    protected function createComponentGraphComponent(): GraphComponent {
        $machine = $this->getEventDispatchFactory()->getEventMachine($this->getEvent());
        return new GraphComponent($this->getContext(), $machine->getPrimaryMachine());
    }
}
