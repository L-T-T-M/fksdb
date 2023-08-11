<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Links;

use Fykosak\NetteORM\Exceptions\CannotAccessModelException;
use Fykosak\NetteORM\Model;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;

/**
 * @template M of Model
 */
abstract class LinkFactory
{
    /** @phpstan-var class-string<M> */
    protected string $modelClassName;

    /**
     * @param class-string<M>|null $modelClassName
     */
    public function __construct(string $modelClassName = null)
    {
        $this->modelClassName = $modelClassName;
    }

    /**
     * @throws InvalidLinkException
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     */
    public function create(Presenter $presenter, Model $model): string
    {
        return $presenter->link(...$this->createLinkParameters($model));
    }

    /**
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     * @phpstan-return M
     */
    protected function getModel(Model $modelSingle): ?Model
    {
        return $modelSingle->getReferencedModel($this->modelClassName);
    }

    /**
     * @throws CannotAccessModelException
     * @throws InvalidLinkException
     * @throws \ReflectionException
     * @phpstan-return array{string,array<string,scalar>}
     */
    public function createLinkParameters(Model $model): array
    {
        $model = $this->getModel($model);
        if (is_null($model)) {
            throw new InvalidLinkException();
        }
        return [
            $this->getDestination($model),
            $this->prepareParams($model),
        ];
    }

    abstract protected function getDestination(Model $model): string;

    /**
     * @phpstan-param M $model
     * @phpstan-return array<string,scalar>
     */
    abstract protected function prepareParams(Model $model): array;

    abstract public function getText(): string;
}
