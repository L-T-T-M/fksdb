<?php

namespace FKSDB\Models\Events\Model\Holder\SecondaryModelStrategies;

use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\ORM\IService;
use Nette\Database\Table\ActiveRow;
use Nette\InvalidStateException;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
abstract class SecondaryModelStrategy {

    /**
     * @param BaseHolder[] $holders
     * @param ActiveRow[] $models
     * @return void
     */
    public function setSecondaryModels(array $holders, iterable $models): void {
        $filledHolders = 0;
        foreach ($models as $secondaryModel) {
            $holders[$filledHolders]->setModel($secondaryModel);
            if (++$filledHolders > count($holders)) {
                throw new InvalidStateException('Supplied more than expected secondary models.');
            }
        }
        for (; $filledHolders < count($holders); ++$filledHolders) {
            $holders[$filledHolders]->setModel(null);
        }
    }

    /**
     * @param IService $service
     * @param string|null $joinOn
     * @param string|null $joinTo
     * @param BaseHolder[] $holders
     * @param ActiveRow|null $primaryModel
     * @return void
     */
    public function loadSecondaryModels(IService $service, ?string $joinOn, ?string $joinTo, array $holders, ?ActiveRow $primaryModel = null): void {
        if ($primaryModel) {
            $joinValue = $joinTo ? $primaryModel[$joinTo] : $primaryModel->getPrimary();
            $secondary = $service->getTable()->where($joinOn, $joinValue);
            if ($joinTo) {
                $event = reset($holders)->getEvent();
                $secondary->where(BaseHolder::EVENT_COLUMN, $event->getPrimary());
            }
        } else {
            $secondary = [];
        }
        $this->setSecondaryModels($holders, $secondary);
    }

    /**
     * @param IService $service
     * @param string|null $joinOn
     * @param string|null $joinTo
     * @param BaseHolder[] $holders
     * @param ActiveRow $primaryModel
     * @return void
     */
    public function updateSecondaryModels(IService $service, ?string $joinOn, ?string $joinTo, array $holders, ActiveRow $primaryModel): void {
        $joinValue = $joinTo ? $primaryModel[$joinTo] : $primaryModel->getPrimary();
        foreach ($holders as $baseHolder) {
            $joinData = [$joinOn => $joinValue];
            if ($joinTo) {
                $existing = $service->getTable()->where($joinData)->where(BaseHolder::EVENT_COLUMN, $baseHolder->getEvent()->getPrimary());
                $conflicts = [];
                foreach ($existing as $secondaryModel) {
                   // if ($baseModel && ($baseModel->getPrimary(false) !== $secondaryModel->getPrimary())) { TODO WTF?
                        $conflicts[] = $secondaryModel;
                   // }
                }
                if ($conflicts) {
                    // TODO this could be called even for joining via PK
                    $this->resolveMultipleSecondaries($baseHolder, $conflicts, $joinData);
                }
            }
            $baseHolder->data += (array)$joinData;
        }
    }

    abstract protected function resolveMultipleSecondaries(BaseHolder $holder, array $secondaries, array $joinData): void;
}
