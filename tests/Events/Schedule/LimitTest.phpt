<?php

declare(strict_types=1);

namespace FKSDB\Tests\Events\Schedule;

use FKSDB\Models\ORM\Services\Schedule\ServicePersonSchedule;
use Nette\Application\Responses\TextResponse;
use Tester\Assert;

$container = require '../../Bootstrap.php';

class LimitTest extends ScheduleTestCase
{

    public function testRegistration(): void
    {

        Assert::equal(
            2,
            $this->getContainer()->getByType(ServicePersonSchedule::class)->getTable()->where(
                ['schedule_item_id' => $this->item->schedule_item_id]
            )->count('*')
        );

        $request = $this->createAccommodationRequest();
        $response = $this->fixture->run($request);
        Assert::type(TextResponse::class, $response);
        Assert::equal(
            2,
            $this->getContainer()->getByType(ServicePersonSchedule::class)->getTable()->where(
                ['schedule_item_id' => $this->item->schedule_item_id]
            )->count('*')
        );
    }

    public function getAccommodationCapacity(): int
    {
        return 2;
    }
}

$testCase = new LimitTest($container);
$testCase->run();
