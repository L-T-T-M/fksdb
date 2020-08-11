<?php

namespace FKSDB\Events\Model\Holder;

use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Services\ServiceEvent;
use Nette\InvalidArgumentException;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class SameYearEvent implements IEventRelation {

    private int $eventTypeId;

    private ServiceEvent $serviceEvent;

    /**
     * SameYearEvent constructor.
     * @param string|int $eventTypeId
     * @param ServiceEvent $serviceEvent
     */
    public function __construct(int $eventTypeId, ServiceEvent $serviceEvent) {
        $this->eventTypeId = $eventTypeId;
        $this->serviceEvent = $serviceEvent;
    }

    public function getEvent(ModelEvent $event): ModelEvent {
        $result = $this->serviceEvent->getTable()->where([
            'event_type_id' => $this->eventTypeId,
            'year' => $event->year,
        ]);
        /** @var ModelEvent|false $event */
        $event = $result->fetch();
        if ($event === false) {
            throw new InvalidArgumentException("No event with event_type_id " . $this->eventTypeId . " for the year " . $event->year . ".");
        } elseif ($result->fetch() !== false) {
            throw new InvalidArgumentException("Ambiguous events with event_type_id " . $this->eventTypeId . " for the year " . $event->year . ".");
        } else {
            return $event;
        }
    }
}
