<?php

declare(strict_types=1);

namespace FKSDB\Modules\EventModule;

use FKSDB\Components\Charts\Core\Chart;
use FKSDB\Components\Charts\Event\Applications\ApplicationRationGeoChart;
use FKSDB\Components\Charts\Event\Applications\ParticipantsTimeGeoChart;
use FKSDB\Components\Charts\Event\Applications\TeamsGeoChart;
use FKSDB\Components\Charts\Event\ApplicationsTimeProgress\SingleComponent;
use FKSDB\Components\Charts\Event\ApplicationsTimeProgress\TeamComponent;
use FKSDB\Components\Charts\Event\Model\GraphComponent;
use FKSDB\Components\Charts\Event\ParticipantAcquaintance\ParticipantAcquaintanceChart;
use FKSDB\Models\Events\Exceptions\EventNotFoundException;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\Exceptions\NotImplementedException;
use FKSDB\Modules\Core\PresenterTraits\ChartPresenterTrait;
use Nette\Application\ForbiddenRequestException;

class ChartPresenter extends BasePresenter
{
    use ChartPresenterTrait;

    /**
     * @throws EventNotFoundException
     */
    public function authorizedList(): void
    {
        $this->setAuthorized($this->isAllowed($this->getModelResource(), 'list'));
    }

    protected function getModelResource(): string
    {
        return 'event.chart';
    }

    /**
     * @throws EventNotFoundException
     */
    public function authorizedChart(): void
    {
        $this->setAuthorized($this->isAllowed($this->getModelResource(), 'chart'));
    }

    /**
     * @throws BadTypeException
     * @throws EventNotFoundException
     * @throws ForbiddenRequestException
     * @throws NotImplementedException
     */
    protected function startup(): void
    {
        parent::startup();
        $this->selectChart();
    }

    /**
     * @return Chart[]
     * @throws EventNotFoundException
     * @throws BadTypeException
     */
    protected function registerCharts(): array
    {
        if ($this->getEvent()->isTeamEvent()) {
            return [
                'teamApplicationProgress' => new TeamComponent($this->getContext(), $this->getEvent()),
                'teamsPerCountry' => new TeamsGeoChart($this->getContext(), $this->getEvent()),
                'ratioPerCountry' => new ApplicationRationGeoChart($this->getContext(), $this->getEvent()),
                'participantsInTimeGeo' => new ParticipantsTimeGeoChart($this->getContext(), $this->getEvent()),
                'model' => new GraphComponent(
                    $this->getContext(),
                    $this->eventDispatchFactory->getTeamMachine($this->getEvent())
                ),
            ];
        } else {
            return [
                'participantAcquaintance' => new ParticipantAcquaintanceChart($this->getContext(), $this->getEvent()),
                'singleApplicationProgress' => new SingleComponent($this->getContext(), $this->getEvent()),
                'model' => new GraphComponent(
                    $this->getContext(),
                    $this->eventDispatchFactory->getEventMachine($this->getEvent())
                ),
            ];
        }
    }
}
