<?php

declare(strict_types=1);

namespace FKSDB\Modules\Core;

use FKSDB\Components\Controls\Choosers\LanguageChooserComponent;
use FKSDB\Components\Controls\ColumnPrinter\ColumnRendererComponent;
use FKSDB\Components\Controls\ColumnPrinter\ColumnTableComponent;
use FKSDB\Components\Controls\LinkPrinter\LinkPrinterComponent;
use FKSDB\Components\Controls\Navigation\NavigationChooserComponent;
use FKSDB\Components\Controls\Navigation\PresenterBuilder;
use FKSDB\Components\Forms\Controls\Autocomplete\AutocompleteJSONProvider;
use FKSDB\Components\Forms\Controls\Autocomplete\AutocompleteSelectBox;
use FKSDB\Components\Forms\Controls\Autocomplete\FilteredDataProvider;
use FKSDB\Models\Authentication\PasswordAuthenticator;
use FKSDB\Models\Authentication\TokenAuthenticator;
use FKSDB\Models\Authorization\ContestAuthorizator;
use FKSDB\Models\Authorization\EventAuthorizator;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\LoginModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\Services\ContestService;
use FKSDB\Models\Utils\Utils;
use FKSDB\Modules\CoreModule\AuthenticationPresenter;
use Fykosak\Utils\Localization\GettextTranslator;
use Fykosak\Utils\Localization\UnsupportedLanguageException;
use Fykosak\Utils\Logging\Message;
use Fykosak\Utils\UI\PageTitle;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\DI\Container;
use Nette\InvalidStateException;
use Nette\Security\AuthenticationException;
use Tracy\Debugger;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter implements AutocompleteJSONProvider
{
    /**
     * @persistent
     * @internal
     */
    public ?string $lang = null;
    private string $language;
    protected ContestService $contestService;
    protected PresenterBuilder $presenterBuilder;
    protected GettextTranslator $translator;
    protected bool $authorized = true;
    private array $authorizedCache = [];
    private Container $diContainer;

    protected TokenAuthenticator $tokenAuthenticator;
    protected PasswordAuthenticator $passwordAuthenticator;
    protected EventAuthorizator $eventAuthorizator;
    protected ContestAuthorizator $contestAuthorizator;

    final public function injectBase(
        Container $diContainer,
        ContestService $contestService,
        PresenterBuilder $presenterBuilder,
        GettextTranslator $translator,
        TokenAuthenticator $tokenAuthenticator,
        PasswordAuthenticator $passwordAuthenticator,
        ContestAuthorizator $contestAuthorizator,
        EventAuthorizator $eventAuthorizator
    ): void {
        $this->contestService = $contestService;
        $this->presenterBuilder = $presenterBuilder;
        $this->translator = $translator;
        $this->diContainer = $diContainer;
        $this->tokenAuthenticator = $tokenAuthenticator;
        $this->passwordAuthenticator = $passwordAuthenticator;
        $this->contestAuthorizator = $contestAuthorizator;
        $this->eventAuthorizator = $eventAuthorizator;
    }

    /**
     * @param \ReflectionMethod|\ReflectionClass $element
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function checkRequirements($element): void
    {
        parent::checkRequirements($element);
        if ($element instanceof \ReflectionClass) {
            if (!$this->getUser()->isLoggedIn() && $this->isAuthAllowed(AuthMethod::tryFrom(AuthMethod::TOKEN))) {
                $this->tryAuthToken();
            }
            if (!$this->getUser()->isLoggedIn() && $this->isAuthAllowed(AuthMethod::tryFrom(AuthMethod::HTTP))) {
                $this->tryHttpAuth();
            }
            if (!$this->getUser()->isLoggedIn() && $this->isAuthAllowed(AuthMethod::tryFrom(AuthMethod::LOGIN))) {
                $this->optionalLoginRedirect();
            }
            $method = $this->formatAuthorizedMethod();
            $this->authorized = $method->invoke($this);
            if (!$this->authorized) {
                throw new ForbiddenRequestException();
            }
        }
    }

    public function formatAuthorizedMethod(): \ReflectionMethod
    {
        $method = 'authorized' . $this->getAction();
        try {
            $reflectionMethod = new \ReflectionMethod($this, $method);
            if ($reflectionMethod->getReturnType()->getName() !== 'bool') {
                throw new InvalidStateException(
                    sprintf('Method %s of %s should return bool.', $reflectionMethod->getName(), get_class($this))
                );
            }
            if ($reflectionMethod->isAbstract() || !$reflectionMethod->isPublic()) {
                throw new InvalidStateException(
                    sprintf(
                        'Method %s of %s should be public and not abstract.',
                        $reflectionMethod->getName(),
                        get_class($this)
                    )
                );
            }
        } catch (\ReflectionException $exception) {
            throw new InvalidStateException(
                sprintf('Presenter %s has not implemented method %s.', get_class($this), $method)
            );
        }
        return $reflectionMethod;
    }

    public function isAuthAllowed(AuthMethod $authMethod): bool
    {
        switch ($authMethod->value) {
            case AuthMethod::LOGIN:
            case AuthMethod::TOKEN:
                // TODO definovať kam sa dá prihlásiť tokenom!!!
                return true;
            case AuthMethod::HTTP:
                return false;
        }
        return false;
    }

    /**
     * @throws BadTypeException
     */
    public function handleAutocomplete(string $acName): void
    {
        ['acQ' => $acQ] = (array)json_decode($this->getHttpRequest()->getRawBody());
        $component = $this->getComponent($acName);
        if (!$component instanceof AutocompleteSelectBox) {
            throw new BadTypeException(AutocompleteSelectBox::class, $component);
        } else {
            $provider = $component->getDataProvider();
            $data = null;
            if ($provider instanceof FilteredDataProvider) {
                $data = $provider->getFilteredItems($acQ);
            }
            $response = new JsonResponse($data);
            $this->sendResponse($response);
        }
    }

    /**
     * @throws BadRequestException
     * @throws InvalidLinkException
     * @throws \ReflectionException
     */
    public function authorized(string $destination, ?array $args = null): bool
    {
        if (substr($destination, -1) === '!' || $destination === 'this') {
            $destination = $this->getAction(true);
        }

        $key = $destination . Utils::getFingerprint($args);
        if (!isset($this->authorizedCache[$key])) {
            /*
             * This part is extracted from Presenter::createRequest
             */
            $a = strrpos($destination, ':');
            if ($a === false) {
                $action = $destination;
                $presenter = $this->getName();
            } else {
                $action = (string)substr($destination, $a + 1);
                if ($destination[0] === ':') { // absolute
                    if ($a < 2) {
                        throw new InvalidLinkException("Missing presenter name in '$destination'.");
                    }
                    $presenter = substr($destination, 1, $a - 1);
                } else { // relative
                    $presenter = $this->getName();
                    $b = strrpos($presenter, ':');
                    if ($b === false) { // no module
                        $presenter = substr($destination, 0, $a);
                    } else { // with module
                        $presenter = substr($presenter, 0, $b + 1) . substr($destination, 0, $a);
                    }
                }
            }

            /*
             * Now create a mock presenter and evaluate accessibility.
             */
            $baseParams = $this->getParameters();
            $testedPresenter = $this->presenterBuilder->preparePresenter($presenter, $action, $args, $baseParams);

            try {
                $testedPresenter->checkRequirements($testedPresenter->getReflection());
                $this->authorizedCache[$key] = $testedPresenter->authorized;
            } catch (BadRequestException $exception) {
                $this->authorizedCache[$key] = false;
            }
        }
        return $this->authorizedCache[$key];
    }

    /**
     * @throws UnsupportedLanguageException
     */
    protected function startup(): void
    {
        parent::startup();
        if (!isset($this->language)) {
            $this->language = $this->selectLang();
            $this->translator->setLang($this->language);
        }
    }

    /**
     * @throws UnsupportedLanguageException
     */
    private function selectLang(): string
    {
        $candidate = $this->getUserPreferredLang() ?? $this->lang;
        $supportedLanguages = $this->translator->getSupportedLanguages();
        if (!$candidate || !in_array($candidate, $supportedLanguages)) {
            $candidate = $this->getHttpRequest()->detectLanguage($supportedLanguages);
        }
        if (!$candidate) {
            $candidate = $this->getContext()->getParameters()['localization']['defaultLanguage'];
        }
        // final check
        if (!in_array($candidate, $supportedLanguages)) {
            throw new UnsupportedLanguageException($candidate);
        }
        return $candidate;
    }

    private function getUserPreferredLang(): ?string
    {
        $person = $this->getLoggedPerson();
        if ($person) {
            return $person->getPreferredLang();
        }
        return null;
    }


    protected function getLoggedPerson(): ?PersonModel
    {
        /**@var LoginModel $login */
        $login = $this->getUser()->getIdentity();
        return $this->getUser()->isLoggedIn() ? $login->person : null;
    }

    protected function createTemplate(): Template
    {
        $template = parent::createTemplate();
        $template->setTranslator($this->translator);
        return $template;
    }

    private function getTheme(): string
    {
        $person = $this->getLoggedPerson();
        if (!$person) {
            return 'light';
        }
        $info = $person->getInfo();
        if (!$info) {
            return 'light';
        }
        return $info->theme ?? 'light';
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->pageTitle = $this->getTitle();
        $this->template->lang = $this->getLang();
        $this->template->navRoots = $this->getNavRoots();
        $this->template->styleId = $this->getStyleId();
        $this->template->theme = $this->getTheme();
    }

    public function getTitle(): PageTitle
    {
        $reflection = new \ReflectionClass($this);
        try {
            $reflectionMethod = $reflection->getMethod('title' . $this->getView());
            if ($reflectionMethod->isAbstract() || !$reflectionMethod->isPublic()) {
                throw new InvalidStateException(
                    sprintf(
                        'Method %s of %s should be public and not abstract.',
                        $reflectionMethod->getName(),
                        get_class($this)
                    )
                );
            }
            $pageTitle = $reflectionMethod->invoke($this);
            $pageTitle->subTitle = $pageTitle->subTitle ?? $this->getSubTitle();
        } catch (\ReflectionException$exception) {
            throw new InvalidStateException(
                sprintf('Missing or invalid %s method in %s', 'title' . $this->getView(), $reflection->getName())
            );
        }
        return $pageTitle;
    }

    protected function getSubTitle(): ?string
    {
        return null;
    }

    protected function getStyleId(): string
    {
        return 'default';
    }

    public function getLang(): string
    {
        return $this->language;
    }

    protected function getNavRoots(): array
    {
        return [];
    }

    public function getContext(): Container
    {
        return $this->diContainer;
    }

    protected function createComponentNavigationChooser(): NavigationChooserComponent
    {
        return new NavigationChooserComponent($this->getContext());
    }

    protected function createComponentPrinter(): ColumnRendererComponent
    {
        return new ColumnRendererComponent($this->getContext());
    }

    protected function createComponentColumnTable(): ColumnTableComponent
    {
        return new ColumnTableComponent($this->getContext());
    }

    protected function createComponentLinkPrinter(): LinkPrinterComponent
    {
        return new LinkPrinterComponent($this->getContext());
    }

    final protected function createComponentLanguageChooser(): LanguageChooserComponent
    {
        return new LanguageChooserComponent($this->getContext(), $this->language, !$this->getUserPreferredLang());
    }

    /**
     * @throws \Exception
     */
    private function tryAuthToken(): void
    {
        $tokenData = $this->getParameter(TokenAuthenticator::PARAM_AUTH_TOKEN);

        if (!$tokenData) {
            return;
        }

        try {
            $login = $this->tokenAuthenticator->authenticate($tokenData);
            Debugger::log(sprintf('%s signed in using token %s.', $login->login, $tokenData), 'token-login');
            $this->flashMessage(_('Successful token authentication.'), Message::LVL_INFO);
            $this->getUser()->login($login);
            $this->redirect('this');
        } catch (AuthenticationException $exception) {
            $this->flashMessage($exception->getMessage(), Message::LVL_ERROR);
        }
    }

    /**
     * @throws \Exception
     */
    private function tryHttpAuth(): void
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            $this->httpAuthPrompt();
            return;
        }
        try {
            $login = $this->passwordAuthenticator->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            Debugger::log(sprintf('%s signed in using HTTP authentication.', $login), 'http-login');
            $this->getUser()->login($login);
            $method = $this->formatAuthorizedMethod();
            $this->authorized = $method->invoke($this);
        } catch (AuthenticationException $exception) {
            $this->httpAuthPrompt();
        }
    }

    private function httpAuthPrompt(): void
    {
        $realm = $this->getHttpRealm();
        if ($realm && $this->requiresLogin()) {
            header('WWW-Authenticate: Basic realm="' . $realm . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo '<h1>Unauthorized</h1>';
            exit;
        }
    }

    protected function getHttpRealm(): ?string
    {
        return null;
    }

    /**
     * This method may be override, however only simple conditions
     * can be checked there -- user session is not prepared at the
     * moment of the call.
     */
    public function requiresLogin(): bool
    {
        return true;
    }

    private function optionalLoginRedirect(): void
    {
        if (!$this->requiresLogin()) {
            return;
        }
        $this->redirect(
            ':Core:Authentication:login',
            [
                'backlink' => $this->storeRequest(),
                AuthenticationPresenter::PARAM_REASON => $this->getUser()->logoutReason,
            ]
        );
    }
}
