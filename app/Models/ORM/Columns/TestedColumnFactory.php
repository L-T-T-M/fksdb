<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns;

use Fykosak\NetteORM\AbstractModel;
use Fykosak\Utils\Logging\Logger;

interface TestedColumnFactory
{
    public function runTest(Logger $logger, AbstractModel $model): void;
}
