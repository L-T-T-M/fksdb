<?php

declare(strict_types=1);

namespace FKSDB\Models\Mail;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Modules\Core\BasePresenter;
use Nette\Application\Application;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\IRequest;
use Nette\InvalidArgumentException;
use Nette\Localization\Translator;

class MailTemplateFactory
{

    /** without trailing slash */
    private string $templateDir;
    /** @var Application */
    private $application;

    private Translator $translator;
    private IRequest $request;

    public function __construct(
        string $templateDir,
        Application $application,
        Translator $translator,
        IRequest $request
    ) {
        $this->templateDir = $templateDir;
        $this->application = $application;
        $this->translator = $translator;
        $this->request = $request;
    }

    /**
     * @param Application $application
     * @internal For automated testing only.
     * @deprecated
     * TODO remove this!
     */
    final public function injectApplication($application): void
    {
        $this->application = $application;
    }

    /**
     * @throws BadTypeException
     */
    public function renderLoginInvitation(string $lang, array $data): string
    {
        return $this->create()->renderToString(
            __DIR__ . DIRECTORY_SEPARATOR . 'loginInvitation.' . $lang . '.latte',
            $data
        );
    }

    /**
     * @throws BadTypeException
     */
    public function renderPasswordRecovery(string $lang, array $data): string
    {
        return $this->create()->renderToString(
            __DIR__ . DIRECTORY_SEPARATOR . 'recovery.' . $lang . '.latte',
            $data
        );
    }

    /**
     * @throws BadTypeException
     */
    public function renderWithParameters(string $templateFile, ?string $lang, array $data = []): string
    {
        return $this->create()->renderToString($this->resolverFileName($templateFile, $lang), $data);
    }

    /**
     * @throws BadTypeException
     */
    private function resolverFileName(string $filename, ?string $lang): string
    {
        $presenter = $this->application->getPresenter();

        if (is_null($lang)) {
            if (!$presenter instanceof BasePresenter) {
                throw new BadTypeException(BasePresenter::class, $presenter);
            }
            $lang = $presenter->getLang();
        }
        $filename = "$filename.$lang.latte";
        if (file_exists($filename)) {
            return $filename;
        }
        $filename = $this->templateDir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filename)) {
            return $filename;
        }
        throw new InvalidArgumentException("Cannot find template '$filename.$lang'.");
    }

    /**
     * @throws BadTypeException
     */
    private function create(): Template
    {
        $presenter = $this->application->getPresenter();
        if (!$presenter instanceof BasePresenter) {
            throw new BadTypeException(BasePresenter::class, $presenter);
        }
        $template = $presenter->getTemplateFactory()->createTemplate();

        if (!$template instanceof Template) {
            throw new BadTypeException(Template::class, $template);
        }
        $template->getLatte()->addProvider('uiControl', $presenter);
        $template->control = $presenter;
        $template->baseUri = $this->request->getUrl()->getBaseUrl();
        $template->setTranslator($this->translator);
        return $template;
    }
}
