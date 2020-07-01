<?php

namespace FKSDB\Authentication;

use FKSDB\Exceptions\ModelException;
use FKSDB\ORM\AbstractModelSingle;
use FKSDB\ORM\Models\ModelAuthToken;
use FKSDB\ORM\Models\ModelLogin;
use FKSDB\ORM\Models\ModelPerson;
use FKSDB\ORM\Services\ServiceAuthToken;
use FKSDB\ORM\Services\ServiceEmailMessage;
use FKSDB\ORM\Services\ServiceLogin;
use Mail\MailTemplateFactory;
use Nette\Application\BadRequestException;
use Nette\Utils\DateTime;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class AccountManager {

    /** @var ServiceLogin */
    private $serviceLogin;

    /** @var ServiceAuthToken */
    private $serviceAuthToken;
    /**
     * @var string
     */
    private $invitationExpiration = '+1 month';
    /**
     * @var string
     */
    private $recoveryExpiration = '+1 day';
    /**
     * @var mixed
     */
    private $emailFrom;
    /** @var ServiceEmailMessage */
    private $serviceEmailMessage;
    /** @var MailTemplateFactory */
    private $mailTemplateFactory;

    /**
     * AccountManager constructor.
     * @param MailTemplateFactory $mailTemplateFactory
     * @param ServiceLogin $serviceLogin
     * @param ServiceAuthToken $serviceAuthToken
     * @param ServiceEmailMessage $serviceEmailMessage
     */
    public function __construct(MailTemplateFactory $mailTemplateFactory,
                                ServiceLogin $serviceLogin,
                                ServiceAuthToken $serviceAuthToken,
                                ServiceEmailMessage $serviceEmailMessage) {
        $this->serviceLogin = $serviceLogin;
        $this->serviceAuthToken = $serviceAuthToken;
        $this->serviceEmailMessage = $serviceEmailMessage;
        $this->mailTemplateFactory = $mailTemplateFactory;
    }

    /**
     * @return string
     */
    public function getInvitationExpiration() {
        return $this->invitationExpiration;
    }

    /**
     * @param $invitationExpiration
     * @return void
     */
    public function setInvitationExpiration($invitationExpiration) {
        $this->invitationExpiration = $invitationExpiration;
    }

    /**
     * @return string
     */
    public function getRecoveryExpiration() {
        return $this->recoveryExpiration;
    }

    /**
     * @param $recoveryExpiration
     * @return void
     */
    public function setRecoveryExpiration($recoveryExpiration) {
        $this->recoveryExpiration = $recoveryExpiration;
    }

    /**
     * @return mixed
     */
    public function getEmailFrom() {
        return $this->emailFrom;
    }

    /**
     * @param $emailFrom
     * @return void
     */
    public function setEmailFrom($emailFrom) {
        $this->emailFrom = $emailFrom;
    }

    /**
     * Creates login and invites user to set up the account.
     *
     * @param ModelPerson $person
     * @param string $email
     * @param string $lang
     * @return ModelLogin
     * @throws BadRequestException
     * @throws ModelException
     */
    public function createLoginWithInvitation(ModelPerson $person, string $email, string $lang) {
        $login = $this->createLogin($person);

        $until = DateTime::from($this->getInvitationExpiration());
        $token = $this->serviceAuthToken->createToken($login, ModelAuthToken::TYPE_INITIAL_LOGIN, $until);

        $templateParams = [
            'token' => $token->token,
            'person' => $person,
            'email' => $email,
            'until' => $until,
        ];
        $data = [];
        $data['text'] = (string)$this->mailTemplateFactory->createLoginInvitation($person->getPreferredLang() ?: $lang, $templateParams);
        $data['subject'] = _('Založení účtu');
        $data['sender'] = $this->getEmailFrom();
        $data['recipient'] = $email;
        $this->serviceEmailMessage->addMessageToSend($data);
        return $login;
    }

    /**
     * @param ModelLogin $login
     * @param string|null $lang
     * @throws \Exception
     */
    public function sendRecovery(ModelLogin $login, string $lang = null) {
        $person = $login->getPerson();
        $recoveryAddress = $person ? $person->getInfo()->email : null;
        if (!$recoveryAddress) {
            throw new RecoveryNotImplementedException();
        }
        $token = $this->serviceAuthToken->getTable()->where([
            'login_id' => $login->login_id,
            'type' => ModelAuthToken::TYPE_RECOVERY,
        ])
            ->where('until > ?', new DateTime())->fetch();
        if ($token) {
            throw new RecoveryExistsException();
        }

        $until = DateTime::from($this->getRecoveryExpiration());
        $token = $this->serviceAuthToken->createToken($login, ModelAuthToken::TYPE_RECOVERY, $until);
        $templateParams = [
            'token' => $token->token,
            'login' => $login,
            'until' => $until,
        ];
        $data = [];
        $data['text'] = (string)$this->mailTemplateFactory->createPasswordRecovery($lang, $templateParams);
        $data['subject'] = _('Obnova hesla');
        $data['sender'] = $this->getEmailFrom();
        $data['recipient'] = $recoveryAddress;

        $this->serviceEmailMessage->addMessageToSend($data);
    }

    /**
     * @param ModelLogin $login
     * @return void
     */
    public function cancelRecovery(ModelLogin $login) {
        $this->serviceAuthToken->getTable()->where([
            'login_id' => $login->login_id,
            'type' => ModelAuthToken::TYPE_RECOVERY,
        ])->delete();
    }

    /**
     * @param ModelPerson $person
     * @param string $login
     * @param string $password
     * @return AbstractModelSingle|ModelLogin
     */
    final public function createLogin(ModelPerson $person, string $login = null, string $password = null) {
        /** @var ModelLogin $login */
        $login = $this->serviceLogin->createNewModel([
            'person_id' => $person->person_id,
            'login' => $login,
            'active' => 1,
        ]);

        /* Must be done after login_id is allocated. */
        if ($password) {
            $hash = $login->createHash($password);
            $this->serviceLogin->updateModel2($login, ['hash' => $hash]);
        }
        return $login;
    }
}
