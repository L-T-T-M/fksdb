<?php

declare(strict_types=1);

namespace FKSDB\Components\Schedule;

use FKSDB\Components\Grids\Components\BaseList;
use FKSDB\Components\Grids\Components\Button\Button;
use FKSDB\Components\Grids\Components\Container\RelatedTable;
use FKSDB\Components\Grids\Components\Container\RowContainer;
use FKSDB\Components\Grids\Components\Referenced\TemplateItem;
use FKSDB\Components\Grids\Components\Renderer\RendererItem;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\FieldLevelPermission;
use FKSDB\Models\ORM\Models\EventModel;
use FKSDB\Models\ORM\Models\Schedule\ScheduleGroupModel;
use FKSDB\Models\ORM\Models\Schedule\ScheduleItemModel;
use Fykosak\NetteORM\TypedGroupedSelection;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

/**
 * @phpstan-extends BaseList<ScheduleGroupModel>
 */
class GroupListComponent extends BaseList
{
    private EventModel $event;

    public function __construct(Container $container, EventModel $event)
    {
        parent::__construct($container, FieldLevelPermission::ALLOW_FULL);
        $this->event = $event;
    }

    /**
     * @phpstan-return TypedGroupedSelection<ScheduleGroupModel>
     */
    protected function getModels(): TypedGroupedSelection
    {
        return $this->event->getScheduleGroups()->order('start');
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(): void
    {
        $this->classNameCallback = fn(ScheduleGroupModel $model) => 'alert alert-secondary';
        $this->setTitle(
            new TemplateItem( // @phpstan-ignore-line
                $this->container,
                _('@schedule_group.name_en (@schedule_group.schedule_group_id)')
            )
        );
        /** @phpstan-var RowContainer<ScheduleGroupModel> $row0 */
        $row0 = new RowContainer($this->container, new Title(null, ''));
        $this->addRow($row0, 'row0');
        $row0->addComponent(new TemplateItem($this->container, '@schedule_group.schedule_group_type'), 'type');
        $row0->addComponent(
            new RendererItem(
                $this->container,
                fn(ScheduleGroupModel $model) => ($model->start->format('j-n') === $model->end->format('j-n'))
                    ? $model->start->format('j. n. Y H:i') . ' - ' . $model->end->format('H:i')
                    : $model->start->format('j. n. Y H:i') . ' - ' . $model->end->format('j. n. Y H:i'),
                new Title(null, _('Duration'))
            ),
            'duration'
        );
        /** @phpstan-var RelatedTable<ScheduleGroupModel,ScheduleItemModel> $itemsRow */
        $itemsRow = new RelatedTable(
            $this->container,
            fn(ScheduleGroupModel $model) => $model->getItems(), //@phpstan-ignore-line
            new Title(null, _('Items')),
            true
        );
        $this->addRow($itemsRow, 'items');
        $itemsRow->addColumn(
            new TemplateItem( // @phpstan-ignore-line
                $this->container,
                '@schedule_item.name:value (@schedule_item.schedule_item_id:value)',
                '@schedule_item.name:title'
            ),
            'title'
        );
        $itemsRow->addColumn(
            new TemplateItem( // @phpstan-ignore-line
                $this->container,
                '@schedule_item.price_czk / @schedule_item.price_eur',
                _('Price')
            ),
            'price'
        );
        $itemsRow->addColumn(
            new TemplateItem( // @phpstan-ignore-line
                $this->container,
                '@schedule_item.used_capacity / @schedule_item.free_capacity / @schedule_item.capacity',
                _('Used / Free / Total')
            ),
            'capacity'
        );

        $itemsRow->addButton(
            new Button(
                $this->container,
                $this->getPresenter(),
                new Title(null, _('Edit')),
                fn(ScheduleItemModel $model) => [':Schedule:Item:edit', ['id' => $model->schedule_item_id]]
            ),
            'edit'
        );
        $itemsRow->addButton(
            new Button(
                $this->container,
                $this->getPresenter(),
                new Title(null, _('Detail')),
                fn(ScheduleItemModel $model) => [':Schedule:Item:detail', ['id' => $model->schedule_item_id]]
            ),
            'detail'
        );
        $itemsRow->addButton(
            new Button(
                $this->container,
                $this->getPresenter(),
                new Title(null, _('Attendance')),
                fn(ScheduleItemModel $model) => [':Schedule:Item:attendance', ['id' => $model->schedule_item_id]]
            ),
            'attendance'
        );
        $this->addPresenterButton(
            ':Schedule:Group:detail',
            'detail',
            _('Detail'),
            false,
            ['id' => 'schedule_group_id']
        );

        $this->addPresenterButton(
            ':Schedule:Group:edit',
            'edit',
            _('Edit'),
            false,
            ['id' => 'schedule_group_id']
        );

        $this->addPresenterButton(
            ':Schedule:Group:attendance',
            'attendance',
            _('Attendance'),
            false,
            ['id' => 'schedule_group_id']
        );
    }
}
