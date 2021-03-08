<?php

namespace FKSDB\Models\Payment\Transition;

use FKSDB\Models\ORM\Models\AbstractModelSingle;
use FKSDB\Models\ORM\Models\ModelPayment;
use FKSDB\Models\ORM\Services\ServicePayment;
use FKSDB\Models\Transitions\Holder\ModelHolder;
use FKSDB\Models\Transitions\Machine\Machine;

class PaymentHolder implements ModelHolder {

    private ?ModelPayment $model;
    private ServicePayment $service;

    public function __construct(?ModelPayment $model, ServicePayment $servicePayment) {
        $this->model = $model;
        $this->service = $servicePayment;
    }

    public static function createNew(array $data, ServicePayment $servicePayment): self {
        $model = $servicePayment->createNewModel($data);
        return new static($model, $servicePayment);
    }

    public function updateState(string $newState): void {
        $this->service->updateModel2($this->model, ['state' => $newState]);
        $newModel = $this->service->refresh($this->model);
        $this->model = $newModel;
    }

    public function getState(): string {
        return isset($this->model) ? $this->model->state : Machine::STATE_INIT;
    }

    public function getModel(): ?AbstractModelSingle {
        return $this->model;
    }

    public function updateData(array $data): void {
        if (isset($this->model)) {
            $this->service->updateModel2($this->model, $data);
            $this->model = $this->service->refresh($this->model);
        } else {
            $this->model = $this->service->createNewModel($data);
        }
    }
}
