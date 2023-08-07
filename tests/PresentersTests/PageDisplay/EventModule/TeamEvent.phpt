<?php

declare(strict_types=1);

namespace FKSDB\Tests\PresentersTests\PageDisplay\EventModule;

// phpcs:disable
$container = require '../../../Bootstrap.php';

// phpcs:enable

use DateTime;

class TeamEvent extends EventModuleTestCase
{
    protected function getEventData(): array
    {
        return [
            'event_type_id' => 1,
            'year' => 1,
            'event_year' => 1,
            'begin' => new DateTime(),
            'end' => new DateTime(),
            'name' => 'TEST FOF',
            'registration_begin' => new \DateTime(),
            'registration_end' => new \DateTime(),
        ];
    }

    public function getPages(): array
    {
        return [
            ['Event:Chart', 'list'],
            ['Event:Dashboard', 'default'],
            ['Event:Dispatch', 'default'],
            ['Event:EventOrg', 'list'],
            ['Event:EventOrg', 'create'],

            ['Event:TeamApplication', 'attendance'],
            ['Event:TeamApplication', 'create'],
            ['Event:TeamApplication', 'detailedList'],
            ['Event:TeamApplication', 'fastEdit'],
            ['Event:TeamApplication', 'list'],
            ['Event:TeamApplication', 'mass'],
        ];
    }
}

// phpcs:disable
$testCase = new TeamEvent($container);
$testCase->run();
// phpcs:enable
