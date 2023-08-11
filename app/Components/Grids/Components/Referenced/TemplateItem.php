<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids\Components\Referenced;

use FKSDB\Components\Controls\ColumnPrinter\ColumnRendererComponent;
use FKSDB\Components\Grids\Components\BaseItem;
use FKSDB\Models\Exceptions\BadTypeException;
use Fykosak\NetteORM\Model;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

/**
 * @template M of \Fykosak\NetteORM\Model
 * @template MH of \Fykosak\NetteORM\Model
 * @phpstan-extends BaseItem<M>
 */
class TemplateItem extends BaseItem
{
    protected string $templateString;
    protected ?string $titleString;
    /** @var (callable(M):M)|null */
    protected $modelAccessorHelper = null;

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     * @phpstan-param (callable(M):M)|null $modelAccessorHelper
     */
    public function __construct(
        Container $container,
        string $templateString,
        ?string $titleString = null,
        ?callable $modelAccessorHelper = null
    ) {
        $printer = new ColumnRendererComponent($container);
        parent::__construct(
            $container,
            $titleString
                ? new Title(null, $printer->renderToString($titleString, null, null))
                : new Title(null, '')
        );
        $this->templateString = $templateString;
        $this->titleString = $titleString;
        $this->modelAccessorHelper = $modelAccessorHelper;
    }

    /**
     * @param M|null $model
     */
    public function render(?Model $model, ?int $userPermission): void
    {
        $model = isset($this->modelAccessorHelper) ? ($this->modelAccessorHelper)($model) : $model;
        $this->doRender($model, $userPermission, [
            'templateString' => $this->templateString,
            'titleString' => $this->titleString,
        ]);
    }

    protected function createComponentPrinter(): ColumnRendererComponent
    {
        return new ColumnRendererComponent($this->getContext());
    }

    protected function getTemplatePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'template.latte';
    }
}
