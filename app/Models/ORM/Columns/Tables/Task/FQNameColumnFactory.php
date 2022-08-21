<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\Task;

use FKSDB\Models\ORM\Columns\AbstractColumnException;
use FKSDB\Models\ORM\Columns\ColumnFactory;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\TaskModel;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

class FQNameColumnFactory extends ColumnFactory
{

    /**
     * @param TaskModel $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        return Html::el('span')->addText($model->getFQName());
    }

    /**
     * @throws AbstractColumnException
     */
    protected function createFormControl(...$args): BaseControl
    {
        throw new AbstractColumnException();
    }
}
