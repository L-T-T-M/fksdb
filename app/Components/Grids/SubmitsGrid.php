<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Components\Grids\Components\Button\ControlButton;
use FKSDB\Components\Grids\Components\Button\PresenterButton;
use FKSDB\Components\Grids\Components\BaseGrid;
use FKSDB\Components\Grids\Components\Renderer\RendererItem;
use FKSDB\Models\Exceptions\NotFoundException;
use FKSDB\Models\ORM\Models\ContestantModel;
use FKSDB\Models\ORM\Models\SubmitModel;
use FKSDB\Models\Submits\StorageException;
use FKSDB\Models\Submits\SubmitHandlerFactory;
use Fykosak\NetteORM\Exceptions\ModelException;
use Fykosak\NetteORM\TypedGroupedSelection;
use Fykosak\Utils\Logging\Message;
use Fykosak\Utils\UI\Title;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\DI\Container;
use Tracy\Debugger;

class SubmitsGrid extends BaseGrid
{
    private ContestantModel $contestant;
    private SubmitHandlerFactory $submitHandlerFactory;
    private string $lang;

    public function __construct(Container $container, ContestantModel $contestant, string $lang)
    {
        parent::__construct($container);
        $this->contestant = $contestant;
        $this->lang = $lang;
    }

    final public function injectPrimary(SubmitHandlerFactory $submitHandlerFactory): void
    {
        $this->submitHandlerFactory = $submitHandlerFactory;
    }

    protected function getModels(): TypedGroupedSelection
    {
        return $this->contestant->getSubmits()->order('task.series DESC, tasknr ASC');
    }

    protected function configure(): void
    {
        $this->addColumn(
            new RendererItem(
                $this->container,
                fn(SubmitModel $submit): string => $submit->task->getFullLabel($this->lang),
                new Title(null, _('Task'))
            ),
            'task'
        );
        $this->addColumn(
            new RendererItem(
                $this->container,
                fn(SubmitModel $model): string => $model->submitted_on->format('c'),
                new Title(null, _('Timestamp'))
            ),
            'submitted_on'
        );
        $this->addColumn(
            new RendererItem(
                $this->container,
                fn(SubmitModel $model): string => $model->source->value,
                new Title(null, _('Method of handing'))
            ),
            'source'
        );

        $this->addButton(
            new ControlButton(
                $this->container,
                $this,
                new Title(null, _('Cancel')),
                fn(SubmitModel $submit): array => ['revoke!', ['id' => $submit->submit_id]],
                'btn btn-sm me-1 btn-outline-warning',
                fn(SubmitModel $submit): bool => $submit->canRevoke()
            ),
            'revoke'
        );

        $this->addButton(
            new ControlButton(
                $this->container,
                $this,
                new Title(null, _('Download original')),
                fn(SubmitModel $submit): array => ['downloadUploaded!', ['id' => $submit->submit_id]],
                null,
                fn(SubmitModel $submit): bool => !$submit->isQuiz()
            ),
            'download_uploaded'
        );

        $this->addButton(
            new ControlButton(
                $this->container,
                $this,
                new Title(null, _('Download corrected')),
                fn(SubmitModel $submit): array => ['downloadCorrected!', ['id' => $submit->submit_id]],
                null,
                fn(SubmitModel $submit): bool => !$submit->isQuiz() && $submit->corrected
            ),
            'download_corrected'
        );

        $this->addButton(
            new PresenterButton(
                $this->container,
                new Title(null, _('Detail')),
                fn(SubmitModel $submit): array => [':Public:Submit:quizDetail', ['id' => $submit->submit_id]],
                null,
                fn(SubmitModel $submit): bool => $submit->isQuiz()
            ),
            'show_quiz_detail'
        );

        $this->paginate = false;
    }

    public function handleRevoke(int $id): void
    {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleRevoke($submit);
            $this->flashMessage(
                sprintf(
                    _('Submitting of task %s cancelled.'),
                    $submit->task->getFullLabel($this->lang)
                ),
                Message::LVL_WARNING
            );
        } catch (ForbiddenRequestException | NotFoundException$exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_ERROR);
        } catch (StorageException | ModelException$exception) {
            Debugger::log($exception);
            $this->flashMessage(_('There was an error during the deletion of task %s.'), Message::LVL_ERROR);
        }
    }

    /**
     * @throws BadRequestException
     */
    public function handleDownloadUploaded(int $id): void
    {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleDownloadUploaded($this->getPresenter(), $submit);
        } catch (ForbiddenRequestException | NotFoundException | StorageException $exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_ERROR);
        }
    }

    /**
     * @throws BadRequestException
     */
    public function handleDownloadCorrected(int $id): void
    {
        try {
            $submit = $this->submitHandlerFactory->getSubmit($id);
            $this->submitHandlerFactory->handleDownloadCorrected($this->getPresenter(), $submit);
        } catch (ForbiddenRequestException | NotFoundException | StorageException $exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_ERROR);
        }
    }
}
