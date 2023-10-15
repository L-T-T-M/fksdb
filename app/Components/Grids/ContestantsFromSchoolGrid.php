<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Components\Grids\Components\BaseGrid;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\ContestantModel;
use FKSDB\Models\ORM\Models\SchoolModel;
use FKSDB\Models\ORM\Services\ContestantService;
use Fykosak\NetteORM\TypedSelection;
use Nette\DI\Container;

/**
 * @phpstan-extends BaseGrid<ContestantModel,array{}>
 */
final class ContestantsFromSchoolGrid extends BaseGrid
{
    private SchoolModel $school;
    private ContestantService $service;

    public function __construct(SchoolModel $school, Container $container)
    {
        parent::__construct($container);
        $this->school = $school;
    }

    /**
     * @phpstan-return TypedSelection<ContestantModel>
     */
    protected function getModels(): TypedSelection
    {
        return $this->service->getTable()->where(
            'person:person_history.school_id',
            $this->school->school_id
        );
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(): void
    {
        $this->paginate = false;
        $this->filtered = false;
        $this->counter = true;
        $this->addSimpleReferencedColumns([
            '@person.full_name',
            '@contestant.year',
            '@person_history.study_year_new',
            '@contest.contest',
        ]);
        $this->addPresenterButton(
            ':Organizer:Contestant:edit',
            'edit',
            _('button.edit'),
            false,
            ['id' => 'contestant_id']
        );
        $this->addPresenterButton(
            ':Organizer:Contestant:detail',
            'detail',
            _('button.detail'),
            false,
            ['id' => 'contestant_id']
        );
    }

    public function inject(ContestantService $service): void
    {
        $this->service = $service;
    }
}
