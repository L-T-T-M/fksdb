<?php

declare(strict_types=1);

namespace FKSDB\Tests\PresentersTests\PublicModule\SubmitPresenter;

// phpcs:disable
$container = require '../../../Bootstrap.php';

// phpcs:enable
use FKSDB\Models\YearCalculator;

class AllUpload extends SubmitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPersonHistory(
            $this->person,
            YearCalculator::getCurrentAcademicYear(),
            $this->genericSchool,
            6
        );
    }

    public function testSubmit(): void
    {
        $this->innerTestSubmit();

        $this->assertSubmit($this->contestant, $this->taskRestricted);
    }
}

// phpcs:disable
$testCase = new AllUpload($container);
$testCase->run();
// phpcs:enable
