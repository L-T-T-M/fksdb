<?php

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceEvent extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_EVENT;
    protected $modelClassName = 'ModelEvent';

    public function getEvents(ModelContest $contest) {
        $result = $this->getTable()
                ->select(DbNames::TAB_EVENT . '.*')
                ->select(DbNames::TAB_EVENT_TYPE . '.name AS `type_name`');
        $result->where(DbNames::TAB_EVENT_TYPE . '.contest_id', $contest->contest_id);
        return $result;
    }

}

