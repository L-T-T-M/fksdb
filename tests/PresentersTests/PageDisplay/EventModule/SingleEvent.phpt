<?php

declare(strict_types=1);

namespace FKSDB\Tests\PresentersTests\PageDisplay\EventModule;

// phpcs:disable
$container = require '../../../Bootstrap.php';

// phpcs:enable
use DateTime;

class SingleEvent extends EventModuleTestCase
{
    protected function getEventData(): array
    {
        return [
            'event_type_id' => 7,
            'year' => 1,
            'event_year' => 1,
            'begin' => new DateTime(),
            'end' => new DateTime(),
            'name' => 'TEST TSAF',
        ];
    }

    public function getPages(): array
    {
        return [
            ['Event:Application', 'list'],
            ['Event:Application', 'import'],
            ['Event:Application', 'transitions'],
            ['Event:Chart', 'list'],
            ['Event:Chart', 'chart', ['chart' => 'participantAcquaintance']],
            ['Event:Chart', 'chart', ['chart' => 'singleApplicationProgress']],
            // ['Event:Chart', 'teamApplicationProgress'],
            ['Event:Chart', 'chart', ['chart' => 'model']],
            ['Event:Dashboard', 'default'],
            ['Event:Dispatch', 'default'],
            ['Event:EventOrg', 'list'],
            ['Event:EventOrg', 'create'],

            // ['Event:Seating', 'default'],
            // ['Event:Seating', 'preview'],
            // ['Event:Seating', 'list'],
        ];
    }
}

// phpcs:disable
$testCase = new SingleEvent($container);
$testCase->run();
// phpcs:enable
