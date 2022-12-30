<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\SchoolModel;
use FKSDB\Models\ORM\Services\SchoolService;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;
use Nette\Utils\Html;

class SchoolsGrid extends FilterBaseGrid
{
    private SchoolService $service;

    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function injectService(SchoolService $service): void
    {
        $this->service = $service;
    }

    protected function getFilterCallback(): void
    {
        $tokens = preg_split('/\s+/', $this->searchTerm['term']);
        foreach ($tokens as $token) {
            $this->data->where('name_full LIKE CONCAT(\'%\', ? , \'%\')', $token);
        }
    }

    /**
     * @throws BadTypeException
     */
    protected function configure(): void
    {
        $this->data = $this->service->getTable();
        $this->addColumn('name', new Title(null, _('Name')), fn(SchoolModel $model) => $model->name);
        $this->addColumn('city', new Title(null, _('City')), fn(SchoolModel $school): string => $school->address->city);
        $this->addColumn(
            'active',
            new Title(null, _('Active?')),
            fn(SchoolModel $row): Html => Html::el('span')
                ->addAttributes(['class' => ('badge ' . ($row->active ? 'bg-success' : 'bg-danger'))])
                ->addText(($row->active))
        );

        $this->addORMLink('school.edit');
        $this->addORMLink('school.detail');
    }
}
