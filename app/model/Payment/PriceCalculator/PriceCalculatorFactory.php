<?php

namespace FKSDB\Payment\PriceCalculator;

use FKSDB\ORM\ModelEvent;
use FKSDB\Payment\PriceCalculator\PreProcess\EventAccommodationPrice;

class PriceCalculatorFactory {
    /**
     * @var \ServiceEventParticipant
     */
    private $serviceEventParticipant;
    /**
     * @var \ServiceEventPersonAccommodation
     */
    private $serviceEventPersonAccommodation;

    public function __construct(\ServiceEventPersonAccommodation $serviceEventPersonAccommodation, \ServiceEventParticipant $serviceEventParticipant) {
        $this->serviceEventParticipant = $serviceEventParticipant;
        $this->serviceEventPersonAccommodation = $serviceEventPersonAccommodation;
    }

    public function createCalculator(ModelEvent $event): PriceCalculator {
        $calculator = new PriceCalculator($event);
        // $calculator->addPreProcess(new EventPrice($this->serviceEventParticipant));
        // $calculator->addPreProcess(new EventSchedulePrice($this->serviceEventParticipant));// TODO mergnuť s programom pre FOF
        $calculator->addPreProcess(new EventAccommodationPrice());
        return $calculator;
    }
}
