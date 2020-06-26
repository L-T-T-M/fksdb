<?php

namespace FKSDB\Components\Grids;

use Authorization\ContestAuthorizator;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\ORM\Models\StoredQuery\ModelStoredQuery;
use FKSDB\ORM\Services\StoredQuery\ServiceStoredQuery;
use FKSDB\Components\Controls\StoredQueryTagCloud;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use NiftyGrid\DataSource\NDataSource;
use NiftyGrid\DuplicateButtonException;
use NiftyGrid\DuplicateColumnException;
use NiftyGrid\DuplicateGlobalButtonException;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class StoredQueriesGrid extends BaseGrid {
    /** @const No. of characters that are showed from query description. */

    const DESCRIPTION_TRUNC = 80;

    /**
     * @var ServiceStoredQuery
     */
    private $serviceStoredQuery;

    /**
     * @var ContestAuthorizator
     */
    private $contestAuthorizator;

    /**
     * @var StoredQueryTagCloud
     */
    private $storedQueryTagCloud;

    /**
     * StoredQueriesGrid constructor.
     * @param Container $container
     * @param StoredQueryTagCloud $storedQueryTagCloud
     */
    public function __construct(Container $container, StoredQueryTagCloud $storedQueryTagCloud) {
        parent::__construct($container);
        $this->storedQueryTagCloud = $storedQueryTagCloud;
    }

    /**
     * @param ServiceStoredQuery $serviceStoredQuery
     * @param ContestAuthorizator $contestAuthorizator
     * @return void
     */
    public function injectPrimary(ServiceStoredQuery $serviceStoredQuery, ContestAuthorizator $contestAuthorizator) {
        $this->serviceStoredQuery = $serviceStoredQuery;
        $this->contestAuthorizator = $contestAuthorizator;
    }

    /**
     * @param Presenter $presenter
     * @return void
     * @throws DuplicateButtonException
     * @throws DuplicateColumnException
     * @throws DuplicateGlobalButtonException
     * @throws InvalidLinkException
     * @throws BadTypeException
     */
    protected function configure(Presenter $presenter) {
        parent::configure($presenter);
        //
        // data
        //
        if (!empty($this->storedQueryTagCloud->activeTagIds)) {
            $queries = $this->serviceStoredQuery->findByTagType($this->storedQueryTagCloud->activeTagIds)->order('name');
            $this->setDataSource(new NDataSource($queries));
        } else {
            $queries = $this->serviceStoredQuery->getTable()->order('name');
            $this->setDataSource(new NDataSource($queries));
        }

        //
        // columns
        //
        $this->addColumn('name', _('Export name'));
        $this->addColumn('description', _('Description'))->setTruncate(self::DESCRIPTION_TRUNC);
        $this->addColumns([
            'stored_query.qid',
            'stored_query.tags',
        ]);
        //
        // operations
        //
        $contest = $presenter->getSelectedContest();
        $this->addButton('edit', _('Edit'))
            ->setText(_('Edit'))
            ->setLink(function (ModelStoredQuery $row) {
                return $this->getPresenter()->link('edit', $row->query_id);
            })
            ->setShow(function (ModelStoredQuery $row) use ($contest) {
                return $this->contestAuthorizator->isAllowed($row, 'edit', $contest);
            });
        $this->addButton('show', _('Podrobnosti'))
            ->setText(_('Podrobnosti'))
            ->setLink(function (ModelStoredQuery $row) {
                return $this->getPresenter()->link('show', $row->query_id);
            })
            ->setShow(function (ModelStoredQuery $row) use ($contest) {
                return $this->contestAuthorizator->isAllowed($row, 'show', $contest);
            });

        $this->addButton('execute', _('Execute'))
            ->setClass('btn btn-sm btn-primary')
            ->setText(_('Spustit'))
            ->setLink(function (ModelStoredQuery $row) {
                return $this->getPresenter()->link('execute', $row->query_id);
            })
            ->setShow(function (ModelStoredQuery $row) use ($contest) {
                return $this->contestAuthorizator->isAllowed($row, 'execute', $contest);
            });

        if ($presenter->authorized('compose')) {
            $this->addGlobalButton('compose', _('Napsat dotaz'))
                ->setLink($this->getPresenter()->link('compose'));
        }
    }

    protected function getModelClassName(): string {
        return ModelStoredQuery::class;
    }

}
