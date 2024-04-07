<?php

declare(strict_types=1);

namespace FKSDB\Models\WebService\Models;

use FKSDB\Components\DataTest\TestLogger;
use FKSDB\Components\DataTest\TestMessage;
use FKSDB\Models\ORM\Models\SchoolModel;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;

/**
 * @phpstan-extends WebModel<array{eventId:int},(array{level:string,text:string})[]>
 */
class SchoolsReportsWebModel extends WebModel
{

    protected function getInnerStructure(): array
    {
        return [];
    }

    protected function getJsonResponse(): array
    {
        set_time_limit(-1);

        $tests = SchoolModel::getTests($this->container);
        $logger = new TestLogger();
        foreach ($tests as $test) {
            $test->run($logger, null); //@phpstan-ignore-line
        }
        return array_map(
            fn(TestMessage $message) => ['text' => $message->toText(), 'level' => $message->level],
            $logger->getMessages()
        );
    }

    protected function isAuthorized(): bool
    {
        return true;
    }
}
