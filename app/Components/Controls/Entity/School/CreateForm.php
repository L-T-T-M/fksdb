<?php

namespace FKSDB\Components\Controls\Entity\School;

use FKSDB\Exceptions\ModelException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Tracy\Debugger;

/**
 * Class CreateForm
 * @author Michal Červeňák <miso@fykos.cz>
 */
class CreateForm extends AbstractForm {
    /**
     * @param Form $form
     * @return void
     */
    protected function configureForm(Form $form) {
        parent::configureForm($form);
        $form->addSubmit('send', _('Create'));
        $form->onSuccess[] = function (Form $form) {
            $this->handleCreateFormSuccess($form);
        };
    }

    /**
     * @param Form $form
     * @throws AbortException
     */
    private function handleCreateFormSuccess(Form $form) {
        $connection = $this->serviceSchool->getConnection();
        $values = $form->getValues();

        try {
            $connection->beginTransaction();
            /* Address */
            $data = \FormUtils::emptyStrToNull($values[self::CONT_ADDRESS]);
            $address = $this->serviceAddress->createNewModel($data);
            /* School */
            $data = \FormUtils::emptyStrToNull($values[self::CONT_SCHOOL]);
            $data['address_id'] = $address->address_id;
            $this->serviceSchool->createNewModel($data);
            /* Finalize */
            $connection->commit();

            $this->getPresenter()->flashMessage(_('Škola založena'), \BasePresenter::FLASH_SUCCESS);

            $this->getPresenter()->redirect('list'); // if there's no backlink
        } catch (ModelException $exception) {
            $connection->rollBack();
            Debugger::log($exception, Debugger::ERROR);
            $this->getPresenter()->flashMessage(_('Chyba při zakládání školy.'), \BasePresenter::FLASH_ERROR);
        }
    }
}
