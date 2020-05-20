<?php

namespace FKSDB\Components\Events;

use FKSDB\Events\Machine\BaseMachine;
use FKSDB\Application\IJavaScriptCollector;
use FKSDB\Components\Controls\BaseControl;
use Nette\DI\Container;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class GraphComponent extends BaseControl {

    /**
     * @var BaseMachine
     */
    private $baseMachine;
    /** @var ExpressionPrinter */
    private $expressionPrinter;

    /**
     * GraphComponent constructor.
     * @param Container $container
     * @param BaseMachine $baseMachine
     * @param ExpressionPrinter $expressionPrinter
     */
    public function __construct(Container $container, BaseMachine $baseMachine, ExpressionPrinter $expressionPrinter) {
        parent::__construct($container);
        $this->monitor(IJavaScriptCollector::class);
        $this->baseMachine = $baseMachine;
        $this->expressionPrinter = $expressionPrinter;
    }

    /** @var bool */
    private $attachedJS = false;

    /**
     * @param $obj
     */
    protected function attached($obj) {
        parent::attached($obj);
        if (!$this->attachedJS && $obj instanceof IJavaScriptCollector) {
            $this->attachedJS = true;
            $obj->registerJSFile('js/graph/raphael.js');
            $obj->registerJSFile('js/graph/dracula_graffle.js');
            $obj->registerJSFile('js/graph/dracula_graph.js');
            $obj->registerJSFile('js/eventModelGraph.js');
        }
    }

    public function render() {
        $this->template->nodes = json_encode($this->prepareNodes());
        $this->template->edges = json_encode($this->prepareTransitions());
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'GraphComponent.latte');
        $this->template->id = $this->getHtmlId();
        $this->template->render();
    }

    /**
     * @return string
     */
    private function getHtmlId() {
        return 'graph-' . $this->getUniqueId();
    }

    /**
     * @return array
     */
    private function getAllStates(): array {
        return array_merge(array_keys($this->baseMachine->getStates()), [BaseMachine::STATE_INIT, BaseMachine::STATE_TERMINATED]);
    }

    /**
     * @return array
     */
    private function prepareNodes(): array {
        $states = $this->getAllStates();
        $nodes = [];
        foreach ($states as $state) {

            $nodes[] = [
                'id' => $state,
                'label' => $this->baseMachine->getStateName($state),
                'type' => $state === BaseMachine::STATE_INIT ? 'init' : $state === BaseMachine::STATE_TERMINATED ? 'terminated' : 'default'
            ];
        }
        return $nodes;
    }

    /**
     * @return array
     */
    private function prepareTransitions(): array {
        $states = $this->getAllStates();
        $edges = [];
        foreach ($this->baseMachine->getTransitions() as $transition) {
            foreach ($states as $state) {
                if ($transition->matches($state)) {
                    $edges[] = [
                        'source' => $state,
                        'target' => $transition->getTarget(),
                        'condition' => $this->expressionPrinter->printExpression($transition->getCondition()),
                        'label' => $transition->getLabel(),
                    ];

                }
            }
        }
        return $edges;
    }
}

