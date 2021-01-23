<?php

namespace FKSDB\Components\Grids;

use FKSDB\Models\Exceptions\ModelException;
use FKSDB\Models\Exceptions\NotFoundException;
use FKSDB\Models\Logging\ILogger;
use FKSDB\Models\Messages\Message;
use FKSDB\Models\ORM\Models\ModelContestant;
use FKSDB\Models\ORM\Models\ModelSubmit;
use FKSDB\Models\Submits\StorageException;
use FKSDB\Models\Submits\SubmitHandlerFactory;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\IPresenter;
use Nette\DI\Container;
use NiftyGrid\DataSource\IDataSource;
use NiftyGrid\DataSource\NDataSource;
use NiftyGrid\DuplicateButtonException;
use NiftyGrid\DuplicateColumnException;
use Tracy\Debugger;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class SubmitsGrid extends BaseGrid {

    private ModelContestant $contestant;

    private SubmitHandlerFactory $submitHandlerFactory;

    public function __construct(Container $container, ModelContestant $contestant) {
        parent::__construct($container);
        $this->contestant = $contestant;
    }

    final public function injectPrimary(SubmitHandlerFactory $submitHandlerFactory): void {
        $this->submitHandlerFactory = $submitHandlerFactory;
    }

    protected function getData(): IDataSource {
        $submits = $this->submitHandlerFactory->serviceSubmit->getSubmits();
        $submits->where('ct_id = ?', $this->contestant->ct_id); //TODO year + contest?
        return new NDataSource($submits);
    }

    /**
     * @param IPresenter $presenter
     * @throws DuplicateButtonException
     * @throws DuplicateColumnException
     */
    protected function configure(IPresenter $presenter): void {
        parent::configure($presenter);

        $this->setDefaultOrder('series DESC, tasknr ASC');

        //
        // columns
        //
        $this->addColumn('task', _('Task'))
            ->setRenderer(function (ModelSubmit $row): string {
                return $row->getTask()->getFQName();
            });
        $this->addColumn('submitted_on', _('Timestamp'));
        $this->addColumn('source', _('Method of handing'));

        //
        // operations
        //
        $this->addButton('revoke', _('Cancel'))
            ->setClass('btn btn-sm btn-warning')
            ->setText(_('Cancel'))
            ->setShow(function (ModelSubmit $row): bool {
                return $row->canRevoke();
            })
            ->setLink(function (ModelSubmit $row): string {
                return $this->link('revoke!', $row->submit_id);
            })
            ->setConfirmationDialog(function (ModelSubmit $row): string {
                return \sprintf(_('Do you really want to take the solution of task %s back?'), $row->getTask()->getFQName());
            });
        $this->addButton('download_uploaded')
            ->setText(_('Download original'))->setLink(function (ModelSubmit $row): string {
                return $this->link('downloadUploaded!', $row->submit_id);
            })
            ->setShow(function (ModelSubmit $row): bool {
                return !$row->isQuiz();
            });
        $this->addButton('download_corrected')
            ->setText(_('Download corrected'))->setLink(function (ModelSubmit $row): string {
                return $this->link('downloadCorrected!', $row->submit_id);
            })->setShow(function (ModelSubmit $row): bool {
                if (!$row->isQuiz()){
                    return $row->corrected;
                } else {
                    return false;
                }
            });

        $this->paginate = false;
        $this->enableSorting = false;
    }

    public function handleRevoke(int $id): void {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleRevoke($submit);
            $this->flashMessage(sprintf(_('Submitting of task %s cancelled.'), $submit->getTask()->getFQName()), ILogger::WARNING);
        } catch (ForbiddenRequestException|NotFoundException$exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_DANGER);
        } catch (StorageException|ModelException$exception) {
            Debugger::log($exception);
            $this->flashMessage(_('There was an error during the deletion of task %s.'), Message::LVL_DANGER);
        }
    }

    /**
     * @param int $id
     * @throws AbortException
     * @throws BadRequestException
     */
    public function handleDownloadUploaded(int $id): void {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleDownloadUploaded($this->getPresenter(), $submit);
        } catch (ForbiddenRequestException|NotFoundException|StorageException $exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_DANGER);
        }
    }

    /**
     * @param int $id
     * @throws AbortException
     * @throws BadRequestException
     */
    public function handleDownloadCorrected(int $id): void {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleDownloadCorrected($this->getPresenter(), $submit);
        } catch (ForbiddenRequestException|NotFoundException|StorageException $exception) {
            $this->flashMessage(new Message($exception->getMessage(), Message::LVL_DANGER));
        }
    }
}
