<?php

declare(strict_types=1);

namespace FKSDB\Components\Grids;

use FKSDB\Models\Exceptions\BadTypeException;
use Fykosak\Utils\Logging\Message;
use FKSDB\Models\ORM\Models\PersonModel;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;

class PersonRelatedGrid extends BaseGrid
{

    protected PersonModel $person;
    protected array $definition;
    protected int $userPermissions;

    public function __construct(string $section, PersonModel $person, int $userPermissions, Container $container)
    {
        $this->definition = $container->getParameters()['components'][$section];
        parent::__construct($container);
        $this->person = $person;
        $this->userPermissions = $userPermissions;
    }

    protected function setData(): void
    {
        $this->data = $this->person->related($this->definition['table']);
        if ($this->definition['minimalPermission'] > $this->userPermissions) {
            $this->data->where('1=0');
            $this->flashMessage('Access denied', Message::LVL_ERROR);
        }
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(Presenter $presenter): void
    {
        $this->paginate = false;
        parent::configure($presenter);
        $this->addColumns($this->definition['rows']);
        foreach ($this->definition['links'] as $link) {
            $this->addORMLink($link);
        }
        // $this->addCSVDownloadButton();
    }
}
