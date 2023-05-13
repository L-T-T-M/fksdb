<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\Events;

use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Controls\Schedule\ExistingPaymentException;
use FKSDB\Components\Forms\Controls\Schedule\FullCapacityException;
use FKSDB\Models\Events\EventDispatchFactory;
use FKSDB\Models\Events\Exceptions\MachineExecutionException;
use FKSDB\Models\Events\Exceptions\SubmitProcessingException;
use FKSDB\Models\Events\Model\ApplicationHandlerException;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Services\Exceptions\DuplicateApplicationException;
use FKSDB\Models\Persons\ModelDataConflictException;
use FKSDB\Models\Transitions\Machine\EventParticipantMachine;
use FKSDB\Models\Transitions\Machine\Machine;
use FKSDB\Models\Transitions\Transition\Transition;
use FKSDB\Models\Utils\FormUtils;
use FKSDB\Modules\Core\AuthenticatedPresenter;
use FKSDB\Modules\Core\BasePresenter;
use FKSDB\Modules\PublicModule\ApplicationPresenter;
use Fykosak\Utils\BaseComponent\BaseComponent;
use Fykosak\Utils\Logging\Message;
use Nette\Database\Connection;
use Nette\DI\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\InvalidStateException;
use Tracy\Debugger;

/**
 * @method AuthenticatedPresenter|BasePresenter getPresenter($need = true)
 */
class ApplicationComponent extends BaseComponent
{
    private BaseHolder $holder;
    private string $templateFile;
    private Connection $connection;
    private EventDispatchFactory $eventDispatchFactory;

    public function __construct(Container $container, BaseHolder $holder)
    {
        parent::__construct($container);
        $this->holder = $holder;
    }

    public function inject(Connection $connection, EventDispatchFactory $eventDispatchFactory): void
    {
        $this->eventDispatchFactory = $eventDispatchFactory;
        $this->connection = $connection;
    }

    public function getMachine(): EventParticipantMachine
    {
        static $machine;
        if (!isset($machine)) {
            $machine = $this->eventDispatchFactory->getParticipantMachine($this->holder->event);
        }
        return $machine;
    }

    private function getTemplateFile(): string
    {
        $template = $this->eventDispatchFactory->getFormLayout($this->holder->event);
        if (stripos($template, '.latte') !== false) {
            return $template;
        } else {
            return __DIR__ . DIRECTORY_SEPARATOR . "layout.application.$template.latte";
        }
    }

    final public function render(): void
    {
        $this->renderForm();
    }

    final public function renderForm(): void
    {
        if (!$this->templateFile) {
            throw new InvalidStateException('Must set template for the application form.');
        }
        $this->template->holder = $this->holder;
        $this->template->render($this->getTemplateFile());
    }

