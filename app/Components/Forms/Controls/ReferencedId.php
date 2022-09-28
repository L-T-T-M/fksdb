<?php

declare(strict_types=1);

namespace FKSDB\Components\Forms\Controls;

use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Forms\Containers\Models\ReferencedContainer;
use FKSDB\Components\Forms\Containers\SearchContainer\SearchContainer;
use FKSDB\Components\Forms\Controls\Schedule\ExistingPaymentException;
use FKSDB\Models\Persons\ModelDataConflictException;
use FKSDB\Models\Persons\ReferencedHandler;
use FKSDB\Models\Utils\Promise;
use Fykosak\NetteORM\Model;
use Fykosak\NetteORM\Service;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Form;

/**
 * Be careful when calling getValue as it executes SQL queries and thus
 * it should always be run inside a transaction.
 */
class ReferencedId extends HiddenField
{
    public const VALUE_PROMISE = '__promise';
    private const JSON_DATA = 'referencedContainer';

    public ReferencedContainer $referencedContainer;
    public SearchContainer $searchContainer;
    public Service $service;
    public ReferencedHandler $handler;

    private ?Promise $promise = null;
    private bool $modelCreated = false;
    private ?Model $model = null;
    private bool $attachedOnValidate = false;
    private bool $attachedSearch = false;

    public function __construct(
        SearchContainer $searchContainer,
        ReferencedContainer $referencedContainer,
        Service $service,
        ReferencedHandler $handler
    ) {
        $this->referencedContainer = $referencedContainer;
        $this->referencedContainer->setReferencedId($this);
        $this->searchContainer = $searchContainer;
        $this->searchContainer->setReferencedId($this);

        $this->service = $service;
        $this->handler = $handler;

        parent::__construct();

        $this->monitor(Form::class, function (Form $form): void {
            if (!$this->attachedOnValidate) {
                $form->onValidate[] = function () {
                    $this->createPromise();
                };
                $this->attachedOnValidate = true;
            }
        });
        $this->monitor(IContainer::class, function (IContainer $container): void {
            if (!$this->attachedSearch) {
                $container->addComponent($this->referencedContainer, $this->getName() . '_container');
                $container->addComponent($this->searchContainer, $this->getName() . '_search');
                $this->attachedSearch = true;
            }
        });
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * @param string|int|Model $value
     * @return static
     */
    public function setValue($value, bool $force = false): self
    {
        if ($value instanceof Model) {
            $this->model = $value;
        } elseif ($value === self::VALUE_PROMISE) {
            $this->model = null;
        } else {
            $this->model = $this->service->findByPrimary($value);
        }

        $this->setModel(
            $this->model,
            $force
                ? ReferencedIdMode::tryFrom(ReferencedIdMode::FORCE)
                : ReferencedIdMode::tryFrom(ReferencedIdMode::NORMAL)
        );

        if (isset($this->model)) {
            $value = $this->model->getPrimary();
        }
        $this->searchContainer->setOption('visible', !$value);
        $this->referencedContainer->setOption('visible', (bool)$value);
        return parent::setValue($value);
    }

    /**
     * If you are calling this method out of transaction, set $fullfilPromise to
     * false. This is the case for event form adjustments.
     *
     * @return mixed
     */
    public function getValue(bool $usePromise = true)
    {
        if ($usePromise && $this->promise) {
            return $this->promise->getValue();
        }
        $value = parent::getValue();
        return $value ?: null;
    }

    public function rollback(): void
    {
        if ($this->modelCreated) {
            $this->setModel(null, ReferencedIdMode::tryFrom(ReferencedIdMode::ROLLBACK));
            if (parent::getValue()) {
                parent::setValue(self::VALUE_PROMISE);
            }
        }
    }

    /**
     * @param bool $value
     * @return static
     */
    public function setDisabled($value = true): self
    {
        $this->referencedContainer->setDisabled($value);
        return $this;
    }

    private function createPromise(): void
    {
        $values = $this->referencedContainer->getValues('array');
        $referencedId = $this->getValue();
        $promise = new Promise(function () use ($values, $referencedId): ?int {
            try {
                if ($referencedId === self::VALUE_PROMISE) {
                    $model = $this->handler->createFromValues($values);
                    $this->setValue($model, true);
                    $this->modelCreated = true;
                    return $model->getPrimary();
                } elseif ($referencedId) {
                    $model = $this->service->findByPrimary($referencedId);
                    $this->handler->update($model, (array)$values);
// reload the model (this is workaround to avoid caching of empty but newly created referenced/related models)
                    $model = $this->service->findByPrimary($model->getPrimary());
                    $this->setValue($model, true);
                    return $referencedId;
                } else {
                    $this->setValue(null, true);
                    return null;
                }
            } catch (ModelDataConflictException $exception) {
                $this->referencedContainer->setConflicts($exception->getConflicts());
                $this->addError($exception->getMessage());
                $this->rollback();
                throw $exception;
            } catch (ExistingPaymentException $exception) {
                $this->addError($exception->getMessage());
                $this->rollback();
                throw $exception;
            }
        });
        //$referencedId = $this->getValue();
        $this->setValue($referencedId);
        $this->promise = $promise;
    }

    public function invalidateFormGroup(): void
    {
        $form = $this->getForm();
        /** @var Presenter $presenter */
        $presenter = $form->lookup(Presenter::class);
        if ($presenter->isAjax()) {
            /** @var Control $control */
            $control = $form->getParent();
            $control->redrawControl(FormControl::SNIPPET_MAIN);
            $control->getTemplate()->mainContainer = $this->parent;
            $control->getTemplate()->level = 2;
            $payload = $presenter->getPayload();
            $payload->{self::JSON_DATA} = (object)[
                'id' => $this->getHtmlId(),
                'value' => $this->getValue(),
            ];
        }
    }

    protected function setModel(?Model $model, ReferencedIdMode $mode): void
    {
        $this->referencedContainer->setModel($model, $mode);
    }
}
