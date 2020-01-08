<?php

namespace FKSDB\Components\Forms\Factories;

use FKSDB\Components\DatabaseReflection\AbstractRow;
use FKSDB\Components\DatabaseReflection\Links\AbstractLink;
use FKSDB\Components\DatabaseReflection\Links\Link;
use FKSDB\Components\DatabaseReflection\RowFactoryComponent;
use FKSDB\ORM\AbstractModelSingle;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Nette\Localization\ITranslator;
use Nette\SmartObject;
use Nette\Utils\Html;
use Tracy\Debugger;

/**
 * Class TableReflectionFactory
 * @package FKSDB\Components\Forms\Factories\PersonInfo
 */
final class TableReflectionFactory {
    use SmartObject;
    /**
     * @var Container
     */
    private $container;
    /**
     * @var ITranslator
     */
    private $translator;

    /**
     * PersonInfoFactory constructor.
     * @param Container $container
     * @param ITranslator $translator
     */
    public function __construct(Container $container, ITranslator $translator) {
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @return AbstractRow
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function loadService(string $tableName, string $fieldName): AbstractRow {
        $service = null;
        try {
            $service = $this->container->getService('DBReflection.' . $tableName . '.' . $fieldName);
        } catch (\Exception $exception) {

        }
        if (!$service) {
            $service = $this->container->getService('row.' . $tableName . '.' . $fieldName);
        }
        if (!$service instanceof AbstractRow) {
            throw new InvalidArgumentException('Field ' . $tableName . '.' . $fieldName . ' not exists');
        }
        return $service;
    }

    /**
     * @param $linkId
     * @return AbstractLink
     * @throws \Exception
     */
    public function loadLinkFactory($linkId): AbstractLink {
        $service = $this->container->getService('DBReflection.link.' . $linkId);
        if (!$service instanceof AbstractLink) {
            throw new InvalidArgumentException('LinkFactory ' . $linkId . ' not exists');
        }
        return $service;
    }

    /**
     * @param string $name
     * @param int $permissionLevel
     * @return RowFactoryComponent|null
     * @throws \Exception
     */
    public function createComponent(string $name, int $permissionLevel) {
        $parts = \explode('__', $name);
        if (\count($parts) === 3) {
            list($prefix, $tableName, $fieldName) = $parts;
            if ($prefix === 'valuePrinter') {
                $factory = $this->loadService($tableName, $fieldName);
                return new RowFactoryComponent($this->translator, $factory, $permissionLevel);
            }
        }
        return null;
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param AbstractModelSingle $modelSingle
     * @param int $userPermissionLevel
     * @return \Nette\Utils\Html
     * @throws \Exception
     */
    public function createGridValue(string $tableName, string $fieldName, AbstractModelSingle $modelSingle, int $userPermissionLevel): Html {
        return $this->loadService($tableName, $fieldName)->renderValue($modelSingle, $userPermissionLevel);
    }


    /**
     * @param array $rows
     * @return array
     */
    public static function parseRows(array $rows): array {
        $items = [];
        foreach ($rows as $item) {
            $items[] = self::parseRow($item);
        }
        return $items;
    }

    /**
     * @param string $row
     * @return array
     */
    public static function parseRow(string $row): array {
        return explode('.', $row);
    }
}
