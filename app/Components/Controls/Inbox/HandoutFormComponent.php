<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\Inbox;

use Fykosak\Utils\BaseComponent\BaseComponent;
use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Forms\Controls\Autocomplete\PersonProvider;
use FKSDB\Components\Forms\Factories\PersonFactory;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\DbNames;
use Fykosak\NetteORM\Exceptions\ModelException;
use Fykosak\Utils\Logging\Message;
use FKSDB\Models\ORM\Models\TaskModel;
use FKSDB\Models\ORM\Models\TaskContributionModel;
use FKSDB\Models\ORM\Services\PersonService;
use FKSDB\Models\ORM\Services\TaskContributionService;
use FKSDB\Models\Submits\SeriesTable;
use Nette\Application\UI\Form;
use Nette\DI\Container;

class HandoutFormComponent extends BaseComponent
{
    public const TASK_PREFIX = 'task';
    private PersonService $personService;
    private SeriesTable $seriesTable;
    private TaskContributionService $taskContributionService;
    private PersonFactory $personFactory;

    public function __construct(Container $container, SeriesTable $seriesTable)
    {
        parent::__construct($container);
        $this->seriesTable = $seriesTable;
    }

    final public function injectPrimary(
        PersonFactory $personFactory,
        PersonService $personService,
        TaskContributionService $taskContributionService
    ): void {
        $this->personFactory = $personFactory;
        $this->personService = $personService;
        $this->taskContributionService = $taskContributionService;
    }

    /**
     * @throws BadTypeException
     */
    protected function createComponentForm(): FormControl
    {
        $formControl = new FormControl($this->getContext());
        $form = $formControl->getForm();
        $orgProvider = new PersonProvider($this->personService);
        $orgProvider->filterOrgs($this->seriesTable->contestYear->contest);
        /** @var TaskModel $task */
        foreach ($this->seriesTable->getTasks() as $task) {
            $control = $this->personFactory->createPersonSelect(false, $task->getFQName(), $orgProvider);
            $control->setMultiSelect(true);
            $form->addComponent($control, self::TASK_PREFIX . $task->task_id);
        }

        $form->addSubmit('save', _('Save'));
        $form->onSuccess[] = fn(Form $form) => $this->handleFormSuccess($form);

        return $formControl;
    }

    /**
     * @throws ModelException
     */
    public function handleFormSuccess(Form $form): void
    {
        $values = $form->getValues();
        $connection = $this->taskContributionService->explorer->getConnection();
        $connection->beginTransaction();
        /** @var TaskModel $task */
        foreach ($this->seriesTable->getTasks() as $task) {
            $task->related(DbNames::TAB_TASK_CONTRIBUTION)->where([
                'type' => TaskContributionModel::TYPE_GRADE,
            ])->delete();
            $key = self::TASK_PREFIX . $task->task_id;
            foreach ($values[$key] as $personId) {
                $data = [
                    'task_id' => $task->task_id,
                    'person_id' => $personId,
                    'type' => TaskContributionModel::TYPE_GRADE,
                ];
                $this->taskContributionService->createNewModel($data);
            }
        }

        $connection->commit();

        $this->getPresenter()->flashMessage(_('Handout saved.'), Message::LVL_SUCCESS);
        $this->getPresenter()->redirect('this');
    }

    final public function render(): void
    {
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'layout.handout.latte');
    }

    /**
     * @throws BadTypeException
     */
    public function setDefaults(): void
    {
        $taskIds = [];
        /** @var TaskModel $task */
        foreach ($this->seriesTable->getTasks() as $task) {
            $taskIds[] = $task->task_id;
        }
        $contributions = $this->taskContributionService->getTable()->where([
            'type' => TaskContributionModel::TYPE_GRADE,
            'task_id' => $taskIds,
        ]);

        $values = [];
        /** @var TaskContributionModel $contribution */
        foreach ($contributions as $contribution) {
            $taskId = $contribution->task_id;
            $personId = $contribution->person_id;
            $key = self::TASK_PREFIX . $taskId;
            if (!isset($values[$key])) {
                $values[$key] = [];
            }
            $values[$key][] = $personId;
        }
        /** @var FormControl $control */
        $control = $this->getComponent('form');
        $control->getForm()->setDefaults($values);
    }
}
