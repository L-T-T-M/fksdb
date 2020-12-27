<?php

namespace FKSDB\Models\DBReflection\ColumnFactories\Tables\Teacher;

use FKSDB\Models\DBReflection\ColumnFactories\Types\DefaultColumnFactory;
use FKSDB\Models\ValuePrinters\DatePrinter;
use FKSDB\Components\Forms\Controls\DateInputs\DateInput;
use FKSDB\Models\ORM\Models\AbstractModelSingle;
use FKSDB\Models\ORM\Models\ModelTeacher;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * Class UntilRow
 * @author Michal Červeňák <miso@fykos.cz>
 */
class UntilRow extends DefaultColumnFactory {

    /**
     * @param AbstractModelSingle|ModelTeacher $model
     * @return Html
     */
    protected function createHtmlValue(AbstractModelSingle $model): Html {
        if ($model->until === null) {
            return Html::el('span')->addAttributes(['class' => 'badge badge-success'])->addText(_('Still teaches'));
        }
        return (new DatePrinter(_('__date')))($model->until);
    }

    protected function createFormControl(...$args): BaseControl {
        return new DateInput($this->getTitle());
    }
}
