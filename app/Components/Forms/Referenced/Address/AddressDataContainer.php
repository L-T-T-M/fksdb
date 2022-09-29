<?php

declare(strict_types=1);

namespace FKSDB\Components\Forms\Referenced\Address;

use FKSDB\Components\Forms\Containers\Models\ReferencedContainer;
use FKSDB\Components\Forms\Controls\WriteOnly\WriteOnlyInput;
use FKSDB\Models\ORM\Models\AddressModel;
use FKSDB\Models\ORM\Services\RegionService;
use Fykosak\NetteORM\Model;
use Nette\Application\UI\Form;
use Nette\DI\Container;

class AddressDataContainer extends ReferencedContainer
{
    private RegionService $regionService;
    private bool $writeOnly;
    private bool $required;

    public function __construct(Container $container, bool $writeOnly = true, bool $required = false)
    {
        parent::__construct($container, false);
        $this->writeOnly = $writeOnly;
        $this->required = $required;
    }

    public function inject(RegionService $regionService): void
    {
        $this->regionService = $regionService;
    }

    protected function configure(): void
    {
        $firstRow = new WriteOnlyInput(_('First row'));
        $firstRow->setOption('description', _('First optional row of the address (e.g. title)'));
        $firstRow->setWriteOnly($this->writeOnly);
        $this->addComponent($firstRow, 'first_row');

        $secondRow = new WriteOnlyInput(_('Second row'));
        $secondRow->setOption('description', _('Second optional row of the address (used rarely)'));
        $secondRow->setWriteOnly($this->writeOnly);
        $this->addComponent($secondRow, 'second_row');

        $target = new WriteOnlyInput(_('Place'));
        $target->setOption('description', _('Typically street and (house) number.'));
        $target->setWriteOnly($this->writeOnly);
        if ($this->required) {
            $target->addRule(Form::FILLED, _('The place is required.'));
        }
        $this->addComponent($target, 'target');

        $city = new WriteOnlyInput(_('City'));
        $city->setWriteOnly($this->writeOnly);
        if ($this->required) {
            $city->addRule(Form::FILLED, _('City is required.'));
        }
        $this->addComponent($city, 'city');

        $postalCode = new WriteOnlyInput(_('postal code'));
        $postalCode->addRule(Form::MAX_LENGTH, _('Max length reached'), 5)
            ->setOption('description', _('Without spaces. For the Czech Republic or Slovakia only.'));
        $postalCode->setWriteOnly($this->writeOnly);
        $this->addComponent($postalCode, 'postal_code');


        $country = $this->addSelect('region_id', _('Country'));
        $country->setItems($this->regionService->getCountries()->order('name')->fetchPairs('region_id', 'name'));
        $country->setPrompt(_('Detect country from postal code (CR, SK only)'));
    }

    public function setModel(?Model $model, string $mode): void
    {
        if ($model instanceof AddressModel) {
            $this->setValues($model->toArray());
        }
    }
}
