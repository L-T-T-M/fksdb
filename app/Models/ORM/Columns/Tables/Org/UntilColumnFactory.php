<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\Org;

use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ValuePrinters\StringPrinter;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\OrgModel;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\Html;

class UntilColumnFactory extends ColumnFactory
{

    /**
     * @param OrgModel $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        if (\is_null($model->until)) {
            return Html::el('span')->addAttributes(['class' => 'badge bg-success'])->addText(_('Still organizes'));
        } else {
            return (new StringPrinter())($model->until);
        }
    }

    protected function createFormControl(...$args): BaseControl
    {
        [$min, $max] = $args;
        if (\is_null($max) || \is_null($min)) {
            throw new \InvalidArgumentException();
        }
        $control = new TextInput($this->getTitle());

        $control->addCondition(Form::FILLED)
            ->addRule(Form::NUMERIC, _('Must be a number'))
            ->addRule(Form::RANGE, _('Final year is not in interval [%d, %d].'), [$min, $max]);
        return $control;
    }
}
