<?php

declare(strict_types=1);

use FKSDB\Components\DataTest\DataTestFactory;
use FKSDB\Models\ORM\Services\EventService;
use Fykosak\Utils\Logging\MemoryLogger;
use Fykosak\Utils\Logging\Message;
use Nette\DI\Container;

/** @var Container $container */
$container = require __DIR__ . '/bootstrap.php';

set_time_limit(-1);
$service = $container->getByType(EventService::class);
$dataTestFactory = $container->getByType(DataTestFactory::class);
$tests = $dataTestFactory->getEventTests();
$logger = new MemoryLogger();
$event = $service->findByPrimary(+$argv[1]);
foreach ($tests as $test) {
    $test->run($logger, $event);
}
echo json_encode(array_map(fn(Message $message) => $message->__toArray(), $logger->getMessages()));
