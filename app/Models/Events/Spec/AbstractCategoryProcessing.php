<?php

declare(strict_types=1);

namespace FKSDB\Models\Events\Spec;

use FKSDB\Components\Forms\Factories\Events\OptionsProvider;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Events\Model\Holder\Field;
use FKSDB\Models\Events\Model\Holder\Holder;
use FKSDB\Models\ORM\Models\Fyziklani\TeamCategory;
use FKSDB\Models\ORM\Models\Fyziklani\TeamModel2;
use FKSDB\Models\ORM\Models\ModelPersonHistory;
use FKSDB\Models\ORM\Services\ServicePerson;
use FKSDB\Models\ORM\Services\ServiceSchool;

abstract class AbstractCategoryProcessing extends WithSchoolProcessing implements OptionsProvider
{

    protected ServiceSchool $serviceSchool;
    protected ServicePerson $servicePerson;

    public function __construct(ServiceSchool $serviceSchool, ServicePerson $servicePerson)
    {
        $this->serviceSchool = $serviceSchool;
        $this->servicePerson = $servicePerson;
    }

    protected function extractValues(Holder $holder): array
    {
        $participants = [];
        foreach ($holder->getBaseHolders() as $name => $baseHolder) {
            if ($name == 'team') {
                continue;
            }

            $schoolValue = $this->getSchoolValue($name);
            $studyYearValue = $this->getStudyYearValue($name);

            if (!$schoolValue && !$studyYearValue) {
                if ($this->isBaseReallyEmpty($name)) {
                    continue;
                }
                $history = $this->getPersonHistory($baseHolder);
                $schoolValue = $history->school_id;
                $studyYearValue = $history->study_year;
            }

            $participants[] = [
                'school_id' => $schoolValue,
                'study_year' => $studyYearValue,
            ];
        }
        return $participants;
    }

    protected function isBaseReallyEmpty(string $name): bool
    {
        $personIdControls = $this->getControl("$name.person_id");
        $personIdControl = reset($personIdControls);
        if ($personIdControl && $personIdControl->getValue(false)) {
            return false;
        }
        return parent::isBaseReallyEmpty($name); // TODO: Change the autogenerated stub
    }

    private function getPersonHistory(BaseHolder $baseHolder): ?ModelPersonHistory
    {
        $personControls = $this->getControl("$baseHolder->name.person_id");
        $value = reset($personControls)->getValue(false);
        $person = $this->servicePerson->findByPrimary($value);
        return $person->getHistoryByContestYear($baseHolder->holder->primaryHolder->event->getContestYear());
    }

    public function getOptions(Field $field): array
    {
        $results = [];
        foreach (TeamCategory::cases() as $category) {
            $results[$category->value] = $category->getName();
        }
        return $results;
    }

    abstract protected function getCategory(array $participants): string;
}
