<?php

namespace EventModule;

use Events\Model\ApplicationHandlerFactory;
use Events\Model\Grid\SingleEventSource;
use FKSDB\Components\Events\ApplicationComponent;
use FKSDB\Components\Grids\Events\Application\AbstractApplicationGrid;
use FKSDB\Components\Grids\Schedule\PersonGrid;
use FKSDB\Components\React\ReactComponent\Events\SingleApplicationsTimeProgress;
use FKSDB\Logging\FlashDumpFactory;
use FKSDB\Logging\MemoryLogger;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Services\ServiceEventParticipant;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use function in_array;

/**
 * Class ApplicationPresenter
 * @package EventModule
 */
abstract class AbstractApplicationPresenter extends BasePresenter {
    use EventEntityTrait;

    /**
     * @var ApplicationHandlerFactory
     */
    protected $applicationHandlerFactory;
    /**
     * @var FlashDumpFactory
     */
    protected $dumpFactory;

    /**
     * @var ServiceEventParticipant
     */
    protected $serviceEventParticipant;

    /**
     * @param ApplicationHandlerFactory $applicationHandlerFactory
     */
    public function injectHandlerFactory(ApplicationHandlerFactory $applicationHandlerFactory) {
        $this->applicationHandlerFactory = $applicationHandlerFactory;
    }

    /**
     * @param FlashDumpFactory $dumpFactory
     */
    public function injectFlashDumpFactory(FlashDumpFactory $dumpFactory) {
        $this->dumpFactory = $dumpFactory;
    }


    /**
     * @param ServiceEventParticipant $serviceEventParticipant
     */
    public function injectServiceEventParticipant(ServiceEventParticipant $serviceEventParticipant) {
        $this->serviceEventParticipant = $serviceEventParticipant;
    }

    /**
     * @param int $id
     * @throws AbortException
     * @throws BadRequestException
     * @throws ForbiddenRequestException
     */
    public function actionDetail(int $id) {
        $this->loadEntity($id);
    }

    /**
     * @throws AbortException
     * @throws BadRequestException
     */
    protected function renderDetail() {
        $this->template->event = $this->getEvent();
        $this->template->hasSchedule = ($this->getEvent()->getScheduleGroups()->count() !== 0);
    }

    /**
     * @throws BadRequestException
     * @throws AbortException
     */
    public function renderList() {
        $this->template->event = $this->getEvent();
    }

    /**
     * @return PersonGrid
     */
    protected function createComponentPersonScheduleGrid(): PersonGrid {
        return new PersonGrid($this->getTableReflectionFactory());
    }

    /**
     * @return ApplicationComponent
     * @throws BadRequestException
     * @throws AbortException
     */
    public function createComponentApplicationComponent(): ApplicationComponent {
        $holders = [];
        $handlers = [];
        $flashDump = $this->dumpFactory->create('application');
        $source = new SingleEventSource($this->getEvent(), $this->container);
        foreach ($source as $key => $holder) {
            $holders[$key] = $holder;
            $handlers[$key] = $this->applicationHandlerFactory->create($this->getEvent(), new MemoryLogger());
        }

        return new ApplicationComponent($handlers[$this->getEntity()->getPrimary()], $holders[$this->getEntity()->getPrimary()], $flashDump);
    }

    /**
     * @return SingleApplicationsTimeProgress
     * @throws AbortException
     * @throws BadRequestException
     */
    protected function createComponentSingleApplicationsTimeProgress() {
        $events = [];
        foreach ($this->getProgressEventIdsByType() as $id) {
            $row = $this->serviceEvent->findByPrimary($id);
            $events[$id] = ModelEvent::createFromActiveRow($row);
        }
        return new SingleApplicationsTimeProgress($this->context, $events, $this->serviceEventParticipant);
    }

    /**
     * @return int[]
     * @throws AbortException
     * @throws BadRequestException
     * TODO hardcore eventIds
     */
    private function getProgressEventIdsByType(): array {
        $eventIds = [
            1 => [30, 31, 32, /*33, 34,*/
                1, 27, 95, 116, 125, 137, 145],
            2 => [2, 7, 92, 113, 123, 135, 143],
            3 => [3, 126, 35],
            7 => [6, 91, 124],
            11 => [111, 119, 129, 140],
            12 => [93, 115, 121, 136, 144],
            9 => [8, 94, 114, 122, 134, 141],
        ];
        $typeId = $this->getEvent()->event_type_id;
        if (isset($eventIds[$typeId])) {
            return $eventIds[$typeId];
        }
        return array_values($this->serviceEvent->getTable()->where('event_type_id', $this->getEvent()->event_type_id)->fetchPairs('event_id', 'event_id'));
    }

    /**
     * @return bool
     * @throws BadRequestException
     * @throws AbortException
     */
    protected function isTeamEvent(): bool {
        if (in_array($this->getEvent()->event_type_id, self::TEAM_EVENTS)) {
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    abstract public function titleList();

    /**
     * @return void
     */
    abstract public function titleDetail();

    /**
     * @return AbstractApplicationGrid
     * @throws AbortException
     * @throws BadRequestException
     */
    abstract function createComponentGrid(): AbstractApplicationGrid;
}
