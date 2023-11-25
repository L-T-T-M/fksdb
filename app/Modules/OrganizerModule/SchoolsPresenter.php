<?php

declare(strict_types=1);

namespace FKSDB\Modules\OrganizerModule;

use FKSDB\Components\DataTest\DataTestFactory;
use FKSDB\Components\EntityForms\SchoolFormComponent;
use FKSDB\Components\Grids\ContestantsFromSchoolGrid;
use FKSDB\Components\Grids\SchoolsGrid;
use FKSDB\Models\Entity\ModelNotFoundException;
use FKSDB\Models\Exceptions\GoneException;
use FKSDB\Models\ORM\Models\SchoolModel;
use FKSDB\Models\ORM\Services\SchoolService;
use FKSDB\Modules\Core\PresenterTraits\EntityPresenterTrait;
use FKSDB\Modules\Core\PresenterTraits\NoContestAvailable;
use Fykosak\Utils\UI\PageTitle;
use Nette\Security\Resource;

final class SchoolsPresenter extends BasePresenter
{
    /** @phpstan-use EntityPresenterTrait<SchoolModel> */
    use EntityPresenterTrait;

    private SchoolService $schoolService;
    private DataTestFactory $dataTestFactory;

    final public function injectServiceSchool(SchoolService $schoolService, DataTestFactory $dataTestFactory): void
    {
        $this->schoolService = $schoolService;
        $this->dataTestFactory = $dataTestFactory;
    }

    public function titleCreate(): PageTitle
    {
        return new PageTitle(null, _('Create school'), 'fas fa-plus');
    }

    public function titleDefault(): PageTitle
    {
        return new PageTitle(null, _('Schools'), 'fas fa-school');
    }

    /**
     * @throws ModelNotFoundException
     * @throws GoneException
     */
    final public function renderDetail(): void
    {
        $this->template->model = $this->getEntity();
    }

    /**
     * @throws ModelNotFoundException
     * @throws GoneException
     */
    public function titleDetail(): PageTitle
    {
        return new PageTitle(
            null,
            sprintf(_('Detail of school %s'), $this->getEntity()->name_abbrev),
            'fas fa-university'
        );
    }

    /**
     * @throws ModelNotFoundException
     * @throws GoneException
     */
    public function titleEdit(): PageTitle
    {
        return new PageTitle(null, sprintf(_('Edit school %s'), $this->getEntity()->name_abbrev), 'fas fa-pen');
    }

    /**
     * @throws NoContestAvailable
     */
    public function authorizedReport(): bool
    {
        return $this->contestAuthorizator->isAllowed(SchoolModel::RESOURCE_ID, 'report', $this->getSelectedContest());
    }

    public function renderReport(): void
    {
        $tests = [];
        foreach ($this->dataTestFactory->getSchoolTests() as $test) {
            $tests[$test->getId()] = $test;
        }
        $query = $this->schoolService->getTable();
        $logs = [];
        /** @var SchoolModel $model */
        foreach ($query as $model) {
            $log = DataTestFactory::runForModel($model, $tests);
            if (\count($log)) {
                $logs[] = ['model' => $model, 'logs' => $log];
            }
        }
        $this->template->tests = $tests;
        $this->template->logs = $logs;
    }

    public function titleReport(): PageTitle
    {
        return new PageTitle(null, _('Report'), 'fas fa-school');
    }

    /**
     * @param Resource|string|null $resource
     */
    protected function traitIsAuthorized($resource, ?string $privilege): bool
    {
        return $this->isAnyContestAuthorized($resource, $privilege);
    }

    protected function getORMService(): SchoolService
    {
        return $this->schoolService;
    }

    protected function createComponentGrid(): SchoolsGrid
    {
        return new SchoolsGrid($this->getContext());
    }

    /**
     * @throws ModelNotFoundException
     * @throws GoneException
     */
    protected function createComponentEditForm(): SchoolFormComponent
    {
        return new SchoolFormComponent($this->getContext(), $this->getEntity());
    }

    protected function createComponentCreateForm(): SchoolFormComponent
    {
        return new SchoolFormComponent($this->getContext(), null);
    }

    /**
     * @throws ModelNotFoundException
     * @throws GoneException
     */
    protected function createComponentContestantsFromSchoolGrid(): ContestantsFromSchoolGrid
    {
        return new ContestantsFromSchoolGrid($this->getEntity(), $this->getContext());
    }
}
