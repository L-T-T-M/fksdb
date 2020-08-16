<?php

namespace FKSDB\Events\FormAdjustments;

use FKSDB\Events\Machine\BaseMachine;
use FKSDB\Events\Machine\Machine;
use FKSDB\Events\Model\Holder\BaseHolder;
use FKSDB\Events\Model\Holder\Holder;
use FKSDB\ORM\IService;
use Nette\Database\Table\GroupedSelection;
use Nette\Forms\Form;
use Nette\Forms\IControl;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class ResourceAvailability extends AbstractAdjustment {

    /** @var array fields that specifies amount used (string masks) */
    private $fields;

    /** @var string Name of event parameter that hold overall capacity. */
    private $paramCapacity;
    /** @var array|string */
    private $includeStates;
    /** @var array|string|string[] */
    private $excludeStates;
    /** @var string */
    private $message;

    /**
     * @param array|string $fields
     * @return void
     */
    private function setFields($fields): void {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->fields = $fields;
    }

    /**
     *
     * @param array|string $fields Fields that contain amount of the resource
     * @param string $paramCapacity Name of the parameter with overall capacity.
     * @param string $message String '%avail' will be substitued for the actual amount of available resource.
     * @param string|array $includeStates any state or array of state
     * @param string|array $excludeStates any state or array of state
     */
    public function __construct($fields, $paramCapacity, $message, $includeStates = BaseMachine::STATE_ANY, $excludeStates = ['cancelled']) {
        $this->setFields($fields);
        $this->paramCapacity = $paramCapacity;
        $this->message = $message;
        $this->includeStates = $includeStates;
        $this->excludeStates = $excludeStates;
    }

    protected function _adjust(Form $form, Machine $machine, Holder $holder): void {
        $groups = $holder->getGroupedSecondaryHolders();
        $groups[] = [
            'service' => $holder->getPrimaryHolder()->getService(),
            'holders' => [$holder->getPrimaryHolder()],
        ];

        $services = [];
        $controls = [];

        foreach ($groups as $group) {
            $holders = [];
            $field = null;
            /** @var BaseHolder $baseHolder */
            foreach ($group['holders'] as $baseHolder) {
                $name = $baseHolder->getName();
                foreach ($this->fields as $fieldMask) {
                    $foundControls = $this->getControl($fieldMask);
                    if (!$foundControls) {
                        continue;
                    }
                    if (isset($foundControls[$name])) {
                        $holders[] = $baseHolder;
                        $controls[] = $foundControls[$name];
                        $field = $fieldMask;
                    } elseif ($name == substr($fieldMask, 0, strpos($fieldMask, self::DELIMITER))) {
                        $holders[] = $baseHolder;
                        $controls[] = reset($foundControls); // assume single result;
                        $field = $fieldMask;
                    }
                }
            }
            if ($holders) {
                $services[] = [
                    'service' => $group['service'],
                    'holders' => $holders,
                    'field' => $field,
                ];
            }
        }

        $usage = 0;
        /** @var IService[]|BaseHolder[][] $serviceData */
        foreach ($services as $serviceData) {
            /** @var BaseHolder $firstHolder */
            $firstHolder = reset($serviceData['holders']);
            $event = $firstHolder->getEvent();
            /** @var GroupedSelection $table */
            $table = $serviceData['service']->getTable();
            $table->where($firstHolder->getEventIdColumn(), $event->getPrimary());
            if ($this->includeStates !== BaseMachine::STATE_ANY) {
                $table->where(BaseHolder::STATE_COLUMN, $this->includeStates);
            }
            if ($this->excludeStates !== BaseMachine::STATE_ANY) {
                $table->where('NOT ' . BaseHolder::STATE_COLUMN, $this->excludeStates);
            } else {
                $table->where('1=0');
            }


            $primaries = array_map(function (BaseHolder $baseHolder) {
                return $baseHolder->getModel()->getPrimary(false);
            }, $serviceData['holders']);
            $primaries = array_filter($primaries, function ($primary): bool {
                return (bool)$primary;
            });

            $column = BaseHolder::getBareColumn($serviceData['field']);
            $pk = $table->getName() . '.' . $table->getPrimary();
            if ($primaries) {
                $table->where("NOT $pk IN", $primaries);
            }
            $usage += $table->sum($column);
        }

        $capacity = $holder->getParameter($this->paramCapacity);

        if ($capacity <= $usage) {
            foreach ($controls as $control) {
                $control->setDisabled();
            }
        }

        $form->onValidate[] = function (Form $form) use ($capacity, $usage, $controls) {
            $controlsUsage = 0;
            /** @var IControl $control */
            foreach ($controls as $control) {
                $controlsUsage += (int)$control->getValue();
            }
            if ($capacity < $usage + $controlsUsage) {
                $message = str_replace('%avail', $capacity - $usage, $this->message);
                $form->addError($message);
            }
        };
    }
}
