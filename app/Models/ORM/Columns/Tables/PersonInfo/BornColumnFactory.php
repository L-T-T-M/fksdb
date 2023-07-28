<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\PersonInfo;

use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ValuePrinters\DatePrinter;
use FKSDB\Components\Forms\Controls\WriteOnly\WriteOnlyDatePicker;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\PersonInfoModel;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

class BornColumnFactory extends ColumnFactory
{

    protected function createFormControl(...$args): BaseControl
    {
        $control = new WriteOnlyDatePicker($this->getTitle());
        $control->setDefaultValue((new \DateTime())->modify('-50 years'));
        return $control;
    }

    /**
     * @param PersonInfoModel $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        return (new DatePrinter(_('__date')))($model->born);
    }
}
