<?php

declare(strict_types=1);

namespace FKSDB\Components\Forms\Factories\Events;

use FKSDB\Models\Events\Model\Holder\Field;
use Nette\Forms\Controls\Checkbox;

class CheckboxFactory extends AbstractFactory
{

    public function createComponent(Field $field): Checkbox
    {
        $component = new Checkbox($field->label);
        $component->setOption('description', $field->description);
        return $component;
    }
}
