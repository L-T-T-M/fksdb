<?php

namespace FKSDB\Components\Controls\Fyziklani;

use BasePresenter;
use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Factories\FyziklaniFactory;
use FKSDB\model\Fyziklani\ClosedSubmittingException;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniSubmit;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniTask;
use FKSDB\ORM\Models\Fyziklani\ModelFyziklaniTeam;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Services\Fyziklani\ServiceFyziklaniTask;
use FKSDB\ORM\Services\Fyziklani\ServiceFyziklaniTeam;
use Nette\Application\BadRequestException;
use Nette\Application\UI\BadSignalException;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Nette\Templating\FileTemplate;
use function get_class;
use function sprintf;

/**
 * Class CloseTeamControl
 * @package FKSDB\Components\Controls\Fyziklani
 * @property FileTemplate $template
 */
class CloseTeamControl extends Control {
    /**
     * @var ServiceFyziklaniTeam
     */
    private $serviceFyziklaniTeam;
    /**
     * @var ModelEvent
     */
    private $event;
    /**
     * @var ITranslator
     */
    private $translator;
    /**
     * @var ModelFyziklaniTeam
     */
    private $team;
    /**
     * @var ServiceFyziklaniTask
     */
    private $serviceFyziklaniTask;
    /**
     * @var FyziklaniFactory
     */
    private $fyziklaniFactory;

    /**
     * CloseTeamControl constructor.
     * @param ModelEvent $event
     * @param ServiceFyziklaniTeam $serviceFyziklaniTeam
     * @param ITranslator $translator
     * @param ServiceFyziklaniTask $serviceFyziklaniTask
     * @param FyziklaniFactory $fyziklaniFactory
     */
    public function __construct(
        ModelEvent $event,
        ServiceFyziklaniTeam $serviceFyziklaniTeam,
        ITranslator $translator,
        ServiceFyziklaniTask $serviceFyziklaniTask,
        FyziklaniFactory $fyziklaniFactory
    ) {
        parent::__construct();
        $this->event = $event;
        $this->serviceFyziklaniTeam = $serviceFyziklaniTeam;
        $this->translator = $translator;
        $this->serviceFyziklaniTask = $serviceFyziklaniTask;
        $this->fyziklaniFactory = $fyziklaniFactory;
    }

    /**
     * @param ModelFyziklaniTeam $team
     * @throws BadSignalException
     * @throws ClosedSubmittingException
     * @throws BadRequestException
     */
    public function setTeam(ModelFyziklaniTeam $team) {
        $this->team = $team;
        if (!$team->hasOpenSubmitting()) {
            throw new ClosedSubmittingException($this->team);
        }
        $this->getFormControl()->getForm()->setDefaults(['next_task' => $this->getNextTask()]);
    }

    /**
     * @return FormControl
     * @throws BadSignalException
     */
    public function getFormControl(): FormControl {
        $control = $this->getComponent('form');
        if ($control instanceof FormControl) {
            return $control;
        }
        throw new BadSignalException('Expected FormControl got ' . get_class($control));
    }


    /**
     * @return FormControl
     * @throws BadRequestException
     */
    protected function createComponentForm(): FormControl {
        $control = new FormControl();
        $form = $control->getForm();
        $form->addText('next_task', _('Úloha u vydavačů'))
            ->setDisabled();
        $form->addSubmit('send', 'Potvrdit správnost');
        $form->onSuccess[] = function () {
            $this->formSucceeded();
        };
        return $control;
    }

    private function formSucceeded() {
        $connection = $this->serviceFyziklaniTeam->getConnection();
        $connection->beginTransaction();
        $submits = $this->team->getSubmits();
        $sum = 0;
        foreach ($submits as $row) {
            $submit = ModelFyziklaniSubmit::createFromActiveRow($row);
            $sum += $submit->points;
        }
        $this->serviceFyziklaniTeam->updateModel($this->team, ['points' => $sum]);
        $this->serviceFyziklaniTeam->save($this->team);
        $connection->commit();
        $this->getPresenter()->flashMessage(sprintf(_('Team %s has successfully closed submitting, with total %d points.'), $this->team->name, $sum), BasePresenter::FLASH_SUCCESS);
    }

    public function render() {
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'CloseTeamControl.latte');
        $this->template->setTranslator($this->translator);
        $this->template->render();
    }

    /**
     * @return string
     */
    private function getNextTask(): string {
        $submits = count($this->team->getSubmits());

        $tasksOnBoard = $this->event->getFyziklaniGameSetup()->tasks_on_board;
        /**
         * @var ModelFyziklaniTask $nextTask
         */
        $nextTask = $this->serviceFyziklaniTask->findAll($this->event)->order('label')->limit(1, $submits + $tasksOnBoard)->fetch();
        return ($nextTask) ? $nextTask->label : '';
    }

}
