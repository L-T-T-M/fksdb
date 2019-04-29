<?php

namespace FKSDB\Components\DatabaseReflection\Event;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;

/**
 * Class EventYearRow
 * @package FKSDB\Components\DatabaseReflection\Event
 */
class EventYearRow extends AbstractEventRowFactory {

    /**
     * @return string
     */
    public function getTitle(): string {
        return _('Event year');
    }

    /**
     * @return BaseControl
     */
    public function createField(): BaseControl {
        $control = parent::createField();
        $control->addRule(Form::INTEGER, _('%label musí být číslo.'))
            ->addRule(Form::FILLED, _('%label je povinný.'))
            ->setOption('description', _('Ročník akce musí být unikátní pro daný typ akce.'));
        return $control;
    }
}
