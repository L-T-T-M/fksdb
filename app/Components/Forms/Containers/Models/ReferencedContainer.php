<?php

namespace FKSDB\Components\Forms\Containers\Models;

use FKSDB\Application\IJavaScriptCollector;
use FKSDB\DBReflection\ColumnFactories\AbstractColumnException;
use FKSDB\DBReflection\OmittedControlException;
use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\ORM\IModel;
use Nette\Application\BadRequestException;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\JsonException;
use Nette\DI\Container as DIContainer;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
abstract class ReferencedContainer extends ContainerWithOptions {

    const ID_MASK = 'frm%s-%s';
    const CSS_AJAX = 'ajax';
    const CONTROL_COMPACT = '_c_compact';
    const SUBMIT_CLEAR = '__clear';

    /** @var ReferencedId */
    private $referencedId;
    /** @var bool */
    protected $allowClear = true;
    /** @var bool */
    private $attachedJS = false;

    /**
     * ReferencedContainer constructor.
     * @param DIContainer $container
     * @param bool $allowClear
     */
    public function __construct(DIContainer $container, bool $allowClear) {
        parent::__construct($container);
        $this->monitor(IJavaScriptCollector::class, function (IJavaScriptCollector $collector) {
            if (!$this->attachedJS) {
                $this->attachedJS = true;
                $collector->registerJSFile('js/referencedContainer.js');
                $this->updateHtmlData();
            }
        }, function (IJavaScriptCollector $collector) {
            $this->attachedJS = false;
            $collector->unregisterJSFile('js/referencedContainer.js');
        });
        $this->createClearButton();
        $this->createCompactValue();

        $this->setAllowClear($allowClear);

    }

    public function getReferencedId(): ReferencedId {
        return $this->referencedId;
    }

    public function setReferencedId(ReferencedId $referencedId): void {
        $this->referencedId = $referencedId;
    }

    public function setDisabled(bool $value = true): void {
        /** @var BaseControl $control */
        foreach ($this->getControls() as $control) {
            $control->setDisabled($value);
        }
    }

    protected function setAllowClear(bool $allowClear): void {
        $this->allowClear = $allowClear;
        /** @var SubmitButton $control */
        $control = $this->getComponent(self::SUBMIT_CLEAR);
        $control->setOption('visible', $allowClear);
    }

    /**
     * @param IComponent $child
     * @return void
     */
    protected function validateChildComponent(IComponent $child) {
        if (!$child instanceof BaseControl && !$child instanceof ContainerWithOptions) {
            throw new InvalidStateException(__CLASS__ . ' can contain only components with get/set option funcionality, ' . get_class($child) . ' given.');
        }
    }

    /**
     * @param array|ArrayHash $conflicts
     * @param null $container
     */
    public function setConflicts($conflicts, $container = null): void {
        $container = $container ?: $this;
        foreach ($conflicts as $key => $value) {
            $component = $container->getComponent($key, false);
            if ($component instanceof Container) {
                $this->setConflicts($value, $component);
            } elseif ($component instanceof BaseControl) {
                $component->addError(null);
            }
        }
    }

    private function createClearButton() {
        $submit = $this->addSubmit(self::SUBMIT_CLEAR, 'X')
            ->setValidationScope(false);
        $submit->getControlPrototype()->class[] = self::CSS_AJAX;
        $submit->onClick[] = function () {
            if ($this->allowClear) {
                $this->referencedId->setValue(null);
                $this->referencedId->invalidateFormGroup();
            }
        };
    }

    private function createCompactValue() {
        $this->addHidden(self::CONTROL_COMPACT);
    }

    /**
     * @note Must be called after a form is attached.
     */
    private function updateHtmlData() {
        $this->setOption('id', sprintf(self::ID_MASK, $this->getForm()->getName(), $this->lookupPath('Nette\Forms\Form')));
        $referencedId = $this->referencedId->getHtmlId();
        $this->setOption('data-referenced-id', $referencedId);
        $this->setOption('data-referenced', 1);
    }

    /**
     * @return void
     * @throws AbstractColumnException
     * @throws BadRequestException
     * @throws BadTypeException
     * @throws JsonException
     * @throws NotImplementedException
     * @throws OmittedControlException
     */
    abstract protected function configure(): void;

    /**
     * @param IModel|null $model
     * @param string $mode
     * @return void
     */
    abstract public function setModel(IModel $model = null, string $mode = ReferencedId::MODE_NORMAL): void;
}
