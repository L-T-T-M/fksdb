<?php

declare(strict_types=1);

namespace FKSDB\Models\Transitions\Callbacks;

use FKSDB\Models\Authentication\AccountManager;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\Mail\MailTemplateFactory;
use FKSDB\Models\ORM\Models\AuthTokenModel;
use FKSDB\Models\ORM\Models\LoginModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\Services\AuthTokenService;
use FKSDB\Models\ORM\Services\EmailMessageService;
use FKSDB\Models\Transitions\Holder\ModelHolder;
use FKSDB\Models\Transitions\Statement;
use FKSDB\Modules\Core\Language;
use Nette\SmartObject;

/**
 * @phpstan-template THolder of ModelHolder
 * @implements Statement<void,THolder>
 */
abstract class MailCallback implements Statement
{
    use SmartObject;

    protected EmailMessageService $emailMessageService;
    protected MailTemplateFactory $mailTemplateFactory;
    protected AccountManager $accountManager;
    protected AuthTokenService $authTokenService;

    public function __construct(
        EmailMessageService $emailMessageService,
        MailTemplateFactory $mailTemplateFactory,
        AuthTokenService $authTokenService,
        AccountManager $accountManager
    ) {
        $this->emailMessageService = $emailMessageService;
        $this->mailTemplateFactory = $mailTemplateFactory;
        $this->accountManager = $accountManager;
        $this->authTokenService = $authTokenService;
    }


    /**
     * @phpstan-param THolder $args
     * @throws \ReflectionException
     * @throws BadTypeException
     */
    public function __invoke(...$args): void
    {
        [$holder] = $args;
        foreach ($this->getPersonsFromHolder($holder) as $person) {
            $data = $this->getData($holder);
            $data['recipient_person_id'] = $person->person_id;
            $data['text'] = $this->createMessageText($holder, $person);
            $this->emailMessageService->addMessageToSend($data);
        }
    }

    /**
     * @throws BadTypeException
     * @phpstan-param THolder $holder
     */
    protected function createMessageText(ModelHolder $holder, PersonModel $person): string
    {
        return $this->mailTemplateFactory->renderWithParameters(
            $this->getTemplatePath($holder),
            Language::tryFrom($person->getPreferredLang()),
            [
                'person' => $person,
                'holder' => $holder,
                'token' => $this->createToken($person, $holder),
            ]
        );
    }

    final protected function resolveLogin(PersonModel $person): LoginModel
    {
        return $person->getLogin() ?? $this->accountManager->createLogin($person);
    }

    /**
     * @phpstan-param THolder $holder
     */
    protected function createToken(PersonModel $person, ModelHolder $holder): ?AuthTokenModel
    {
        return null;
    }


    /**
     * @phpstan-return PersonModel[]
     * @throws \ReflectionException
     * @throws BadTypeException
     * @phpstan-param THolder $holder
     */
    protected function getPersonsFromHolder(ModelHolder $holder): array
    {
        $person = $holder->getModel()->getReferencedModel(PersonModel::class);
        if (is_null($person)) {
            throw new BadTypeException(PersonModel::class, $person);
        }
        return [$person];
    }

    /**
     * @phpstan-param THolder $holder
     */
    abstract protected function getTemplatePath(ModelHolder $holder): string;

    /**
     * @phpstan-param THolder $holder
     * @phpstan-return array{
     *     blind_carbon_copy?:string,
     *     subject:string,
     *     sender:string,
     *     reply_to?:string,
     * }
     */
    abstract protected function getData(ModelHolder $holder): array;
}
