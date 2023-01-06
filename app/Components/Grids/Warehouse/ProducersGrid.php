<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids\Warehouse;

use FKSDB\Components\Grids\EntityGrid;
use FKSDB\Models\ORM\Services\Warehouse\ProducerService;
use Nette\Database\Table\Selection;
use Nette\DI\Container;

class ProducersGrid extends EntityGrid
{
    public function __construct(Container $container)
    {
        parent::__construct($container, ProducerService::class, [
            'warehouse_producer.producer_id',
            'warehouse_producer.name',
        ]);
    }

    protected function getModels(): Selection
    {
        $query = parent::getModels();
        $query->order('name');
        return $query;
    }
}
