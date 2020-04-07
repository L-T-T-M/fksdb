<?php

namespace FKSDB\Components\Control\AjaxUpload;

use FKSDB\Components\React\ReactComponent;
use FKSDB\Logging\ILogger;
use FKSDB\Messages\Message;
use FKSDB\ORM\Models\ModelTask;
use FKSDB\ORM\Services\ServiceSubmit;
use FKSDB\React\ReactResponse;
use FKSDB\Submits\FilesystemUploadedSubmitStorage;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Container;
use Nette\Http\FileUpload;
use PublicModule\SubmitPresenter;
use ReactMessage;

/**
 * Class AjaxUpload
 * @package FKSDB\Components\Control\AjaxUpload
 * @property-read SubmitPresenter $presenter
 */
class AjaxUpload extends ReactComponent {
    use SubmitRevokeTrait;
    use SubmitSaveTrait;
    use SubmitDownloadTrait;
    /**
     * @var ServiceSubmit
     */
    private $serviceSubmit;

    /**
     * AjaxUpload constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        parent::__construct($context);
        $this->serviceSubmit = $this->container->getByType(ServiceSubmit::class);
    }

    /**
     * @throws InvalidLinkException
     */
    protected function configure() {
        $this->addAction('revoke', $this->link('revoke!'));
        $this->addAction('upload', $this->link('upload!'));
        $this->addAction('download', $this->link('download!'));
        parent::configure();
    }

    /**
     * @return string
     * @throws BadRequestException
     * @throws InvalidLinkException
     */
    public function getData(): string {
        $data = [];
        /**
         * @var ModelTask $task
         */
        foreach ($this->getPresenter()->getAvailableTasks() as $task) {
            $submit = $this->serviceSubmit->findByContestant($this->getPresenter()->getContestant()->ct_id, $task->task_id);
            $data[$task->task_id] = $this->serviceSubmit->serializeSubmit($submit, $task, $this->getPresenter());
        }
        return json_encode($data);
    }

    /**
     * @param bool $need
     * @return SubmitPresenter
     * @throws BadRequestException
     */
    public function getPresenter($need = TRUE) {
        $presenter = parent::getPresenter();
        if (!$presenter instanceof SubmitPresenter) {
            throw new BadRequestException();
        }
        return $presenter;
    }

    /**
     * @throws InvalidLinkException
     * @throws BadRequestException
     * @throws AbortException
     * @throws \Exception
     */
    public function handleUpload() {
        $response = new ReactResponse();
        /** @var FilesystemUploadedSubmitStorage $filesystemUploadedSubmitStorage */
        $filesystemUploadedSubmitStorage = $this->getContext()->getByType(FilesystemUploadedSubmitStorage::class);
        $contestant = $this->getPresenter()->getContestant();
        $files = $this->getHttpRequest()->getFiles();
        foreach ($files as $name => $fileContainer) {
            $this->serviceSubmit->getConnection()->beginTransaction();
            $filesystemUploadedSubmitStorage->beginTransaction();
            if (!preg_match('/task([0-9]+)/', $name, $matches)) {
                $response->addMessage(new ReactMessage(_('Task not found'), ILogger::WARNING));
                continue;
            }
            $task = $this->getPresenter()->isAvailableSubmit($matches[1]);
            if (!$task) {

                $response->setCode(403);
                $response->addMessage(new ReactMessage(_('Upload not allowed'), ILogger::ERROR));
                $this->getPresenter()->sendResponse($response);
            }
            /**
             * @var FileUpload $file
             */
            $file = $fileContainer;
            if (!$file->isOk()) {
                $response->setCode(500);
                $response->addMessage(new ReactMessage(_('File is not Ok'), ILogger::ERROR));
                $this->getPresenter()->sendResponse($response);
                return;
            }
            // store submit
            $submit = $this->saveSubmitTrait($file, $task, $contestant);
            $filesystemUploadedSubmitStorage->commit();
            $this->serviceSubmit->getConnection()->commit();
            $response->addMessage(new ReactMessage(_('Upload successful'), ILogger::SUCCESS));
            $response->setAct('upload');
            $response->setData($this->serviceSubmit->serializeSubmit($submit, $task, $this->getPresenter()));
            $this->getPresenter()->sendResponse($response);
        }
    }

    /**
     * @throws AbortException
     * @throws InvalidLinkException
     * @throws BadRequestException
     */
    public function handleRevoke() {
        $submitId = $this->getReactRequest()->requestData['submitId'];
        /**
         * @var Message $message
         */
        list($message, $data) = $this->traitHandleRevoke($submitId);
        $response = new ReactResponse();
        if ($data) {
            $response->setData($data);
        }
        $response->addMessage(new ReactMessage($message->getMessage(), $message->getLevel()));
        $this->getPresenter()->sendResponse($response);
        die();
    }

    /**
     * @throws BadRequestException
     * @throws AbortException
     */
    public function handleDownload() {
        $submitId = $this->getReactRequest()->requestData['submitId'];
        $this->traitHandleDownloadUploaded($submitId);
        die();
    }

    /**
     * @inheritDoc
     */
    protected function getReactId(): string {
        return 'public.ajax-upload';
    }
}
