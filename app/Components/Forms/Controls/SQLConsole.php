<?php

namespace FKSDB\Components\Forms\Controls;

use FKS\Application\IJavaScriptCollector;
use FKS\Application\IStylesheetCollector;
use Nette\Forms\Controls\TextArea;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 * 
 * 
 * @author Michal Koutný <michal@fykos.cz>
 */
class SQLConsole extends TextArea {

    const CSS_CLASS = 'sqlConsole';

    public function __construct($label = NULL, $cols = NULL, $rows = NULL) {
        parent::__construct($label, $cols, $rows);
        $this->monitor('FKS\Application\IJavaScriptCollector');
        $this->monitor('FKS\Application\IStylesheetCollector');
    }

    private $attachedJS = false;
    private $attachedCSS = false;

    protected function attached($component) {
        parent::attached($component);
        if (!$this->attachedJS && $component instanceof IJavaScriptCollector) {
            $this->attachedJS = true;
            $component->registerJSFile('js/codemirror.min.js');
            $component->registerJSFile('js/sqlconsole.js');
        }
        if (!$this->attachedCSS && $component instanceof IStylesheetCollector) {
            $this->attachedCSS = true;
            $component->registerStylesheetFile('css/codemirror.css', array('screen', 'projection', 'tv'));
        }
    }

    public function getControl() {
        $control = parent::getControl();
        $control->class = self::CSS_CLASS;

        return $control;
    }

}
