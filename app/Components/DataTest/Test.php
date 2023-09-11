<?php

declare(strict_types=1);

namespace FKSDB\Components\DataTest;

use Fykosak\NetteORM\Model;
use Fykosak\Utils\Logging\Logger;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

/**
 * @template TModel of Model
 */
abstract class Test
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $container->callInjects($this);
    }

    /**
     * @phpstan-param TModel $model
     */
    abstract public function run(Logger $logger, Model $model): void;

    abstract public function getTitle(): Title;

    public function getDescription(): ?string
    {
        return null;
    }
}
