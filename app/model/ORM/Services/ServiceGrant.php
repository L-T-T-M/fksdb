<?php

namespace FKSDB\ORM\Services;

use FKSDB\ORM\AbstractServiceSingle;
use FKSDB\ORM\DbNames;
use FKSDB\ORM\DeprecatedLazyDBTrait;
use FKSDB\ORM\Models\ModelGrant;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceGrant extends AbstractServiceSingle {
    use DeprecatedLazyDBTrait;

    public function getModelClassName(): string {
        return ModelGrant::class;
    }

    protected function getTableName(): string {
        return DbNames::TAB_GRANT;
    }
}
