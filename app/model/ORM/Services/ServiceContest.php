<?php

namespace FKSDB\ORM\Services;


use FKSDB\ORM\DbNames;
use FKSDB\ORM\DeprecatedLazyDBTrait;
use FKSDB\ORM\Models\ModelContest;
use Nette\Database\Context;
use Nette\Database\IConventions;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 * @method ModelContest findByPrimary($key)
 */
class ServiceContest extends AbstractServiceSingle {
    use DeprecatedLazyDBTrait;

    public function __construct(Context $connection, IConventions $conventions) {
        parent::__construct($connection, $conventions, DbNames::TAB_CONTEST, ModelContest::class);
    }
}
