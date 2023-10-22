<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\ColumnPrinter;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\FieldLevelPermission;
use FKSDB\Models\ORM\ReflectionFactory;
use Fykosak\NetteORM\Exceptions\CannotAccessModelException;
use Fykosak\NetteORM\Model;
use Fykosak\Utils\BaseComponent\BaseComponent;
use Nette\Application\UI\InvalidLinkException;

class ColumnRendererComponent extends BaseComponent
{
    private ReflectionFactory $reflectionFactory;

    final public function injectTableReflectionFactory(ReflectionFactory $reflectionFactory): void
    {
        $this->reflectionFactory = $reflectionFactory;
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    final public function renderTemplateString(string $templateString, Model $model, ?int $userPermission): void
    {
        $this->template->render(
            __DIR__ . DIRECTORY_SEPARATOR . 'string.latte',
            ['html' => $this->renderToString($templateString, $model, $userPermission)]
        );
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    final public function renderToString(string $templateString, ?Model $model, ?int $userPermission): string
    {
        return preg_replace_callback(
            '/@([a-z_]+)\.([a-z_]+)(:([a-zA-Z]+))?/',
            function (array $match) use ($model, $userPermission) {
                [, $table, $field, , $render] = $match;
                $factory = $this->reflectionFactory->loadColumnFactory($table, $field);
                switch ($render) {
                    default:
                    case 'value':
                        if (!$model) {
                            throw new \InvalidArgumentException('"value" is available only with model');
                        }
                        return (string)$factory->render($model, $userPermission);
                    case 'title':
                        return $factory->getTitle();
                    case 'description':
                        return (string)$factory->getDescription();
                }
            },
            $templateString
        );
    }

    /**
     * @throws CannotAccessModelException
     * @deprecated
     */
    final public function renderListItem(
        string $field,
        Model $model,
        int $userPermission = FieldLevelPermission::ALLOW_FULL
    ): void {
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'layout.listItem.latte', [
            'model' => $model,
            'userPermission' => $userPermission,
            'name' => $field,
        ]);
    }
    /**
     * @throws BadTypeException
     * @throws CannotAccessModelException
     * @throws InvalidLinkException
     * @throws \ReflectionException
     */
    final public function renderButton(string $linkId, Model $model): void
    {
        $factory = $this->reflectionFactory->loadLinkFactory(...explode('.', $linkId, 2));
        $this->template->render(
            __DIR__ . DIRECTORY_SEPARATOR . 'layout.link.latte',
            [
                'title' => $factory->getTitle(),
                'link' => $factory->create($this->getPresenter(), $model),
            ]
        );
    }
}
