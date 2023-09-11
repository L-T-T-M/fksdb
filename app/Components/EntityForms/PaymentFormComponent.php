<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms;

use FKSDB\Components\Forms\Containers\PersonPaymentContainer;
use FKSDB\Components\Forms\Controls\Autocomplete\PersonProvider;
use FKSDB\Components\Forms\Factories\PersonFactory;
use FKSDB\Components\Forms\Factories\SingleReflectionFormFactory;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\Exceptions\NotImplementedException;
use FKSDB\Models\ORM\Models\EventModel;
use FKSDB\Models\ORM\Models\PaymentModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\OmittedControlException;
use FKSDB\Models\ORM\Services\PaymentService;
use FKSDB\Models\ORM\Services\Schedule\SchedulePaymentService;
use FKSDB\Models\Submits\StorageException;
use FKSDB\Models\Transitions\Machine\PaymentMachine;
use FKSDB\Models\Transitions\Transition\UnavailableTransitionsException;
use FKSDB\Modules\Core\Language;
use Fykosak\NetteORM\Exceptions\ModelException;
use Nette\DI\Container;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;

/**
 * @phpstan-extends EntityFormComponent<PaymentModel>
 */
class PaymentFormComponent extends EntityFormComponent
{
    private PersonFactory $personFactory;
    private PersonProvider $personProvider;
    private bool $isOrganizer;
    private PaymentMachine $machine;
    private PaymentService $paymentService;
    private SchedulePaymentService $schedulePaymentService;
    private SingleReflectionFormFactory $reflectionFormFactory;
    private EventModel $event;
    private PersonModel $loggedPerson;

    public function __construct(
        Container $container,
        EventModel $event,
        PersonModel $loggedPerson,
        bool $isOrganizer,
        PaymentMachine $machine,
        ?PaymentModel $model
    ) {
        parent::__construct($container, $model);
        $this->machine = $machine;
        $this->isOrganizer = $isOrganizer;
        $this->event = $event;
        $this->loggedPerson = $loggedPerson;
    }

    final public function injectPrimary(
        PaymentService $paymentService,
        PersonFactory $personFactory,
        PersonProvider $personProvider,
        SchedulePaymentService $schedulePaymentService,
        SingleReflectionFormFactory $reflectionFormFactory
    ): void {
        $this->paymentService = $paymentService;
        $this->personFactory = $personFactory;
        $this->personProvider = $personProvider;
        $this->schedulePaymentService = $schedulePaymentService;
        $this->reflectionFormFactory = $reflectionFormFactory;
    }

    protected function appendSubmitButton(Form $form): SubmitButton
    {
        return $form->addSubmit('submit', isset($this->model) ? _('Save payment') : _('Proceed to summary'));
    }

    /**
     * @throws BadTypeException
     * @throws OmittedControlException
     * @throws NotImplementedException
     * @throws \Exception
     */
    protected function configureForm(Form $form): void
    {
        if ($this->isOrganizer) {
            $form->addComponent(
                $this->personFactory->createPersonSelect(true, _('Person'), $this->personProvider),
                'person_id'
            );
        }
        /** @var SelectBox $currencyField */
        $currencyField = $this->reflectionFormFactory->createField('payment', 'currency');
        // $currencyField->setItems($this->machine->priceCalculator->getAllowedCurrencies());
        $currencyField->setRequired(_('Please select currency'));
        $form->addComponent($currencyField, 'currency');
        $form->addComponent(
            new PersonPaymentContainer(
                $this->getContext(),
                $this->event,
                $this->loggedPerson,
                $this->isOrganizer,
                $this->model
            ),
            'items'
        );
    }

    /**
     * @throws UnavailableTransitionsException
     * @throws ModelException
     * @throws StorageException
     * @throws \Throwable
     */
    protected function handleFormSuccess(Form $form): void
    {
        /** @phpstan-var array{currency:string,person_id:int,items:array<array<int,bool>>} $values */
        $values = $form->getValues('array');
        $connection = $this->paymentService->explorer->getConnection();
        $connection->beginTransaction();
        try {
            $model = $this->paymentService->storeModel(
                [
                    'event_id' => $this->event->event_id,
                    'currency' => $values['currency'],
                    'person_id' => $this->isOrganizer ? $values['person_id'] : $this->loggedPerson->person_id,
                ],
                $this->model
            );
            $this->schedulePaymentService->storeItems(
                (array)$values['items'],
                $model,
                Language::from($this->translator->lang)
            );
            if (!isset($this->model)) {
                $holder = $this->machine->createHolder($model);
                $transition = $this->machine->getImplicitTransition($holder);
                $this->machine->execute($transition, $holder);
                $model = $holder->getModel();
            }
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
        $connection->commit();
        $this->getPresenter()->flashMessage(
            !isset($this->model)
                ? _('Payment has been created.')
                : _('Payment has been updated.')
        );
        $this->getPresenter()->redirect('detail', ['id' => $model->payment_id]);
    }

    protected function setDefaults(Form $form): void
    {
        if (isset($this->model)) {
            $form->setDefaults($this->model->toArray());
            /** @var PersonPaymentContainer $itemContainer */
            $itemContainer = $form->getComponent('items');
            $itemContainer->setPayment($this->model);
        }
    }
}
