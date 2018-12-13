<?php

namespace FKSDB\EventPayment\PriceCalculator\PreProcess;

use FKSDB\EventPayment\PriceCalculator\Price;
use FKSDB\ORM\ModelEventParticipant;
use FKSDB\ORM\ModelPayment;
use Nette\Application\BadRequestException;

class EventSchedulePrice extends AbstractPreProcess {
    /**
     * @var \ServiceEventParticipant
     */
    private $serviceEventParticipant;

    public function __construct(\ServiceEventParticipant $serviceEventParticipant) {
        $this->serviceEventParticipant = $serviceEventParticipant;
    }

    public function calculate(ModelPayment $modelPayment): Price {
        $price = new Price(0, $modelPayment->currency);
        $ids = $this->getData($modelPayment);
        $schedule = $modelPayment->getEvent()->getParameter('schedule');
        foreach ($ids as $id) {
            $participantSchedule = $this->getParticipantSchedule($id);
            if ($participantSchedule) {
                $schedulePrice = $this->calculateSchedule($participantSchedule, $schedule, $modelPayment->currency);
                $price->add($schedulePrice);
            }
        }
        return $price;
    }

    private function getParticipantSchedule($id) {
        $row = $this->serviceEventParticipant->findByPrimary($id);
        $model = ModelEventParticipant::createFromTableRow($row);
        return $model->schedule;
    }

    public function getGridItems(ModelPayment $modelPayment): array {
        $ids = $this->getData($modelPayment);
        $items = [];
        $schedule = $modelPayment->getEvent()->getParameter('schedule');
        foreach ($ids as $id) {
            $participantSchedule = $this->getParticipantSchedule($id);
            if ($participantSchedule) {
                $price = $this->calculateSchedule($participantSchedule, $schedule, $modelPayment->currency);
                $items[] = [
                    'label' => '',
                    'price' => $price,
                ];

            }
        }
        return $items;
    }

    private function calculateSchedule($participantSchedule, $schedule, $currency): Price {
        $data = \json_decode($participantSchedule);

        $price = new Price(0, $currency);
        foreach ($data as $key => $selectedId) {
            $parallel = $this->findScheduleItem($schedule, $key, $selectedId);
            switch ($price->getCurrency()) {
                case Price::CURRENCY_EUR:
                    $price->addAmount($parallel['price']['eur']);
                    break;
                case Price::CURRENCY_KC:
                    $price->addAmount($parallel['price']['kc']);
                    break;
            }
        }
        return $price;
    }


    private function findScheduleItem($schedule, string $key, int $id) {
        foreach ($schedule as $scheduleKey => $item) {
            if ($scheduleKey !== $key) {
                continue;
            }
            foreach ($item['parallels'] as $parallel) {
                if ($parallel['id'] == $id) {
                    return $parallel;
                }
            }
        }
        throw new BadRequestException('Item nenájdený');
    }
}
