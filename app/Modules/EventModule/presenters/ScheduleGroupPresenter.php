<?php

namespace FKSDB\Modules\EventModule;

use FKSDB\Components\Grids\BaseGrid;
use FKSDB\Components\Grids\Schedule\AllPersonsGrid;
use FKSDB\Components\Grids\Schedule\GroupsGrid;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\Modules\Core\PresenterTraits\EventEntityPresenterTrait;
use FKSDB\ORM\Services\Schedule\ServiceScheduleGroup;
use FKSDB\UI\PageTitle;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;
use Nette\Security\IResource;

/**
 * Class ScheduleGroupPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
class ScheduleGroupPresenter extends BasePresenter {
    use EventEntityPresenterTrait;

    /**
     * @var ServiceScheduleGroup
     */
    private $serviceScheduleGroup;

    /**
     * @param ServiceScheduleGroup $serviceScheduleGroup
     * @return void
     */
    public function injectServiceScheduleGroup(ServiceScheduleGroup $serviceScheduleGroup) {
        $this->serviceScheduleGroup = $serviceScheduleGroup;
    }

    /**
     * @return void
     * @throws BadRequestException
     */
    public function titleList() {
        $this->setPageTitle(new PageTitle(_('Schedule'), 'fa fa-calendar-check-o'));
    }

    /**
     * @return void
     * @throws BadRequestException
     */
    public function titlePersons() {
        $this->setPageTitle(new PageTitle(_('Whole program'), 'fa fa-calendar-check-o'));
    }

    /**
     * @return Control
     * @throws NotImplementedException
     */
    protected function createComponentCreateForm(): Control {
        throw new NotImplementedException();
    }

    /**
     * @return Control
     * @throws NotImplementedException
     */
    protected function createComponentEditForm(): Control {
        throw new NotImplementedException();
    }

    /**
     * @return BaseGrid
     * @throws BadRequestException
     * @throws AbortException
     */
    protected function createComponentGrid(): BaseGrid {
        return new GroupsGrid($this->getEvent(), $this->getContext());
    }

    /**
     * @return AllPersonsGrid
     * @throws AbortException
     * @throws BadRequestException
     */
    protected function createComponentAllPersonsGrid(): AllPersonsGrid {
        return new AllPersonsGrid($this->getContext(), $this->getEvent());
    }

    protected function getORMService(): ServiceScheduleGroup {
        return $this->serviceScheduleGroup;
    }

    /**
     * @param IResource|string|null $resource
     * @param string $privilege
     * @return bool
     * @throws BadRequestException
     */
    protected function traitIsAuthorized($resource, string $privilege): bool {
        return $this->isContestsOrgAuthorized($resource, $privilege);
    }
}
