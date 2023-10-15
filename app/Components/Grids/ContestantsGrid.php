<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Components\Grids\Components\BaseGrid;
use FKSDB\Components\Grids\Components\Renderer\RendererItem;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\ContestantModel;
use FKSDB\Models\ORM\Models\ContestYearModel;
use FKSDB\Models\UI\NotSetBadge;
use Fykosak\NetteORM\TypedGroupedSelection;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

/**
 * @phpstan-extends BaseGrid<ContestantModel,array{}>
 */
final class ContestantsGrid extends BaseGrid
{
    private ContestYearModel $contestYear;

    public function __construct(Container $container, ContestYearModel $contestYear)
    {
        parent::__construct($container);
        $this->contestYear = $contestYear;
    }

    /**
     * @phpstan-return TypedGroupedSelection<ContestantModel>
     */
    protected function getModels(): TypedGroupedSelection
    {
        return $this->contestYear->getContestants()->order('person.other_name ASC');
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
            '@contestant.contest_category',
            '@person_history.study_year_new',
        ]);
        $this->addTableColumn(
            new RendererItem(
                $this->container,
                function (ContestantModel $row) {
                    if (!$row->getPersonHistory()) {
                        return NotSetBadge::getHtml();
                    }
                    return $this->tableReflectionFactory->loadColumnFactory(
                        'school',
                        'school'
                    )->render(
                        $row->getPersonHistory(),
                        1024
                    );
                },
                new Title(null, _('School'))
            ),
            'school_name',
        );

        $this->addPresenterButton('Contestant:edit', 'edit', _('button.edit'), false, ['id' => 'contestant_id']);
        // $this->addLinkButton('Contestant:detail', 'detail', _('Detail'), false, ['id' => 'contestant_id']);
    }
}
