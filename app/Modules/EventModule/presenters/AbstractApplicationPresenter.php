<?php

namespace FKSDB\Modules\EventModule;

use FKSDB\Components\Events\TransitionButtonsComponent;
use FKSDB\Config\NeonSchemaException;
use FKSDB\Entity\ModelNotFoundException;
use FKSDB\Events\EventNotFoundException;
use FKSDB\Events\Model\ApplicationHandlerFactory;
use FKSDB\Events\Model\Grid\SingleEventSource;
use FKSDB\Components\Events\ApplicationComponent;
use FKSDB\Components\Events\MassTransitionsControl;
use FKSDB\Components\Grids\Application\AbstractApplicationsGrid;
use FKSDB\Components\Grids\Schedule\PersonGrid;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\Logging\MemoryLogger;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\Modules\Core\PresenterTraits\EventEntityPresenterTrait;
use FKSDB\ORM\Services\ServiceEventParticipant;
use FKSDB\UI\PageTitle;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Control;
use Nette\InvalidStateException;
use Nette\Security\IResource;

/**
 * Class AbstractApplicationPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
abstract class AbstractApplicationPresenter extends BasePresenter {
    use EventEntityPresenterTrait;

    protected ApplicationHandlerFactory $applicationHandlerFactory;

    protected ServiceEventParticipant $serviceEventParticipant;

    public function injectHandlerFactory(ApplicationHandlerFactory $applicationHandlerFactory): void {
        $this->applicationHandlerFactory = $applicationHandlerFactory;
    }

    public function injectServiceEventParticipant(ServiceEventParticipant $serviceEventParticipant): void {
        $this->serviceEventParticipant = $serviceEventParticipant;
    }

    /**
     * @throws EventNotFoundException
     */
    final public function titleList(): void {
        $this->setPageTitle(new PageTitle(_('List of applications'), 'fa fa-users'));
    }

    /**
     * @return void
     * @throws BadTypeException
     * @throws EventNotFoundException
     * @throws ForbiddenRequestException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    final public function titleDetail(): void {
        $this->setPageTitle(new PageTitle(sprintf(_('Application detail "%s"'), $this->getEntity()->__toString()), 'fa fa-user'));
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    final public function titleTransitions(): void {
        $this->setPageTitle(new PageTitle(_('Group transitions'), 'fa fa-user'));
    }

    /**
     * @param IResource|string|null $resource
     * @param string $privilege
     * @return bool
     * @throws EventNotFoundException
     */
    protected function traitIsAuthorized($resource, string $privilege): bool {
        return $this->isContestsOrgAuthorized($resource, $privilege);
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function renderDetail(): void {
        $this->template->event = $this->getEvent();
        $this->template->hasSchedule = ($this->getEvent()->getScheduleGroups()->count() !== 0);
    }

    /**
     * @return void
     * @throws EventNotFoundException
     */
    public function renderList(): void {
        $this->template->event = $this->getEvent();
    }

    protected function createComponentPersonScheduleGrid(): PersonGrid {
        return new PersonGrid($this->getContext());
    }

    /**
     * @return ApplicationComponent
     * @throws BadTypeException
     * @throws EventNotFoundException
     * @throws ForbiddenRequestException
     * @throws ModelNotFoundException
     * @throws NeonSchemaException
     *
     */
    protected function createComponentApplicationComponent(): ApplicationComponent {
        $source = new SingleEventSource($this->getEvent(), $this->getContext(), $this->getEventDispatchFactory());
        foreach ($source->getHolders() as $key => $holder) {
            if ($key === $this->getEntity()->getPrimary()) {
                return new ApplicationComponent($this->getContext(), $this->applicationHandlerFactory->create($this->getEvent(), new MemoryLogger()), $holder);
            }
        }
        throw new InvalidStateException();
    }
    /**
     * @return TransitionButtonsComponent
     * @throws BadTypeException
     * @throws EventNotFoundException
     * @throws ForbiddenRequestException
     * @throws ModelNotFoundException
     * @throws NeonSchemaException
     *
     */
    protected function createComponentApplicationTransitions(): TransitionButtonsComponent {
        $source = new SingleEventSource($this->getEvent(), $this->getContext(), $this->getEventDispatchFactory());
        foreach ($source->getHolders() as $key => $holder) {
            if ($key === $this->getEntity()->getPrimary()) {
                return new TransitionButtonsComponent($this->getContext(), $this->applicationHandlerFactory->create($this->getEvent(), new MemoryLogger()), $holder);
            }
        }
        throw new InvalidStateException();
    }

    /**
     * @return MassTransitionsControl
     * @throws EventNotFoundException
     */
    final protected function createComponentMassTransitions(): MassTransitionsControl {
        return new MassTransitionsControl($this->getContext(), $this->getEvent());
    }

    /**
     * @return AbstractApplicationsGrid
     * @throws AbortException
     *
     */
    abstract protected function createComponentGrid(): AbstractApplicationsGrid;

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
}
