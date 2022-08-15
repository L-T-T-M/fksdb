<?php

declare(strict_types=1);

namespace FKSDB\Tests\PresentersTests\PublicModule\ApplicationPresenter;

use FKSDB\Models\ORM\Models\EventModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\Services\Events\ServiceDsefGroup;
use FKSDB\Tests\Events\EventTestCase;
use Nette\Utils\DateTime;
use FKSDB\Modules\PublicModule\ApplicationPresenter;

abstract class DsefTestCase extends EventTestCase
{

    protected ApplicationPresenter $fixture;
    protected PersonModel $person;
    protected EventModel $event;

    protected function getEvent(): EventModel
    {
        return $this->event;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = $this->createEvent([
            'event_type_id' => 2,
            'event_year' => 20,
            'registration_end' => new \DateTime(date('c', time() + 1000)),
            'parameters' => <<<EOT
EOT
            ,
        ]);

        $this->getContainer()->getByType(ServiceDsefGroup::class)->storeModel([
            'e_dsef_group_id' => 1,
            'event_id' => $this->event->event_id,
            'name' => 'Alpha',
            'capacity' => 4,
        ]);

        $this->fixture = $this->createPresenter('Public:Application');
        $this->mockApplication();

        $this->person = $this->createPerson(
            'Paní',
            'Bílá',
            ['email' => 'bila@hrad.cz', 'born' => DateTime::from('2000-01-01')],
            []
        );
    }
}