    /**
     * @throws BadTypeException
     */
    protected function createComponentForm(): FormControl
    {
        $result = new FormControl($this->getContext());
        $form = $result->getForm();

        $container = $this->holder->createFormContainer();
        $form->addComponent($container, 'participant');
        /*
         * Create save (no transition) button
         */
        $saveSubmit = null;
        if ($this->canEdit()) {
            $saveSubmit = $form->addSubmit('save', _('Save'));
            $saveSubmit->onClick[] = fn(SubmitButton $button) => $this->handleSubmit($button->getForm());
        }

        /*
         * Create transition buttons
         */
        $transitionSubmit = null;

        foreach (
            $this->getMachine()->getAvailableTransitions(
                $this->holder,
                $this->holder->getModelState()
            ) as $transition
        ) {
            $submit = $form->addSubmit($transition->getId(), $transition->getLabel());

            if (!$transition->getValidation()) {
                $submit->setValidationScope([]);
            }

            $submit->onClick[] = fn(SubmitButton $button) => $this->handleSubmit(
                $button->getForm(),
                $transition
            );

            if ($transition->isCreating()) {
                $transitionSubmit = $submit;
            }

            $submit->getControlPrototype()->addAttributes(
                ['class' => 'btn btn-outline-' . $transition->behaviorType->value]
            );
        }

        /*
         * Create cancel button
         */
        $cancelSubmit = $form->addSubmit('cancel', _('Cancel'));
        $cancelSubmit->getControlPrototype()->addAttributes(['class' => 'btn btn-outline-warning']);
        $cancelSubmit->setValidationScope([]);
        $cancelSubmit->onClick[] = fn() => $this->finalRedirect();

        /*
         * Custom adjustments
         */
        $this->holder->adjustForm($form);
        $form->getElementPrototype()->data['submit-on'] = 'enter';
        if ($saveSubmit) {
            $saveSubmit->getControlPrototype()->data['submit-on'] = 'this';
        } elseif ($transitionSubmit) {
            $transitionSubmit->getControlPrototype()->data['submit-on'] = 'this';
        }

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function handleSubmit(Form $form, ?Transition $transition = null): void
    {
        try {
            if (!$transition || $transition->getValidation()) {
                try {
                    if (!$this->connection->getPdo()->inTransaction()) {
                        $this->connection->beginTransaction();
                    }
                    $values = FormUtils::emptyStrToNull($form->getValues());
                    Debugger::log(json_encode((array)$values), 'app-form');
                    $this->holder->processFormValues($values);

                    if ($transition) {
                        $state = $this->holder->getModelState();
                        $transition = $this->getMachine()->getTransitionByStates($state, $transition->target);
                    }
                    if (isset($values['participant'])) {
                        $this->holder->data += (array)$values['participant'];
                    }

                    if ($transition) {
                        $this->getMachine()->execute2($transition, $this->holder);
                    }
                    $this->holder->saveModel();
                    if ($transition) {
                        $transition->callAfterExecute($this->holder);
                    }

                    if ($transition && $transition->isCreating()) {
                        $this->getPresenter()->flashMessage(
                            sprintf(_('Application "%s" created.'), (string)$this->holder->getModel()),
                            Message::LVL_SUCCESS
                        );
                    } elseif ($transition) {
                        $this->getPresenter()->flashMessage(
                            sprintf(
                                _('State of application "%s" changed.'),
                                $this->holder->getModel()->person->getFullName()
                            ),
                            Message::LVL_INFO
                        );
                    }
                    $this->getPresenter()->flashMessage(
                        sprintf(_('Application "%s" saved.'), (string)$this->holder->getModel()),
                        Message::LVL_SUCCESS
                    );
                    if ($this->connection->getPdo()->inTransaction()) {
                        $this->connection->commit();
                    }
                } catch (
                ModelDataConflictException |
                DuplicateApplicationException |
                MachineExecutionException |
                SubmitProcessingException |
                FullCapacityException |
                ExistingPaymentException $exception
                ) {
                    $this->getPresenter()->flashMessage($exception->getMessage(), Message::LVL_ERROR);
                    /** @var ReferencedId $referencedId */
                    foreach ($form->getComponents(true, ReferencedId::class) as $referencedId) {
                        $referencedId->rollback();
                    }
                    $this->connection->rollBack();
                    throw new ApplicationHandlerException(_('Error while saving the application.'), 0, $exception);
                }
            } else {
                $this->getMachine()->execute($transition, $this->holder);
                $this->getPresenter()->flashMessage(_('Transition successful'), Message::LVL_SUCCESS);
            }
            $this->finalRedirect();
        } catch (ApplicationHandlerException $exception) {
            /* handled elsewhere, here it's to just prevent redirect */
        }
    }

    private function canEdit(): bool
    {
        return $this->holder->getModelState() != Machine::STATE_INIT && $this->holder->isModifiable();
    }

    private function finalRedirect(): void
    {
        $this->getPresenter()->redirect(
            'this',
            [
                'eventId' => $this->holder->event->event_id,
                'id' => $this->holder->getModel()->getPrimary(),
                ApplicationPresenter::PARAM_AFTER => true,
            ]
        );
    }
}
