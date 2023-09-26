<?php

declare(strict_types=1);

namespace FKSDB\Components\Schedule;

use FKSDB\Components\Grids\Components\BaseGrid;
use FKSDB\Components\Grids\Components\Renderer\RendererItem;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\Schedule\PersonScheduleModel;
use FKSDB\Models\ORM\Models\Schedule\ScheduleItemModel;
use Fykosak\NetteORM\TypedGroupedSelection;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

/**
 * @phpstan-extends BaseGrid<PersonScheduleModel,array{}>
 */
class PersonsGrid extends BaseGrid
{
    private ScheduleItemModel $item;

    public function __construct(Container $container, ScheduleItemModel $item)
    {
        parent::__construct($container);
        $this->item = $item;
    }

    /**
     * @phpstan-return TypedGroupedSelection<PersonScheduleModel>
     */
    protected function getModels(): TypedGroupedSelection
    {
        return $this->item->getInterested();
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(): void
    {
        $this->paginate = false;
        $this->addTableColumn(
            new RendererItem(
                $this->container,
                fn(PersonScheduleModel $model) => (string)$model->person_schedule_id,
                new Title(null, _('Person schedule Id'))
            ),
            'person_schedule_id'
        );
        $this->addSimpleReferencedColumns([
            '@person.full_name',
            '@event.role',
            '@payment.payment',
            '@person_schedule.state',
        ]);
    }
}
