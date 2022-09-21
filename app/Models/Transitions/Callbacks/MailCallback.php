<?php

declare(strict_types=1);

namespace FKSDB\Models\Transitions\Callbacks;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\Mail\MailTemplateFactory;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\Services\EmailMessageService;
use FKSDB\Models\Transitions\Holder\ModelHolder;

class MailCallback implements TransitionCallback
{

    protected EmailMessageService $emailMessageService;
    protected MailTemplateFactory $mailTemplateFactory;
    protected string $templateFile;
    protected array $emailData;

    public function __construct(
        string $templateFile,
        array $emailData,
        EmailMessageService $emailMessageService,
        MailTemplateFactory $mailTemplateFactory
    ) {
        $this->templateFile = $templateFile;
        $this->emailData = $emailData;
        $this->emailMessageService = $emailMessageService;
        $this->mailTemplateFactory = $mailTemplateFactory;
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    public function __invoke(ModelHolder $holder, ...$args): void
    {
        foreach ($this->getPersonFromHolder($holder) as $person) {
            $data = $this->emailData;
            $data['recipient_person_id'] = $person->person_id;
            $data['text'] = (string)$this->mailTemplateFactory->createWithParameters(
                $this->templateFile,
                $person->getPreferredLang(),
                ['holder' => $holder]
            );
            $this->emailMessageService->addMessageToSend($data);
        }
    }

    /**
     * @return PersonModel[]
     * @throws \ReflectionException
     * @throws BadTypeException
     */
    protected function getPersonFromHolder(ModelHolder $holder): array
    {
        $person = $holder->getModel()->getReferencedModel(PersonModel::class);
        if (is_null($person)) {
            throw new BadTypeException(PersonModel::class, $person);
        }
        return [$person];
    }
}
