<?php

namespace FKSDB\DBReflection\ColumnFactories\Types;

use FKSDB\Components\Controls\Badges\NotSetBadge;
use FKSDB\DBReflection\OmittedControlException;
use FKSDB\ORM\Models\AbstractModelSingle;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * Class StateRow
 * @author Michal Červeňák <miso@fykos.cz>
 */
class StateColumnFactory extends DefaultColumnFactory {

    protected array $states = [];

    protected function createHtmlValue(AbstractModelSingle $model): Html {
        $state = $model->{$this->getModelAccessKey()};
        if (is_null($state)) {
            return NotSetBadge::getHtml();
        }
        $stateDef = $this->getState($state);
        return Html::el('span')->addAttributes(['class' => $stateDef['badge']])->addText(_($stateDef['label']));
    }

    public function setStates(array $states): void {
        $this->states = $states;
    }

    public function getState(string $state): array {
        if (isset($this->states[$state])) {
            return $this->states[$state];
        }
        return ['badge' => '', 'label' => ''];
    }

    protected function createFormControl(...$args): BaseControl {
        throw new OmittedControlException();
    }
}
