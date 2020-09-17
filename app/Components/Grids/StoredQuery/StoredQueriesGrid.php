<?php

namespace FKSDB\Components\Grids\StoredQuery;

use FKSDB\Components\Grids\BaseGrid;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\ORM\Models\StoredQuery\ModelStoredQuery;
use FKSDB\ORM\Services\StoredQuery\ServiceStoredQuery;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use NiftyGrid\DataSource\NDataSource;
use NiftyGrid\DuplicateButtonException;
use NiftyGrid\DuplicateColumnException;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class StoredQueriesGrid extends BaseGrid {
    /** @const No. of characters that are showed from query description. */

    public const DESCRIPTION_TRUNC = 80;

    private ServiceStoredQuery $serviceStoredQuery;

    private array $activeTagIds;

    /**
     * StoredQueries2Grid constructor.
     * @param Container $container
     * @param array $activeTagIds
     */
    public function __construct(Container $container, array $activeTagIds) {
        parent::__construct($container);
        $this->activeTagIds = $activeTagIds;
    }

    public function injectServiceStoredQuery(ServiceStoredQuery $serviceStoredQuery): void {
        $this->serviceStoredQuery = $serviceStoredQuery;
    }

    /**
     * @param Presenter $presenter
     * @return void
     * @throws BadTypeException
     * @throws DuplicateButtonException
     * @throws DuplicateColumnException
     */
    protected function configure(Presenter $presenter): void {
        parent::configure($presenter);

        if (!empty($this->activeTagIds)) {
            $queries = $this->serviceStoredQuery->findByTagType($this->activeTagIds)->order('name');
            $this->setDataSource(new NDataSource($queries));
        } else {
            $queries = $this->serviceStoredQuery->getTable()->order('name');
            $this->setDataSource(new NDataSource($queries));
        }
        $this->addColumns([
            'stored_query.query_id',
            'stored_query.name',
            'stored_query.qid',
            'stored_query.tags',
        ]);
        $this->addColumn('description', _('Description'))->setTruncate(self::DESCRIPTION_TRUNC);

        $this->addLinkButton('StoredQuery:edit', 'edit', _('Edit'), false, ['id' => 'query_id']);
        $this->addLinkButton('StoredQuery:detail', 'detail', _('Detail'), false, ['id' => 'query_id']);
        $this->addLinkButton('Export:execute', 'execute', _('Execute'), false, ['id' => 'query_id']);
    }

    protected function getModelClassName(): string {
        return ModelStoredQuery::class;
    }
}
