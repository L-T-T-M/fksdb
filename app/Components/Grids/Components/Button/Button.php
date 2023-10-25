<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids\Components\Button;

use FKSDB\Components\Grids\Components\BaseItem;
use Fykosak\NetteORM\Model;
use Fykosak\Utils\UI\Title;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Container;
use Nette\Utils\Html;

/**
 * @phpstan-template TModel of \Fykosak\NetteORM\Model
 * @phpstan-extends BaseItem<TModel>
 */
class Button extends BaseItem
{
    /** @phpstan-var callable(TModel):array{string,array<string,scalar>} */
    private $linkCallback;
    /** @phpstan-var (callable(TModel,int):bool)|null */
    private $showCallback;
    private ?string $buttonClassName;
    private Title $buttonLabel;
    private Control $control;

    /**
     * @phpstan-param callable(TModel):array{string,array<string,scalar>} $linkCallback
     * @phpstan-param (callable(TModel,int):bool)|null $showCallback
     */
    public function __construct(
        Container $container,
        Control $control,
        Title $buttonLabel,
        callable $linkCallback,
        ?string $buttonClassName = null,
        ?callable $showCallback = null
    ) {
        parent::__construct($container);
        $this->linkCallback = $linkCallback;
        $this->buttonClassName = $buttonClassName;
        $this->showCallback = $showCallback;
        $this->buttonLabel = $buttonLabel;
        $this->control = $control;
    }

    /**
     * @phpstan-param TModel $model
     * @throws InvalidLinkException
     */
    public function render(Model $model, int $userPermission): void
    {
        [$destination, $params] = ($this->linkCallback)($model);
        $html = Html::el('a');
        if (!isset($this->showCallback) || ($this->showCallback)($model, $userPermission)) {
            $html->addAttributes([
                'href' => $this->control->link($destination, $params),
                'class' => $this->buttonClassName ?? 'btn btn-sm me-1 btn-outline-secondary',
            ]);
            $html->setHtml($this->buttonLabel->toHtml());
        }
        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . '../html.latte', ['html' => $html]);
    }

    public function renderTitle(): void
    {
    }
}
