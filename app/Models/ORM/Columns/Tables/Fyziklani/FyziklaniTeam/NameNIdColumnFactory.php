<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\Fyziklani\FyziklaniTeam;

use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ORM\Models\Fyziklani\TeamModel2;
use Fykosak\NetteORM\Model;
use Nette\Utils\Html;

/**
 * @phpstan-extends ColumnFactory<TeamModel2,never>
 */
class NameNIdColumnFactory extends ColumnFactory
{
    /**
     * @param TeamModel2 $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        return Html::el('span')->addText(sprintf(_('%s (%d)'), $model->name, $model->fyziklani_team_id));
    }
}
