<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids\Components\Button;

use FKSDB\Components\Grids\Components\BaseItem;
use Fykosak\NetteORM\Model;
use Fykosak\Utils\UI\Title;
use Nette\Application\UI\Control;
use Nette\DI\Container;

abstract class Button extends BaseItem
{
    /** @var callable */
    private $linkCallback;
    /** @var callable|null */
    private $showCallback;
    private ?string $buttonClassName;

    public function __construct(
        Container $container,
        Title $title,
        callable $linkCallback,
        ?string $buttonClassName = null,
        ?callable $showCallback = null
    ) {
        parent::__construct($container, $title);
        $this->linkCallback = $linkCallback;
        $this->buttonClassName = $buttonClassName;
        $this->showCallback = $showCallback;
    }

    protected function getTemplatePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'button.latte';
    }

    public function render(?Model $model, ?int $userPermission): void
    {
        $this->template->linkControl = $this->getLinkControl();
        $this->template->show = isset($this->showCallback) ? ($this->showCallback)($model, $userPermission) : true;
        [$this->template->destination, $this->template->params] = ($this->linkCallback)($model);
        $this->template->buttonClassName = $this->buttonClassName ?? 'btn btn-sm me-1 btn-outline-secondary';
        parent::render($model, $userPermission);
    }

    abstract protected function getLinkControl(): Control;
}
