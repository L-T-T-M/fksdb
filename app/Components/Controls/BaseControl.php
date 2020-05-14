<?php

namespace FKSDB\Components\Controls;

use Nette\Application\UI\Control;
use Nette\DI\Container;
use Nette\Localization\ITranslator;
use Nette\Templating\FileTemplate;
use Nette\Templating\ITemplate;

/**
 * Class BaseControl
 * @package FKSDB\Components\Controls
 * @property FileTemplate $template
 */
abstract class BaseControl extends Control {
    /**
     * @var Container
     */
    private $context;

    /**
     * SubmitsTableControl constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct();
        $this->context = $container;
    }

    /**
     * @param null $class
     * @return ITemplate
     */
    protected function createTemplate($class = NULL) {
        $template = parent::createTemplate($class);
        /** @var ITranslator $translator */
        $translator = $this->getContext()->getByType(ITranslator::class);
        $template->setTranslator($translator);
        return $template;
    }

    /**
     * @return Container
     */
    final protected function getContext() {
        return $this->context;
    }
}
