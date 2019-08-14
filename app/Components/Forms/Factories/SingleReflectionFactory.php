<?php

namespace FKSDB\Components\Forms\Factories;

use Exception;
use FKSDB\Components\DatabaseReflection\AbstractRow;
use FKSDB\Components\DatabaseReflection\Org\SinceRow;
use FKSDB\Components\DatabaseReflection\PersonHistory\StudyYearRow;
use FKSDB\Components\Forms\Containers\ModelContainer;
use Nette\Forms\Controls\BaseControl;

/**
 * Class SingleReflectionFactory
 * @package FKSDB\Components\Forms\Factories
 */
abstract class SingleReflectionFactory {
    /**
     * @var TableReflectionFactory
     */
    protected $tableReflectionFactory;

    /**
     * PersonHistoryFactory constructor.
     * @param TableReflectionFactory $tableReflectionFactory
     */
    public function __construct(TableReflectionFactory $tableReflectionFactory) {
        $this->tableReflectionFactory = $tableReflectionFactory;
    }

    /**
     * @return string
     */
    abstract protected function getTableName(): string;

    /**
     * @param string $fieldName
     * @return AbstractRow|SinceRow|StudyYearRow
     * @throws Exception
     */
    protected function loadFactory(string $fieldName): AbstractRow {
        return $this->tableReflectionFactory->loadService($this->getTableName(), $fieldName);
    }

    /**
     * @param string $fieldName
     * @return BaseControl
     * @throws Exception
     */
    public function createField(string $fieldName): BaseControl {
        return $this->loadFactory($fieldName)->createField();
    }

    /**
     * @param array $fields
     * @return ModelContainer
     * @throws Exception
     */
    public function createContainer(array $fields): ModelContainer {
        $container = new ModelContainer();

        foreach ($fields as $field) {
            $control = $this->createField($field);
            $container->addComponent($control, $field);
        }
        return $container;
    }
}
