<?php

namespace FKSDB\Modules\PublicModule;

use FKSDB\Components\DatabaseReflection\ColumnFactories\AbstractColumnException;
use FKSDB\Components\DatabaseReflection\OmittedControlException;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\Localization\UnsupportedLanguageException;
use FKSDB\Modules\Core\BasePresenter as CoreBasePresenter;
use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Forms\Containers\Models\ContainerWithOptions;
use FKSDB\Components\Forms\Controls\CaptchaBox;
use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Factories\ReferencedPerson\ReferencedPersonFactory;
use FKSDB\Config\Expressions\Helpers;
use FKSDB\ORM\IModel;
use FKSDB\ORM\Models\ModelContest;
use FKSDB\ORM\Models\ModelPerson;
use FKSDB\ORM\Services\ServiceContestant;
use FKSDB\ORM\Services\ServicePerson;
use FKSDB\Modules\Core\ContestPresenter\IContestPresenter;
use FKSDB\UI\PageTitle;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\InvalidStateException;
use Nette\Utils\JsonException;
use Persons\ExtendedPersonHandler;
use Persons\ExtendedPersonHandlerFactory;
use Persons\IExtendedPersonPresenter;
use Persons\SelfResolver;
/**
 * INPUT:
 *   contest (nullable)
 *   logged user (nullable)
 *   condition: the logged user is not contestant of the contest
 *   condition: the logged user is a person
 *
 * OUTPUT:
 *   registered contestant for the current year
 *      - if contest was provided in that contest
 *      - if user was provided for that user
 *
 * OPERATION
 *   - show/process person/login info iff logged user is null
 *   - show contest selector iff contest is null
 *   - contestant for filling default values
 *     - user must be logged in
 *     - if exists use last contestant from the provided contest
 *     - otherwise use last contestant from any contest (Vyfuk <= FYKOS)
 *
 * Just proof of concept (obsoleted due to ReferencedPerson).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class RegisterPresenter extends CoreBasePresenter implements IContestPresenter, IExtendedPersonPresenter {
    /**
     * @var int
     * @persistent
     */
    public $contestId;
    /**
     * @var int
     * @persistent
     */
    public $year;
    /**
     * @var int
     * @persistent
     */
    public $personId;

    /**
     * @var ModelPerson
     */
    private $person;

    /**
     * @var ServiceContestant
     */
    private $serviceContestant;

    /**
     * @var ReferencedPersonFactory
     */
    private $referencedPersonFactory;

    /**
     * @var ExtendedPersonHandlerFactory
     */
    private $handlerFactory;
    /**
     * @var ServicePerson
     */
    protected $servicePerson;

    /**
     * @param ServiceContestant $serviceContestant
     * @return void
     */
    public function injectServiceContestant(ServiceContestant $serviceContestant) {
        $this->serviceContestant = $serviceContestant;
    }

    /**
     * @param ServicePerson $servicePerson
     * @return void
     */
    public function injectServicePerson(ServicePerson $servicePerson) {
        $this->servicePerson = $servicePerson;
    }

    /**
     * @param ReferencedPersonFactory $referencedPersonFactory
     * @return void
     */
    public function injectReferencedPersonFactory(ReferencedPersonFactory $referencedPersonFactory) {
        $this->referencedPersonFactory = $referencedPersonFactory;
    }

    /**
     * @param ExtendedPersonHandlerFactory $handlerFactory
     * @return void
     */
    public function injectHandlerFactory(ExtendedPersonHandlerFactory $handlerFactory) {
        $this->handlerFactory = $handlerFactory;
    }

    /* ********************* TITLE ***************** */
    public function titleContest() {
        $this->setPageTitle(new PageTitle(_('Select contest')));
    }

    public function titleYear() {
        $this->setPageTitle(new PageTitle(_('Select year'), '', $this->getSelectedContest()->name));
    }

    public function titleEmail() {
        $this->setPageTitle(new PageTitle(_('Zadejte e-mail'), 'fa fa-envelope', $this->getSelectedContest()->name));
    }

    public function titleContestant() {
        $this->setPageTitle(new PageTitle(sprintf(_('%s – registrace řešitele (%s. ročník)'), $this->getSelectedContest()->name, $this->getSelectedYear())));
    }
    /* ********************* ACTIONS ***************** */
    /**
     * @throws AbortException
     */
    public function actionDefault() {
        $this->redirect('contest');
    }

    /**
     * @throws AbortException
     */
    public function actionContestant() {

        if ($this->user->isLoggedIn()) {
            $person = $this->getPerson();

            if (!$person) {
                $this->flashMessage(_('Uživatel musí být osobou, aby se mohl registrovat jako řešitel.'), self::FLASH_INFO);
                $this->redirect(':Core:Authentication:login');
            }
        } else {
            $email = $this->getHttpRequest()->getQuery('email');
            $person = $this->servicePerson->findByEmail($email);
            if ($person) {
                if ($person->getLogin()) {
                    $this->flashMessage('Byl nalezen existující účet, pro pokračování se přihlaste.');
                    $this->redirect(':Core:Authentication:login', ['login' => $email, 'backlink' => $this->storeRequest()]);
                }
            }
        }

        if ($this->getSelectedContest() && $person) {
            $contestants = $person->getActiveContestants($this->getYearCalculator());
            $contest = $this->getSelectedContest();
            $contestant = isset($contestants[$contest->contest_id]) ? $contestants[$contest->contest_id] : null;
            if ($contestant && $contestant->year == $this->getSelectedYear()) {
                // TODO FIXME persistent flash
                $this->flashMessage(sprintf(_('%s již řeší %s.'), $person->getFullName(), $contest->name), self::FLASH_INFO);
                $this->redirect(':Core:Authentication:login');
            }
        }
    }

    public function renderContest() {
        $this->template->contests = $this->getServiceContest()->getTable();
    }

    /**
     * @return void
     * @throws AbortException
     */
    public function renderYear() {
        $contest = $this->getSelectedContest();
        $forward = $this->getYearCalculator()->getForwardShift($contest);
        if ($forward) {
            $years = [];
            $years[] = $this->getYearCalculator()->getCurrentYear($contest);
            $years[] = $this->getYearCalculator()->getCurrentYear($contest) + $this->getYearCalculator()->getForwardShift($contest);
            $this->template->years = $years;
        } else {
            $this->redirect('this', ['year' => $this->getYearCalculator()->getCurrentYear($contest),]);
        }
    }

    /**
     * @throws BadTypeException
     */
    public function renderContestant() {
        $person = $this->getPerson();
        /** @var FormControl $contestantForm */
        $contestantForm = $this->getComponent('contestantForm');
        /** @var ReferencedId $referencedId */
        $referencedId = $contestantForm->getForm()->getComponent(ExtendedPersonHandler::CONT_AGGR)->getComponent(ExtendedPersonHandler::EL_PERSON);
        if ($person) {
            $referencedId->setDefaultValue($person);
        } else {
            $referencedId->setDefaultValue(ReferencedId::VALUE_PROMISE);
        }
    }


    /**
     * @return ModelContest|ActiveRow|null
     */
    public function getSelectedContest() {
        return $this->contestId ? $this->getServiceContest()->findByPrimary($this->contestId) : null;
    }

    /**
     * @return int
     */
    public function getSelectedYear() {
        return $this->year;
    }

    public function getSelectedAcademicYear(): int {
        if (!$this->getSelectedContest()) {
            throw new InvalidStateException("Cannot get acadamic year without selected contest.");
        }
        return $this->getYearCalculator()->getAcademicYear($this->getSelectedContest(), $this->getSelectedYear());
    }

    /**
     * @return ModelPerson|null
     */
    private function getPerson() {
        if (!$this->person) {

            if ($this->user->isLoggedIn()) {
                $this->person = $this->user->getIdentity()->getPerson();
            } else {
                $this->person = null;
            }
        }
        return $this->person;
    }

    /**
     * @return FormControl
     * @throws BadTypeException
     */
    protected function createComponentEmailForm(): FormControl {
        $control = new FormControl();
        $form = $control->getForm();
        $form->addText('email', _('e-mail'));
        $form->addSubmit('submit', _('Find'));
        $form->onSuccess[] = function (Form $form) {
            $this->emailFormSucceeded($form);
        };
        return $control;
    }

    /**
     * @param Form $form
     * @throws AbortException
     */
    private function emailFormSucceeded(Form $form) {
        $values = $form->getValues();
        $this->redirect('contestant', ['email' => $values['email'],]);
    }


    /**
     * @return array
     */
    private function getFieldsDefinition() {
        $contestId = $this->getSelectedContest()->contest_id;
        $contestName = $this->globalParameters['contestMapping'][$contestId];
        return Helpers::evalExpressionArray($this->globalParameters[$contestName]['registerContestant'], $this->getContext());
    }

    /**
     * @return FormControl
     *
     *
     * @throws AbstractColumnException
     * @throws BadTypeException
     * @throws JsonException
     * @throws NotImplementedException
     * @throws OmittedControlException
     * @throws UnsupportedLanguageException
     */
    protected function createComponentContestantForm(): FormControl {
        $control = new FormControl();
        $form = $control->getForm();

        $container = new ContainerWithOptions();
        $form->addComponent($container, ExtendedPersonHandler::CONT_AGGR);
        $component = $this->referencedPersonFactory->createReferencedPerson(
            $this->getFieldsDefinition(),
            $this->getSelectedAcademicYear(),
            ReferencedPersonFactory::SEARCH_NONE,
            false,
            new SelfResolver($this->getUser()),
            new SelfResolver($this->getUser())
        );

        $container->addComponent($component->getReferencedId(), ExtendedPersonHandler::EL_PERSON);
        $container->addComponent($component, ExtendedPersonHandler::CONT_PERSON);


        /*
         * CAPTCHA
         */
        if (!$this->getPerson()) {
            $captcha = new CaptchaBox();
            $form->addComponent($captcha, 'captcha');
        }

        $handler = $this->handlerFactory->create($this->serviceContestant, $this->getSelectedContest(), $this->getSelectedYear(), $this->getLang());

        $submit = $form->addSubmit('register', _('Register'));
        $submit->onClick[] = function (SubmitButton $button) use ($handler) {
            $form = $button->getForm();
            $result = $handler->handleForm($form, $this, true);
            if ($result) { // intentionally =
                /*
                 * Do not automatically log in user with existing logins for security reasons.
                 * (If someone was able to fill the form without conflicts, he might gain escalated privileges.)
                 */
                if (!$this->getPerson() && $result !== ExtendedPersonHandler::RESULT_OK_EXISTING_LOGIN) {
                    $login = $handler->getPerson()->getLogin();
                    $this->getUser()->login($login);
                }
                $this->redirect('Dashboard:default');

            }
        };
        $form->addProtection(_('Vypršela časová platnost formuláře. Odešlete jej prosím znovu.'));

        return $control;
    }

    /**
     * @return null|IModel
     */
    public function getModel() {
        return null; //we always create new contestant
    }

    public function messageCreate(): string {
        return _('Řešitel %s zaregistrován.');
    }

    public function messageEdit(): string {
        return _('Řešitel %s upraven.');
    }

    public function messageError(): string {
        return _('Chyba při registraci.');
    }

    public function messageExists(): string {
        return _('Řešitel je již registrován.');
    }

    /**
     * @return void
     *
     *
     *
     * @throws BadTypeException
     * @throws UnsupportedLanguageException
     * @throws \ReflectionException
     */
    protected function beforeRender() {
        $contest = $this->getSelectedContest();
        if ($contest) {
            $this->getPageStyleContainer()->navBarClassName = 'bg-dark navbar-dark';
            $this->getPageStyleContainer()->styleId = $contest->getContestSymbol();
        }
        parent::beforeRender();
    }
}
