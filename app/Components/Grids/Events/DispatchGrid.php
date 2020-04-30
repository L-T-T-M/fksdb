<?php

namespace FKSDB\Components\Grids\Events;

use FKSDB\Components\DatabaseReflection\ValuePrinters\EventRole;
use FKSDB\Components\Grids\BaseGrid;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\ORM\Models\ModelEvent;
use FKSDB\ORM\Models\ModelPerson;
use FKSDB\ORM\Services\ServiceEvent;
use FKSDB\YearCalculator;
use Nette\DI\Container;
use NiftyGrid\DataSource\NDataSource;
use NiftyGrid\DuplicateButtonException;
use NiftyGrid\DuplicateColumnException;

/**
 * Class DispatchGrid
 * @package FKSDB\Components\Grids\Events
 */
class DispatchGrid extends BaseGrid {

    /**
     * @var ServiceEvent
     */
    private $serviceEvent;
    /**
     * @var ModelPerson
     */
    private $person;
    /**
     * @var YearCalculator
     */
    private $yearCalculator;

    /**
     * DispatchGrid constructor.
     * @param ModelPerson $person
     * @param Container $container
     */
    function __construct(ModelPerson $person, Container $container) {
        parent::__construct($container);
        $this->person = $person;
        $this->serviceEvent = $container->getByType(ServiceEvent::class);
        $this->yearCalculator = $container->getByType(YearCalculator::class);
    }

    /**
     * @param $presenter
     * @throws DuplicateButtonException
     * @throws DuplicateColumnException
     * @throws NotImplementedException
     */
    protected function configure($presenter) {
        parent::configure($presenter);

        $events = $this->serviceEvent->getTable()->order('begin DESC');
        $this->setDataSource(new NDataSource($events));

        $this->addColumns(['event.event_id', 'event.name', 'referenced.contest', 'event.year']);

        $this->addColumn('roles', _('Roles'))->setRenderer(function (ModelEvent $event) {
            $roles = $this->person->getRolesForEvent($event, $this->yearCalculator);
            return EventRole::getHtml($roles);
        })->setSortable(false);

        $this->addLinkButton('Dashboard:default', 'detail', _('Detail'), false, ['eventId' => 'event_id']);
    }

    /**
     * @return string
     */
    protected function getModelClassName(): string {
        return ModelEvent::class;
    }
}
