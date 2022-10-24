<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms;

use FKSDB\Components\Forms\Containers\ModelContainer;
use FKSDB\Components\Forms\Containers\SearchContainer\PersonSearchContainer;
use FKSDB\Components\Forms\Controls\CaptchaBox;
use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Models\Authentication\AccountManager;
use FKSDB\Models\Authorization\ContestAuthorizator;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\ContestYearModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\Services\ContestantService;
use FKSDB\Models\Persons\Resolvers\SelfResolver;
use Fykosak\Utils\Logging\Message;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Security\User;

class RegisterContestantFormComponent extends EntityFormComponent
{
    use ReferencedPersonTrait;

    public const CONT_CONTESTANT = 'contestant';

    private ContestYearModel $contestYear;
    private ?PersonModel $person;
    private string $lang;

    private ContestAuthorizator $contestAuthorizator;
    private ContestantService $service;
    private AccountManager $accountManager;
    private User $user;

    public function __construct(
        Container $container,
        string $lang,
        ContestYearModel $contestYear,
        ?PersonModel $person
    ) {
        parent::__construct($container, null);
        $this->person = $person;
        $this->lang = $lang;
        $this->contestYear = $contestYear;
    }

    final public function injectTernary(
        ContestAuthorizator $contestAuthorizator,
        ContestantService $service,
        AccountManager $accountManager,
        User $user
    ): void {
        $this->contestAuthorizator = $contestAuthorizator;
        $this->service = $service;
        $this->accountManager = $accountManager;
        $this->user = $user;
    }

    protected function configureForm(Form $form): void
    {
        $container = new ModelContainer();

        $referencedId = $this->referencedPersonFactory->createReferencedPerson(
            $this->getContext()->getParameters()['forms']['registerContestant' .
            ucfirst($this->contestYear->contest->getContestSymbol())],
            $this->contestYear,
            PersonSearchContainer::SEARCH_NONE,
            false,
            new SelfResolver($this->user)
        );
        $container->addComponent($referencedId, 'person_id');
        $form->addComponent($container, self::CONT_CONTESTANT);
        if (!$this->person) {
            $captcha = new CaptchaBox();
            $form->addComponent($captcha, 'captcha');
        }

        $form->addProtection(_('The form has expired. Please send it again.'));
    }

    /**
     * @throws BadTypeException
     */
    protected function handleFormSuccess(Form $form): void
    {
        $form->getValues('array');//trigger RPC
        /** @var ReferencedId $referencedId */
        $referencedId = $form[self::CONT_CONTESTANT]['person_id'];
        /** @var PersonModel $person */
        $person = $referencedId->getModel();
        $this->service->storeModel([
            'contest_id' => $this->contestYear->contest,
            'person_id' => $person->person_id,
            'year' => $this->contestYear->year,
        ]);
        $email = $person->getInfo()->email;
        if ($email && !$person->getLogin()) {
            $this->accountManager->createLoginWithInvitation($person, $email, $this->lang);
            $this->flashMessage(_('E-mail invitation sent.'), Message::LVL_INFO);
        }
        $this->getPresenter()->redirect(':Core:Dispatch:default');
    }

    /**
     * @throws BadTypeException
     */
    protected function setDefaults(): void
    {
        $this->getForm()->setDefaults([
            self::CONT_CONTESTANT => [
                'person_id' => isset($this->person) ? $this->person->person_id
                    : ReferencedId::VALUE_PROMISE,
            ],
        ]);
    }
}
