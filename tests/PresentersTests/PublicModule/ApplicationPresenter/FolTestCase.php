<?php

namespace FKSDB\Tests\PresentersTests\PublicModule\ApplicationPresenter;

use FKSDB\Tests\Events\EventTestCase;
use Nette\Application\IPresenter;
use Nette\Database\IRow;
use Nette\Utils\DateTime;
use Tester\Assert;

abstract class FolTestCase extends EventTestCase {

    protected IPresenter $fixture;

    protected int $personId;

    protected int $eventId;

    protected function getEventId(): int {
        return $this->eventId;
    }

    protected function setUp(): void {
        parent::setUp();

        $future = DateTime::from(time() + DateTime::DAY);
        $this->eventId = $this->createEvent([
            'event_type_id' => 9,
            'event_year' => 4,
            'begin' => $future,
            'end' => $future,
            'parameters' => <<<EOT
EOT
            ,
        ]);

        $this->fixture = $this->createPresenter('Public:Application');
        $this->mockApplication();

        $this->personId = $this->createPerson('Paní', 'Bílá', ['email' => 'bila@hrad.cz', 'born' => DateTime::from('2000-01-01')], []);
    }

    protected function tearDown(): void {
        $this->connection->query('DELETE FROM e_fyziklani_participant');
        $this->connection->query('DELETE FROM e_fyziklani_team');
        parent::tearDown();
    }

    protected function assertTeamApplication(int $eventId, string $teamName): IRow {
        $application = $this->connection->fetch('SELECT * FROM e_fyziklani_team WHERE event_id = ? AND name = ?', $eventId, $teamName);
        Assert::notEqual(null, $application);
        return $application;
    }

}
