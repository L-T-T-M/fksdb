<?php

namespace FKSDB\Components\Control\AjaxUpload;

use FKSDB\Components\React\ReactComponent;
use FKSDB\Messages\Message;
use FKSDB\ORM\Models\ModelTask;
use FKSDB\ORM\Services\ServiceSubmit;
use FKSDB\React\ReactResponse;
use FKSDB\Submits\ISubmitStorage;
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
    /**
     * @var ServiceSubmit
     */
    private $serviceSubmit;
    /**
     * @var ISubmitStorage
     */
    private $submitStorage;

    /**
     * AjaxUpload constructor.
     * @param Container $context
     * @param ServiceSubmit $serviceSubmit
     * @param ISubmitStorage $submitStorage
     */
    public function __construct(Container $context, ServiceSubmit $serviceSubmit, ISubmitStorage $submitStorage) {
        parent::__construct($context);
        $this->serviceSubmit = $serviceSubmit;
        $this->submitStorage = $submitStorage;
    }

    /**
     * @return ServiceSubmit
     */
    protected function getServiceSubmit(): ServiceSubmit {
        return $this->serviceSubmit;
    }

    /**
     * @return ISubmitStorage
     */
    protected function getSubmitStorage(): ISubmitStorage {
        return $this->submitStorage;
    }

    /**
     * @return string
     */
    public function getModuleName(): string {
        return 'public';
    }

    /**
     * @return string
     */
    public function getMode(): string {
        return '';
    }

    /**
     * @return array
     * @throws InvalidLinkException
     */
    public function getActions(): array {
        $actions = parent::getActions();
        $actions['revoke'] = $this->link('revoke!');
        $actions['upload'] = $this->link('upload!');
        return $actions;
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
        };
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
     * @return string
     */
    public function getComponentName(): string {
        return 'ajax-upload';
    }

    /**
     * @throws InvalidLinkException
     * @throws BadRequestException
     * @throws AbortException
     */
    public function handleUpload() {
        $response = new ReactResponse();
        $contestant = $this->getPresenter()->getContestant();
        $files = $this->getHttpRequest()->getFiles();
        foreach ($files as $name => $fileContainer) {
            $this->serviceSubmit->getConnection()->beginTransaction();
            $this->submitStorage->beginTransaction();
            if (!preg_match('/task([0-9]+)/', $name, $matches)) {
                $response->addMessage(new ReactMessage('task not found', 'warning'));
                continue;
            }
            $task = $this->getPresenter()->isAvailableSubmit($matches[1]);
            if (!$task) {
                $this->getPresenter()->getHttpResponse()->setCode('403');
                $response->addMessage(new ReactMessage('upload not allowed', 'danger'));
                $this->getPresenter()->sendResponse($response);
            };
            /**
             * @var FileUpload $file
             */
            $file = $fileContainer;
            if (!$file->isOk()) {
                $this->getPresenter()->getHttpResponse()->setCode('500');
                $response->addMessage(new ReactMessage('file is not Ok', 'danger'));
                $this->getPresenter()->sendResponse($response);
                return;
            }
            // store submit
            $submit = $this->saveSubmitTrait($file, $task, $contestant);
            $this->submitStorage->commit();
            $this->serviceSubmit->getConnection()->commit();
            $response->addMessage(new ReactMessage('Upload úspešný', 'success'));
            $response->setAct('upload');
            $response->setData($this->serviceSubmit->serializeSubmit($submit, $task, $this->getPresenter()));
            $this->getPresenter()->sendResponse($response);
        }

        die();
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
}
