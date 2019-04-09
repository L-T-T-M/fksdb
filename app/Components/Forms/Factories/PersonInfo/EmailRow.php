<?php

namespace FKSDB\Components\Forms\Factories\PersonInfo;

use FKSDB\Components\Forms\Factories\AbstractRow;
use Nette\Forms\Form;
use Nette\Forms\IControl;

/**
 * Class EmailField
 * @package FKSDB\Components\Forms\Factories\PersonInfo
 */
class EmailRow extends AbstractRow {

    /**
     * @return string
     */
    public static function getTitle(): string {
        return _('E-mail');
    }

    /**
     * @return IControl
     */
    public function createField(): IControl {
        $control = parent::createField();
        $control->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, _('Neplatný tvar e-mailu.'));
        return $control;
    }
    /**
     * @return int
     */
    public function getPermissionsValue(): int {
        return 64;
    }
}
