<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models\Schedule;

use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\PaymentModel;
use Nette\Database\Table\ActiveRow;

/**
 * @property-read ActiveRow payment
 * @property-read ActiveRow person_schedule
 * @property-read int person_schedule_id
 */
class ModelSchedulePayment extends Model
{

    public function getPayment(): PaymentModel
    {
        return PaymentModel::createFromActiveRow($this->payment);
    }

    public function getPersonSchedule(): ModelPersonSchedule
    {
        return ModelPersonSchedule::createFromActiveRow($this->person_schedule);
    }
}
