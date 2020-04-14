<?php

namespace FKSDB\Components\Grids\Events\Application;

use Closure;
use FKSDB\Events\Model\Holder\Holder;
use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Components\Forms\Containers\Models\ContainerWithOptions;
use FKSDB\Components\Grids\BaseGrid;
use FKSDB\NotImplementedException;
use FKSDB\ORM\Models\ModelEvent;
use Nette\Application\BadRequestException;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Utils\Html;
use NiftyGrid\DuplicateColumnException;

/**
 * Class AbstractApplicationGrid
 * @package FKSDB\Components\Grids\Events\Application
 */
abstract class AbstractApplicationGrid extends BaseGrid {
    /** @var ModelEvent */
    protected $event;
    /** @var Holder */
    private $holder;

    /**
     * AbstractApplicationGrid constructor.
     * @param ModelEvent $event
     * @param Holder $holder
     * @param Container $container
     */
    public function __construct(ModelEvent $event, Holder $holder, Container $container) {
        parent::__construct($container);
        $this->event = $event;
        $this->holder = $holder;
    }

    /**
     * @return Selection
     */
    abstract protected function getSource(): Selection;

    /**
     * @return FormControl
     * @throws BadRequestException
     */
    protected function createComponentSearchForm(): FormControl {
        $query = $this->getSource()->select('count(*) AS count,status.*')->group('status');

        $states = [];
        foreach ($query as $row) {
            $states[] = [
                'state' => $row->status,
                'count' => $row->count,
                'description' => $row->description,
            ];
        }

        $control = new FormControl();
        $form = $control->getForm();
        $stateContainer = new ContainerWithOptions();
        $stateContainer->setOption('label', _('States'));
        foreach ($states as $state) {
            $label = Html::el('span')
                ->addHtml(Html::el('b')->addText($state['state']))
                ->addText(': ')
                ->addHtml(Html::el('i')->addText(_($state['description'])))
                ->addText(' (' . $state['count'] . ')');
            $stateContainer->addCheckbox(\str_replace('.', '__', $state['state']), $label);
        }
        $form->addComponent($stateContainer, 'status');
        $form->addSubmit('submit', _('Apply filter'));
        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();
            $this->searchTerm = $values;
            $this->dataSource->applyFilter($values);
            $count = $this->dataSource->getCount();
            $this->getPaginator()->itemCount = $count;
        };
        return $control;
    }

    /**
     * @return Closure
     */
    public function getFilterCallBack(): Closure {
        return function (Selection $table, $value) {
            $states = [];
            foreach ($value->status as $state => $value) {
                if ($value) {
                    $states[] = \str_replace('__', '.', $state);
                }
            }
            if (\count($states)) {
                $table->where('status IN ?', $states);
            }
        };
    }

    /**
     * @return array
     */
    abstract protected function getHoldersColumns(): array;

    /**
     * @param array $fields
     * @throws DuplicateColumnException
     * @throws NotImplementedException
     */
    protected function addColumns(array $fields) {
        parent::addColumns($fields);

        $holderFields = $this->holder->getPrimaryHolder()->getFields();

        foreach ($holderFields as $name => $def) {
            if (\in_array($name, $this->getHoldersColumns())) {
                $this->addReflectionColumn($this->getTableName(), $name, $this->getModelClassName());
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getTableName(): string;
}
