<?php

declare(strict_types=1);

namespace FKSDB\Models\Expressions;

/**
 * @phpstan-extends EvaluatedExpression<bool,bool,ArgType>
 * @template ArgType
 */
abstract class VariadicExpression extends EvaluatedExpression
{
    /** @phpstan-var array<int,callable(ArgType):bool> */
    protected array $arguments;

    /**
     * @phpstan-param callable(ArgType):bool $args
     */
    public function __construct(...$args)
    {
        $this->arguments = $args;
    }
}
