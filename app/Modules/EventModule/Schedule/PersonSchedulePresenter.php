<?php

declare(strict_types=1);

namespace FKSDB\Modules\EventModule\Schedule;

use FKSDB\Components\Grids\Schedule\PerPersonScheduleList;
use FKSDB\Models\Events\Exceptions\EventNotFoundException;
use FKSDB\Modules\EventModule\BasePresenter;
use Fykosak\Utils\UI\PageTitle;

class PersonSchedulePresenter extends BasePresenter
{
    /** @persistent */
    public ?int $personId = null;

    public function titleList(): PageTitle
    {
        return new PageTitle(null, _('Schedule per person'), 'fas fa-list');
    }

    /**
     * @throws EventNotFoundException
     */
    public function authorizedList(): bool
    {
        return $this->isAllowed('event.scheduleGroup', 'create');
    }

    public function titleDefault(): PageTitle
    {
        return new PageTitle(null, _('My schedule'), 'fas fa-list');
    }

    /**
     * @throws EventNotFoundException
     */
    public function authorizedDefault(): bool
    {
        $person = $this->getLoggedPerson();
        return $person && count($person->getEventRoles($this->getEvent()));
    }


    /**
     * @throws EventNotFoundException
     */
    public function renderDefault(): void
    {
        $this->template->event = $this->getEvent();
        $this->template->person = $this->getLoggedPerson();
    }

    /**
     * @throws EventNotFoundException
     */
    protected function createComponentList(): PerPersonScheduleList
    {
        return new PerPersonScheduleList($this->getContext(), $this->getEvent());
    }
}
