<?php

declare(strict_types=1);

namespace FKSDB\Components\Game\Closing;

use FKSDB\Components\Grids\Components\Container\RowContainer;
use FKSDB\Components\Grids\Components\FilterList;
use FKSDB\Components\Grids\Components\Referenced\TemplateBaseItem;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\FieldLevelPermission;
use FKSDB\Models\ORM\Models\EventModel;
use FKSDB\Models\ORM\Models\Fyziklani\TeamModel2;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use Nette\Forms\Form;

class TeamListComponent extends FilterList
{
    private EventModel $event;

    public function __construct(Container $container, EventModel $event)
    {
        parent::__construct($container, FieldLevelPermission::ALLOW_FULL);
        $this->event = $event;
    }

    protected function getModels(): Selection
    {
        return $this->event->getPossiblyAttendingTeams()->order('fyziklani_team_id');
    }

    protected function configureForm(Form $form): void
    {
        // TODO: Implement configureForm() method.
    }

    /**
     * @throws \ReflectionException
     * @throws BadTypeException
     */
    protected function configure(): void
    {
        $this->classNameCallback = function (TeamModel2 $team) {
            try {
                $team->canClose();
                return 'alert alert-info';
            } catch (AlreadyClosedException $exception) {
                return 'alert alert-success';
            } catch (NotCheckedSubmitsException $exception) {
                return 'alert alert-danger';
            }
        };
        $row1 = new RowContainer($this->container);
        $row1->addComponent(
            new TemplateBaseItem($this->container, '<b>(@fyziklani_team.fyziklani_team_id) @fyziklani_team.name</b>'),
            'name'
        );
        $row1->addComponent(new TemplateBaseItem($this->container, '@fyziklani_team.category'), 'category');
        $row1->addComponent(new TemplateBaseItem($this->container, '@fyziklani_team.state'), 'state');
        $this->addRow($row1, 'row1');
        $row2 = new RowContainer($this->container);
        $row2->addComponent(new TemplateBaseItem($this->container, _('points: @fyziklani_team.points')), 'points');
        $this->addRow($row2, 'row2');
    }
}
