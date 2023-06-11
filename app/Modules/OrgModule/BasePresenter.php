<?php

declare(strict_types=1);

namespace FKSDB\Modules\OrgModule;

use FKSDB\Modules\Core\AuthenticatedPresenter;
use FKSDB\Modules\Core\PresenterTraits\PresenterRole;
use FKSDB\Modules\Core\PresenterTraits\SeriesPresenterTrait;
use Nette\Security\Resource;

abstract class BasePresenter extends AuthenticatedPresenter
{
    use SeriesPresenterTrait;

    protected function startup(): void
    {
        parent::startup();
        $this->seriesTraitStartup();
    }

    protected function getNavRoots(): array
    {
        return ['Org.Dashboard.default'];
    }

    protected function getStyleId(): string
    {
        $contest = $this->getSelectedContest();
        if (isset($contest)) {
            return 'contest-' . $contest->getContestSymbol();
        }
        return parent::getStyleId();
    }

    protected function getDefaultSubTitle(): ?string
    {
        return sprintf(_('%d. year, %s. series'), $this->getSelectedContestYear()->year, $this->getSelectedSeries());
    }

    /**
     * @param Resource|string|null $resource
     */
    protected function isAnyContestAuthorized($resource, ?string $privilege): bool
    {
        return $this->contestAuthorizator->isAllowed($resource, $privilege);
    }

    protected function getRole(): PresenterRole
    {
        return PresenterRole::tryFrom(PresenterRole::ORG);
    }
}
