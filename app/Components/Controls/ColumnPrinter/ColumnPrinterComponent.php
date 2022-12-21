<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\ColumnPrinter;

use Fykosak\Utils\BaseComponent\BaseComponent;
use Fykosak\NetteORM\Exceptions\CannotAccessModelException;
use FKSDB\Models\ORM\ORMFactory;
use FKSDB\Models\ORM\FieldLevelPermission;
use FKSDB\Models\Exceptions\BadTypeException;
use Fykosak\NetteORM\Model;
use Tracy\Debugger;

class ColumnPrinterComponent extends BaseComponent
{

    private ORMFactory $tableReflectionFactory;

    final public function injectTableReflectionFactory(ORMFactory $tableReflectionFactory): void
    {
        $this->tableReflectionFactory = $tableReflectionFactory;
    }

    /**
     * @throws BadTypeException
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     */
    final public function render(string $field, Model $model, int $userPermission): void
    {
        $factory = $this->tableReflectionFactory->loadColumnFactory(...explode('.', $field));
        $this->template->title = $factory->getTitle();
        $this->template->description = $factory->getDescription();
        $this->template->html = $factory->render($model, $userPermission);
        $this->template->render();
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    final public function renderTemplateString(string $templateString, Model $model, int $userPermission): void
    {
        $this->template->html = preg_replace_callback(
            '/@([a-z_]+).([a-z_]+)(:([a-zA-Z]+))?/',
            function (array $match) use ($model, $userPermission) {
                [, $table, $field, , $render] = $match;
                $factory = $this->tableReflectionFactory->loadColumnFactory($table, $field);
                switch ($render) {
                    default:
                    case 'value':
                        return $factory->render($model, $userPermission);
                    case 'title':
                        return $factory->getTitle();
                    case 'description':
                        return $factory->getDescription();
                }
            },
            $templateString
        );
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'string.latte');
    }

    /**
     * @throws BadTypeException
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     */
    final public function renderRow(
        string $field,
        Model $model,
        int $userPermission = FieldLevelPermission::ALLOW_FULL
    ): void {
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'layout.row.latte');
        $this->render($field, $model, $userPermission);
    }

    /**
     * @throws BadTypeException
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     */
    final public function renderListItem(
        string $field,
        Model $model,
        int $userPermission = FieldLevelPermission::ALLOW_FULL
    ): void {
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'layout.listItem.latte');
        $this->render($field, $model, $userPermission);
    }

    /**
     * @throws BadTypeException
     * @throws CannotAccessModelException
     * @throws \ReflectionException
     */
    final public function renderOnlyValue(
        string $field,
        Model $model,
        int $userPermission = FieldLevelPermission::ALLOW_FULL
    ): void {
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'layout.onlyValue.latte');
        $this->render($field, $model, $userPermission);
    }
}
