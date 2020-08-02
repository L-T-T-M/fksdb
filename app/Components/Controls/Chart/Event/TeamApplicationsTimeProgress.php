<?php

namespace FKSDB\Components\React\ReactComponent\Events;

use FKSDB\Components\Controls\Chart\IChart;
use FKSDB\Components\React\ReactComponent;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Models\ModelEventType;
use FKSDB\ORM\Services\Fyziklani\ServiceFyziklaniTeam;
use FKSDB\ORM\Services\ServiceEvent;
use Nette\Application\UI\Control;
use Nette\DI\Container;

/**
 * Class TeamApplicationsTimeProgress
 * @author Michal Červeňák <miso@fykos.cz>
 */
class TeamApplicationsTimeProgress extends ReactComponent implements IChart {

    /** @var ServiceFyziklaniTeam */
    private $serviceFyziklaniTeam;

    /** @var ModelEventType */
    private $eventType;

    /** @var ServiceEvent */
    private $serviceEvent;

    /**
     * TeamApplicationsTimeProgress constructor.
     * @param Container $context
     * @param ModelEvent $event
     */
    public function __construct(Container $context, ModelEvent $event) {
        parent::__construct($context, 'events.applications-time-progress.teams');
        $this->eventType = $event->getEventType();
    }

    /**
     * @param ServiceFyziklaniTeam $serviceFyziklaniTeam
     * @param ServiceEvent $serviceEvent
     * @return void
     */
    public function injectPrimary(ServiceFyziklaniTeam $serviceFyziklaniTeam, ServiceEvent $serviceEvent) {
        $this->serviceFyziklaniTeam = $serviceFyziklaniTeam;
        $this->serviceEvent = $serviceEvent;
    }

    /**
     * @return array[]|mixed|null
     */
    protected function getData() {
        $data = [
            'teams' => [],
            'events' => [],
        ];
        /** @var ModelEvent $event */
        foreach ($this->serviceEvent->getEventsByType($this->eventType) as $event) {
            $data['teams'][$event->event_id] = $this->serviceFyziklaniTeam->getTeamsAsArray($event);
            $data['events'][$event->event_id] = $event->__toArray();
        }
        return $data;
    }

    public function getTitle(): string {
        return 'Team applications time progress';
    }

    public function getControl(): Control {
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription() {
        return null;
    }
}
