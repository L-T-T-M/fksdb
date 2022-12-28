<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids\StoredQuery;

use FKSDB\Components\Grids\BaseGrid;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Services\StoredQuery\QueryService;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;

class StoredQueriesGrid extends BaseGrid
{
    /** @const No. of characters that are showed from query description. */
    public const DESCRIPTION_TRUNC = 80;

    private QueryService $storedQueryService;

    private array $activeTagIds;

    public function __construct(Container $container, array $activeTagIds)
    {
        parent::__construct($container);
        $this->activeTagIds = $activeTagIds;
    }

    final public function injectServiceStoredQuery(QueryService $storedQueryService): void
    {
        $this->storedQueryService = $storedQueryService;
    }

    public function setData(): void
    {
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(Presenter $presenter): void
    {
        parent::configure($presenter);

        if (count($this->activeTagIds)) {
            $queries = $this->storedQueryService->findByTagType($this->activeTagIds)->order('name');
        } else {
            $queries = $this->storedQueryService->getTable()->order('name');
        }
        $this->data = $queries;
        $this->addColumns([
            'stored_query.query_id',
            'stored_query.name',
            'stored_query.description',
            'stored_query.qid',
            'stored_query.tags',
        ]);

        $this->addPresenterButton(
            'StoredQuery:edit',
            'edit',
            _('Edit'),
            false,
            ['id' => 'query_id'],
            'btn btn-sm btn-outline-primary'
        );
        $this->addPresenterButton(
            'StoredQuery:detail',
            'detail',
            _('Detail'),
            false,
            ['id' => 'query_id'],
            'btn btn-sm btn-outline-info'
        );
        $this->addPresenterButton(
            'Export:execute',
            'execute',
            _('Execute export'),
            false,
            ['id' => 'query_id'],
            'btn btn-sm btn-outline-success'
        );
    }
}
