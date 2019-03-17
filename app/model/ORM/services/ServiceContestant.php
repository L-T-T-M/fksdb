<?php

namespace FKSDB\ORM\Services;

use AbstractServiceSingle;
use FKSDB\ORM\DbNames;
use Nette\Database\Table\Selection;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceContestant extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_CONTESTANT_BASE;
    protected $viewName = DbNames::VIEW_CONTESTANT;
    protected $modelClassName = 'FKSDB\ORM\Models\ModelContestant';

    /**
     * @note Read-only (loads data from view).
     *
     * @param int $contest_id
     * @param int $year
     * @return Selection
     */
    public function getCurrentContestants($contest_id, $year) {
        $contestants = $this->getConnection()->table($this->viewName)
            ->select('*');


        $contestants->where([
            'v_contestant.contest_id' => $contest_id,
            'v_contestant.year' => $year,
        ]);

        return $contestants;
    }

}

