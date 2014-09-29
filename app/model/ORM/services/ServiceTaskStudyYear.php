<?php

use ORM\CachingServiceTrait;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceTaskStudyYear extends AbstractServiceSingle {

    use CachingServiceTrait;

    protected $tableName = DbNames::TAB_TASK_STUDY_YEAR;
    protected $modelClassName = 'ModelTaskStudyYear';

}

