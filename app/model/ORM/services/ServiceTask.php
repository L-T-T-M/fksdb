<?php

use FKSDB\ORM\ModelContest;
use FKSDB\ORM\ModelTask;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceTask extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_TASK;
    protected $modelClassName = 'FKSDB\ORM\ModelTask';

    /**
     * Syntactic sugar.
     *
     * @param ModelContest $contest
     * @param int $year
     * @param int $series
     * @param int $tasknr
     * @return ModelTask|null
     */
    public function findBySeries(ModelContest $contest, $year, $series, $tasknr) {
        $result = $this->getTable()->where([
                    'contest_id' => $contest->contest_id,
                    'year' => $year,
                    'series' => $series,
                    'tasknr' => $tasknr,
        ])->fetch();

        if ($result !== false) {
            return ModelTask::createFromTableRow($result);
        } else {
            return null;
        }
    }

}

