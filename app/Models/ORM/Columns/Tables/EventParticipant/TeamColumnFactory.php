<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\EventParticipant;

use FKSDB\Components\Badges\NotSetBadge;
use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ValuePrinters\StringPrinter;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\ModelEventParticipant;
use Nette\Utils\Html;

class TeamColumnFactory extends ColumnFactory
{

    /**
     * @param ModelEventParticipant $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        $team = $model->getFyziklaniTeam();
        return $team ? (new StringPrinter())($team->name) : NotSetBadge::getHtml();
    }
}
