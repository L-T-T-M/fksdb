<?php

declare(strict_types=1);

namespace FKSDB\Models\Events\FormAdjustments;

use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\ORM\Services\EventParticipantService;
use FKSDB\Models\Transitions\Holder\ModelHolder;
use Nette\Forms\Control;
use Nette\Forms\Form;

class UniqueCheck extends AbstractAdjustment
{
    private string $field;
    private string $message;
    private EventParticipantService $eventParticipantService;

    public function __construct(string $field, string $message, EventParticipantService $eventParticipantService)
    {
        $this->field = $field;
        $this->message = $message;
        $this->eventParticipantService = $eventParticipantService;
    }

    protected function innerAdjust(Form $form, ModelHolder $holder): void
    {
        $controls = $this->getControl($this->field);
        if (!$controls) {
            return;
        }

        foreach ($controls as $control) {
            $control->addRule(function (Control $control) use ($holder): bool {
                $table = $this->eventParticipantService->getTable();
                $column = BaseHolder::getBareColumn($this->field);
                if ($control instanceof ReferencedId) {
                    /* We don't want to fulfill potential promise
                     * as it would be out of transaction here.
                     */
                    $value = $control->getValue(false);
                } else {
                    $value = $control->getValue();
                }
                $model = $holder->getModel();
                $pk = $table->getName() . '.' . $table->getPrimary();

                $table->where($column, $value);
                $table->where(
                    'event_participant.event_id',
                    $holder->event->getPrimary()
                );
                if ($model) {
                    $table->where("NOT $pk = ?", $model->getPrimary());
                }
                return count($table) == 0;
            }, $this->message);
        }
    }
}
