<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms;

use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Factories\SchoolFactory;
use FKSDB\Models\ORM\Models\SchoolModel;
use FKSDB\Models\ORM\Services\SchoolService;
use FKSDB\Models\Utils\FormUtils;
use Fykosak\Utils\Logging\Message;
use Nette\Forms\Form;

/**
 * @phpstan-extends EntityFormComponent<SchoolModel>
 */
class SchoolFormComponent extends EntityFormComponent
{
    public const CONT_ADDRESS = 'address';
    public const CONT_SCHOOL = 'school';

    private SchoolService $schoolService;
    private SchoolFactory $schoolFactory;

    final public function injectPrimary(
        SchoolFactory $schoolFactory,
        SchoolService $schoolService
    ): void {
        $this->schoolFactory = $schoolFactory;
        $this->schoolService = $schoolService;
    }

    protected function configureForm(Form $form): void
    {
        $form->addComponent($this->schoolFactory->createContainer(), self::CONT_SCHOOL);
    }

    protected function getTemplatePath(): string
    {
        return __DIR__ . '/school.latte';
    }

    /**
     * @throws \PDOException
     */
    protected function handleFormSuccess(Form $form): void
    {
        /** @phpstan-var array{school:array{
         *     name_full:string,
         *     name:string,
         *     name_abbrev:string,
         *     description:string,
         *     email:string,
         *     ic:string,
         *     izo:string,
         *     active:bool,
         *     note:string,
         *     address_id:int,
         * }} $values
         */
        $values = $form->getValues('array');
        $schoolData = FormUtils::emptyStrToNull2($values[self::CONT_SCHOOL]);
        $this->schoolService->storeModel($schoolData, $this->model);
        $this->getPresenter()->flashMessage(
            isset($this->model) ? _('School has been updated') : _('School has been created'),
            Message::LVL_SUCCESS
        );
        $this->getPresenter()->redirect('list');
    }

    protected function setDefaults(Form $form): void
    {
        if (isset($this->model)) {
            $form->setDefaults([self::CONT_SCHOOL => $this->model->toArray()]);
        } else {
            $form->setDefaults([self::CONT_SCHOOL => ['address_id' => ReferencedId::VALUE_PROMISE]]);
        }
    }
}
