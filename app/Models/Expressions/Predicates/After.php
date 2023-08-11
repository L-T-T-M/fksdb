<?php

declare(strict_types=1);

namespace FKSDB\Models\Expressions\Predicates;

use FKSDB\Models\Expressions\EvaluatedExpression;
use Nette\InvalidStateException;

/**
 * @phpstan-extends EvaluatedExpression<bool,\DateTimeInterface,ArgType>
 * @template ArgType
 */
class After extends EvaluatedExpression
{

    /** @var (callable(ArgType):\DateTimeInterface)|\DateTimeInterface */
    private $datetime;

    /**
     * @param (callable(ArgType):\DateTimeInterface)|\DateTimeInterface $datetime
     */
    public function __construct($datetime)
    {
        $this->datetime = $datetime;
    }

    public function __invoke(...$args): bool
    {
        $datetime = $this->evaluateArgument($this->datetime, ...$args);
        if (!$datetime instanceof \DateTimeInterface) {
            throw new InvalidStateException();
        }
        return $datetime->getTimestamp() <= time();
    }
}
