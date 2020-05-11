<?php

namespace FKSDB\Components\Forms\Controls;

use DateTime;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class TimeBox extends TextInput {

    const TIME_FORMAT = 'H:i:s';

    /**
     * TimeBox constructor.
     * @param null $label
     * @param null $maxLength
     */
    public function __construct($label = NULL, $maxLength = NULL) {
        parent::__construct($label, $maxLength);

        $this->addCondition(Form::FILLED)
                ->addRule(Form::PATTERN, _('%label očekává hh:mm[:ss].'), '/^[0-2]?\d:[0-5]\d(:[0-5]\d)?$/');
    }

    /**
     * @param $value
     * @return \Nette\Forms\Controls\TextBase|void
     */
    public function setValue($value) {
        if ($value instanceof DateTime) {
            $value = $value->format(self::TIME_FORMAT);
        }
        parent::setValue($value);
    }

}
