<?php

declare(strict_types=1);

namespace FKSDB\Models\Payment\Handler;

use Nette\InvalidStateException;

class EmptyDataException extends InvalidStateException
{
}
