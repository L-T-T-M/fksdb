<?php

namespace FKSDB\Components\Forms\Controls;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SelectBox;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class CaptchaBox extends SelectBox {

    const VALUE_YES = 'xyz';
    const VALUE_NO = 'pqrt';

    public function __construct() {
        parent::__construct(_('Jsi robot?'), [
            self::VALUE_NO => _('ne'),
            self::VALUE_YES => _('ano'),
        ]);

        $this->addRule(function(BaseControl $control) {
                    return $control->getValue() == self::VALUE_NO;
                }, _('This form is for people only.'));

        $this->setDefaultValue(self::VALUE_YES);
    }

}
