<?php

declare(strict_types=1);

namespace FKSDB\Models\Events\Model;

use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Controls\Schedule\ExistingPaymentException;
use FKSDB\Components\Forms\Controls\Schedule\FullCapacityException;
use FKSDB\Models\Events\Exceptions\MachineExecutionException;
use FKSDB\Models\Events\Machine\BaseMachine;
use FKSDB\Models\ORM\Services\Exceptions\DuplicateApplicationException;
use FKSDB\Models\Events\Machine\Transition;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Events\Exceptions\SubmitProcessingException;
use FKSDB\Models\Persons\ModelDataConflictException;
use FKSDB\Models\Events\EventDispatchFactory;
use Fykosak\Utils\Logging\Logger;
use Fykosak\Utils\Logging\Message;
use FKSDB\Models\ORM\Models\EventModel;
use FKSDB\Models\Transitions\Transition\UnavailableTransitionException;
use FKSDB\Models\Utils\FormUtils;
use Nette\Database\Connection;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class ApplicationHandler
{

    public const ERROR_ROLLBACK = 'rollback';
    public const ERROR_SKIP = 'skip';
    public const STATE_TRANSITION = 'transition';
    public const STATE_OVERWRITE = 'overwrite';
    private EventModel $event;

    private Logger $logger;

    private string $errorMode = self::ERROR_ROLLBACK;
    private Connection $connection;
    private EventDispatchFactory $eventDispatchFactory;

    public function __construct(EventModel $event, Logger $logger, Container $container)
    {
        $this->event = $event;
        $this->logger = $logger;
        $container->callInjects($this);
    }

    public function injectPrimary(Connection $connection, EventDispatchFactory $eventDispatchFactory): void
    {
        $this->eventDispatchFactory = $eventDispatchFactory;
        $this->connection = $connection;
    }

    public function setErrorMode(string $errorMode): void
    {
        $this->errorMode = $errorMode;
    }

    public function getMachine(): BaseMachine
    {
        static $machine;
        if (!isset($machine)) {
            $machine = $this->eventDispatchFactory->getEventMachine($this->event);
        }
        return $machine;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    final public function store(BaseHolder $holder, ArrayHash $data): void
    {
        $this->innerStoreAndExecute($holder, $data, null, null, self::STATE_OVERWRITE);
    }

    final public function storeAndExecuteValues(BaseHolder $holder, ArrayHash $data): void
    {
        $this->innerStoreAndExecute($holder, $data, null, null, self::STATE_TRANSITION);
    }

    final public function storeAndExecuteForm(
        BaseHolder $holder,
        Form $form,
        ?string $explicitTransitionName = null
    ): void {
        $this->innerStoreAndExecute($holder, null, $form, $explicitTransitionName, self::STATE_TRANSITION);
    }

    final public function onlyExecute(BaseHolder $holder, string $explicitTransitionName): void
    {
        try {
            $this->beginTransaction();
            $transition = $this->getMachine()->getTransition($explicitTransitionName);
            if (!$transition->matches($holder->getModelState())) {
                throw new UnavailableTransitionException($transition, $holder->getModel());
            }

            $this->saveAndExecute($transition, $holder);
        } catch (ModelDataConflictException $exception) {
            $container = $exception->getReferencedId()->referencedContainer;
            $container->setConflicts($exception->getConflicts());

            $message = sprintf(
                _('Some fields of group "%s" don\'t match an existing record.'),
                $container->getOption('label')
            );
            $this->logger->log(new Message($message, Message::LVL_ERROR));
            $this->reRaise($exception);
        } catch (
        DuplicateApplicationException
        | MachineExecutionException
        | SubmitProcessingException
        | FullCapacityException
        | ExistingPaymentException
        | UnavailableTransitionException $exception
        ) {
            $this->logger->log(new Message($exception->getMessage(), Message::LVL_ERROR));
            $this->reRaise($exception);
        }
    }

    private function innerStoreAndExecute(
        BaseHolder $holder,
        ?ArrayHash $data,
        ?Form $form,
        ?string $explicitTransitionName,
        ?string $execute
    ): void {
        try {
            $this->beginTransaction();

            $transition = $this->processData(
                $data,
                $form,
                $explicitTransitionName
                    ? $this->getMachine()->getTransition($explicitTransitionName)
                    : null,
                $holder,
                $execute
            );

            if ($execute === self::STATE_OVERWRITE) {
                if (isset($data[$holder->name][BaseHolder::STATE_COLUMN])) {
                    $holder->setModelState(
                        $data[$holder->name][BaseHolder::STATE_COLUMN]
                    );
                }
            }

            $this->saveAndExecute($transition, $holder);

            if (($data || $form) && (!$transition || !$transition->isTerminating())) {
                $this->logger->log(
                    new Message(
                        sprintf(_('Application "%s" saved.'), (string)$holder->getModel()),
                        Message::LVL_SUCCESS
                    )
                );
            }
        } catch (ModelDataConflictException $exception) {
            $container = $exception->getReferencedId()->referencedContainer;
            $container->setConflicts($exception->getConflicts());
            $message = sprintf(
                _('Some fields of group "%s" don\'t match an existing record.'),
                $container->getOption('label')
            );
            $this->logger->log(new Message($message, Message::LVL_ERROR));
            $this->formRollback($form);
            $this->reRaise($exception);
        } catch (
        DuplicateApplicationException
        | MachineExecutionException
        | SubmitProcessingException
        | FullCapacityException
        | ExistingPaymentException $exception
        ) {
            $this->logger->log(new Message($exception->getMessage(), Message::LVL_ERROR));
            $this->formRollback($form);
            $this->reRaise($exception);
        }
    }

    private function saveAndExecute(?Transition $transition, BaseHolder $holder)
    {
        if ($transition) {
            $transition->execute($holder);
        }
        $holder->saveModel();
        if ($transition) {
            $transition->executed($holder);
        }
        $this->commit();

        if ($transition && $transition->isCreating()) {
            $this->logger->log(
                new Message(
                    sprintf(_('Application "%s" created.'), (string)$holder->getModel()),
                    Message::LVL_SUCCESS
                )
            );
        } elseif ($transition && $transition->isTerminating()) {
            $this->logger->log(new Message(_('Application deleted.'), Message::LVL_SUCCESS));
        } elseif ($transition) {
            $this->logger->log(
                new Message(
                    sprintf(
                        _('State of application "%s" changed.'),
                        (string)$holder->getModel()
                    ),
                    Message::LVL_INFO
                )
            );
        }
    }

    private function processData(
        ?ArrayHash $data,
        ?Form $form,
        ?Transition $transition,
        BaseHolder $holder,
        ?string $execute
    ): ?Transition {
        if ($form) {
            $values = FormUtils::emptyStrToNull($form->getValues());
        } else {
            $values = $data;
        }
        Debugger::log(json_encode((array)$values), 'app-form');
        $newState = null;
        if (isset($values[$holder->name][BaseHolder::STATE_COLUMN])) {
            $newState = $values[$holder->name][BaseHolder::STATE_COLUMN];
        }

        $processState = $holder->processFormValues(
            $values,
            $this->getMachine(),
            $transition,
            $this->logger,
            $form
        );

        $newState = $newState ?: $processState;

        if ($execute == self::STATE_TRANSITION) {
            if ($newState) {
                $state = $holder->getModelState();
                $transition = $this->getMachine()->getTransitionByTarget(
                    $state,
                    $newState
                );
                if (!$transition) {
                    throw new MachineExecutionException(
                        sprintf(
                            _('There is not a transition from state "%s" of machine "%s" to state "%s".'),
                            $this->getMachine()->getStateName($state),
                            $holder->label,
                            $this->getMachine()->getStateName($newState)
                        )
                    );
                }
            }
        }

        if (isset($values[$holder->name])) {
            $holder->data += (array)$values[$holder->name];
        }

        return $transition;
    }

    private function formRollback(?Form $form): void
    {
        if ($form) {
            /** @var ReferencedId $referencedId */
            foreach ($form->getComponents(true, ReferencedId::class) as $referencedId) {
                $referencedId->rollback();
            }
        }
        $this->rollback();
    }

    public function beginTransaction(): void
    {
        if (!$this->connection->getPdo()->inTransaction()) {
            $this->connection->beginTransaction();
        }
    }

    private function rollback(): void
    {
        if ($this->errorMode === self::ERROR_ROLLBACK) {
            $this->connection->rollBack();
        }
    }

    public function commit(bool $final = false): void
    {
        if ($this->connection->getPdo()->inTransaction() && ($this->errorMode == self::ERROR_ROLLBACK || $final)) {
            $this->connection->commit();
        }
    }

    /**
     * @return never|void
     * @throws ApplicationHandlerException
     */
    private function reRaise(\Throwable $e): void
    {
        throw new ApplicationHandlerException(_('Error while saving the application.'), 0, $e);
    }
}
