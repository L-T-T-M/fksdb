<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms\Spam;

use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Models\Events\Exceptions\ConfigurationNotFoundException;
use FKSDB\Models\ORM\Models\ContestYearModel;
use FKSDB\Models\Utils\CSVParser;
use Fykosak\Utils\BaseComponent\BaseComponent;
use Fykosak\Utils\Logging\Message;
use Nette\Database\Connection;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Http\FileUpload;

final class SpamPersonImportComponent extends BaseComponent
{
    private Connection $connection;
    private ContestYearModel $contestYear;

    public function __construct(ContestYearModel $contestYear, Container $container)
    {
        parent::__construct($container);
        $this->contestYear = $contestYear;
    }

    public function inject(Connection $connection): void
    {
        $this->connection = $connection;
    }

    protected function createComponentFormImport(): FormControl
    {
        $control = new FormControl($this->getContext());
        $form = $control->getForm();

        $form->addUpload('file', _('File with people'))
            ->addRule(Form::FILLED);

        $form->addSubmit('import', _('Import'));

        $form->onSuccess[] = fn (Form $form) => $this->handleFormImport($form);
        return $control;
    }

    final public function render(): void
    {
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'importLayout.latte');
    }

    private function handleFormImport(Form $form): void
    {
        /** @phpstan-var array{file:FileUpload} $values */
        $values = $form->getValues();
        try {
            // process form values
            $filename = $values['file']->getTemporaryFile();
            $parser = new CSVParser($filename, CSVParser::INDEX_FROM_HEADER);
            $handler = new Handler($this->contestYear, $this->container);
            $this->connection->beginTransaction();
            foreach ($parser as $data) {
                foreach (['other_name', 'family_name', 'school_label', 'study_year'] as $requiredField) {
                    if (!array_key_exists($requiredField, $data)) {
                        $this->getPresenter()->flashMessage(
                            sprintf(_('Missing required column %s.'), $requiredField),
                            Message::LVL_ERROR
                        );
                        throw new MissingColumnException();
                    }
                }

                $transformedData = [
                    'other_name' => $data['other_name'],
                    'family_name' => $data['family_name'],
                    'school_label_key' => $data['school_label'],
                    'study_year_new' => $data['study_year'],
                ];

                $handler->storeSchool($transformedData['school_label_key'], null);
                $handler->storePerson($transformedData, null);
            }
            $this->connection->commit();
            $this->getPresenter()->flashMessage(_('Import successful.'), Message::LVL_SUCCESS);
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            $this->getPresenter()->flashMessage(_('Import completed with errors.'), Message::LVL_WARNING);
        }
        $this->getPresenter()->redirect('this');
    }
}
