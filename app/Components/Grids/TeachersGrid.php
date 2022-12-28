<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Services\TeacherService;
use FKSDB\Models\SQL\SearchableDataSource;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use NiftyGrid\DataSource\IDataSource;
use NiftyGrid\DataSource\NDataSource;

class TeachersGrid extends EntityGrid
{

    public function __construct(Container $container)
    {
        parent::__construct($container, TeacherService::class, [
            'person.full_name',
            'teacher.note',
            'teacher.state',
            'teacher.since',
            'teacher.until',
            'teacher.number_brochures',
            'school.school',
        ]);
    }

    protected function getData(): IDataSource
    {
        $dataSource = new NDataSource(
            $this->service->getTable()->select('teacher.*, person.family_name AS display_name')
        );
        /*    $dataSource->setFilterCallback(function (Selection $table, array $value) {
                $tokens = preg_split('/\s+/', $value['term']);
                foreach ($tokens as $token) {
                    $table->where('CONCAT(person.family_name, person.other_name) LIKE CONCAT(\'%\', ? , \'%\')', $token);
                }
            });*/
        return $dataSource;
    }

    /**
     * @throws BadTypeException
     */
    protected function configure(Presenter $presenter): void
    {
        parent::configure($presenter);
        $this->addORMLink('teacher.edit');
        $this->addORMLink('teacher.detail');
    }
}
