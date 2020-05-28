<?php

namespace FKSDB\Components\DatabaseReflection\Fyziklani\FyziklaniTeam;

use FKSDB\Components\DatabaseReflection\DefaultPrinterTrait;

/**
 * Class PointsRow
 * *
 */
class PointsRow extends AbstractFyziklaniTeamRow {
    use DefaultPrinterTrait;

    /**
     * @return string
     */
    public function getTitle(): string {
        return _('Points');
    }

    /**
     * @return string
     */
    protected function getModelAccessKey(): string {
        return 'points';
    }
}
