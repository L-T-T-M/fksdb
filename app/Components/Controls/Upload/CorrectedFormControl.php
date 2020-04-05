<?php

namespace FKSDB\Components\Controls\Upload;

use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Logging\ILogger;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

/**
 * Class CorrectedFormControl
 * @package FKSDB\Components\Controls\Upload
 */
class CorrectedFormControl extends SeriesTableControl {

    /**
     * @inheritDoc
     */
    public function render() {
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'CorrectedFormControl.latte');
        $this->template->render();
    }

    /**
     * @return FormControl
     * @throws BadRequestException
     */
    public function createComponentForm(): FormControl {
        $control = new FormControl();
        $form = $control->getForm();
        $form->addTextArea('submits', _('Submits'))->setOption('description', _('Comma separated submitIDs'));
        $form->addSubmit('submit', _('Save'));
        $form->onSuccess[] = function (Form $form) {
            $this->handleSuccess($form);
        };
        return $control;
    }

    /**
     * @param Form $form
     * @throws AbortException
     */
    private function handleSuccess(Form $form) {
        $values = $form->getValues();
        $ids = [];
        foreach (explode(',', $values['submits']) as $value) {
            $ids[] = trim($value);
        }
        try {
            $updated = $this->seriesTable->getSubmits()->where('submit_id', $ids)->update(['corrected' => 1]);
            $this->flashMessage(sprintf(_('Updated %d submits'), $updated), ILogger::INFO);
        } catch (\PDOException $exception) {
            $this->flashMessage(_('Error during updating'), ILogger::ERROR);
        }
        $this->getPresenter()->redirect('this');
    }
}
