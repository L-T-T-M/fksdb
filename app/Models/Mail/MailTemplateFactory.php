<?php

declare(strict_types=1);

namespace FKSDB\Models\Mail;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Modules\Core\BasePresenter;
use Nette\Application\Application;
use Nette\Application\UI\Template;
use Nette\Http\IRequest;
use Nette\InvalidArgumentException;
use Nette\Localization\Translator;
use Tracy\Debugger;

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
    public function createLoginInvitation(?string $lang, array $data): Template
    {
        return $this->createWithParameters('loginInvitation', $lang, $data);
    }

    /**
     * @throws BadTypeException
     */
    public function createPasswordRecovery(string $lang, array $data): Template
    {
        return $this->createWithParameters(__DIR__ . DIRECTORY_SEPARATOR . 'recovery', $lang, $data);
    }

    /**
     * @throws BadTypeException
     */
    public function createWithParameters(string $templateFile, ?string $lang, array $data = []): Template
    {
        $template = $this->createFromFile($templateFile, $lang);
        $template->setTranslator($this->translator);
        foreach ($data as $key => $value) {
            $template->{$key} = $value;
        }
        return $template;
    }

    /**
     * @throws BadTypeException
     */
    final public function createFromFile(string $filename, ?string $lang): Template
    {
        $presenter = $this->application->getPresenter();
        if (($lang === null) && !$presenter instanceof BasePresenter) {
            throw new BadTypeException(BasePresenter::class, $presenter);
        }
        if ($lang === null) {
            $lang = $presenter->getLang();
        }
        $filename = "$filename.$lang.latte";
        if (realpath($filename) !== $filename) {
            $filename = $this->templateDir . DIRECTORY_SEPARATOR . $filename;
        }

        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Cannot find template '$filename.$lang'.");
        }
        $template = $presenter->getTemplateFactory()->createTemplate();
        $template->setFile($filename);

        if ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
            $template->getLatte()->addProvider('uiControl', $presenter);
        }
        $template->control = $presenter;
        $template->baseUri = $this->request->getUrl()->getBaseUrl();
        return $template;
    }
}
