<?php

namespace FKSDB\Modules\WarehouseModule;

use FKSDB\Components\Grids\Warehouse\ItemsGrid;
use FKSDB\Exceptions\NotImplementedException;
use FKSDB\Modules\Core\PresenterTraits\EntityPresenterTrait;
use FKSDB\ORM\Services\Warehouse\ServiceItem;
use FKSDB\UI\PageTitle;
use Nette\Application\UI\Control;
use Nette\Security\IResource;

/**
 * Class ItemPresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
class ItemPresenter extends BasePresenter {
    use EntityPresenterTrait;

    private ServiceItem $serviceItem;

    public function injectService(ServiceItem $serviceItem): void {
        $this->serviceItem = $serviceItem;
    }

    protected function titleList(): void {
        $this->setPageTitle(new PageTitle(_('Items'), 'fa fa-truck'));
    }

    protected function titleEdit(): void {
        $this->setPageTitle(new PageTitle(_('Edit item'), 'fa fa-truck'));
    }

    protected function titleCreate(): void {
        $this->setPageTitle(new PageTitle(_('Create item'), 'fa fa-truck'));
    }

    protected function createComponentCreateForm(): Control {
        throw new NotImplementedException();
    }

    protected function createComponentEditForm(): Control {
        throw new NotImplementedException();
    }

    protected function createComponentGrid(): ItemsGrid {
        return new ItemsGrid($this->getContext(), $this->getContest());
    }

    protected function getORMService(): ServiceItem {
        return $this->serviceItem;
    }

    /**
     * @param IResource|string|null $resource
     * @param string|null $privilege
     * @return bool
     */
    protected function traitIsAuthorized($resource, ?string $privilege): bool {
        return $this->isAllowed($resource, $privilege);
    }
}
