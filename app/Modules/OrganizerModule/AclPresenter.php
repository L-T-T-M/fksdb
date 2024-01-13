<?php

declare(strict_types=1);

namespace FKSDB\Modules\OrganizerModule;

use FKSDB\Components\Grids\AclGrid;
use FKSDB\Modules\Core\PresenterTraits\NoContestAvailable;
use Fykosak\Utils\UI\PageTitle;

class AclPresenter extends BasePresenter
{
    /**
     * @throws NoContestAvailable
     */
    public function authorizedList(): bool
    {
        return $this->contestAuthorizator->isAllowed($this->getSelectedContest(), 'acl', $this->getSelectedContest());
    }

    public function titleList(): PageTitle
    {
        return new PageTitle(null, _('ACL list'), 'fas fa-user-lock');
    }

    /**
     * @throws NoContestAvailable
     */
    protected function createComponentGrid(): AclGrid
    {
        return new AclGrid($this->getContext(), $this->getSelectedContest());
    }
}
