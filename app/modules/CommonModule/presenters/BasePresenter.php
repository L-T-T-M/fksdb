<?php

namespace FKSDB\Modules\CommonModule;

use FKSDB\Modules\Core\AuthenticatedPresenter;
use FKSDB\UI\PageStyleContainer;
use Nette\Security\IResource;

/**
 * Class BasePresenter
 * @author Michal Červeňák <miso@fykos.cz>
 */
abstract class BasePresenter extends AuthenticatedPresenter {

    protected function getPageStyleContainer(): PageStyleContainer {
        $container = parent::getPageStyleContainer();
        $container->styleId = 'theme-light common';
        $container->navBarClassName = 'bg-dark navbar-dark';
        return $container;
    }

    protected function beforeRender() {
        parent::beforeRender();
    }

    /**
     * @return string[]
     */
    protected function getNavRoots(): array {
        $roots = parent::getNavRoots();
        $roots[] = 'Common.Dashboard.default';
        return $roots;

    }

    /**
     * @param IResource|string $resource
     * @param string $privilege
     * @return bool
     */
    protected function isAnyContestAuthorized($resource, string $privilege): bool {
        return $this->getContestAuthorizator()->isAllowedForAnyContest($resource, $privilege);
    }
}
