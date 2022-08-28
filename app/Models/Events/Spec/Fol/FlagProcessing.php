<?php

declare(strict_types=1);

namespace FKSDB\Models\Events\Spec\Fol;

use FKSDB\Models\Events\Model\Holder\Holder;
use FKSDB\Models\Events\Spec\WithSchoolProcessing;
use Fykosak\Utils\Logging\Logger;
use FKSDB\Models\ORM\Models\PersonHasFlagModel;
use FKSDB\Models\ORM\Services\SchoolService;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

class FlagProcessing extends WithSchoolProcessing
{

    private SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    protected function innerProcess(
        ?string $state,
        ArrayHash $values,
        Holder $holder,
        Logger $logger,
        ?Form $form
    ): void {
        if (!isset($values['team'])) {
            return;
        }
        if ($holder->primaryHolder->name == 'team') {
            return;
        }
        $formValues = [
            'school_id' => $this->getSchoolValue($holder->primaryHolder->name),
            'study_year' => $this->getStudyYearValue($holder->primaryHolder->name),
        ];

        if (!$formValues['school_id']) {
            if ($this->isBaseReallyEmpty($holder->primaryHolder->name)) {
                return;
            }

            $history = $holder->primaryHolder->getModel2()->mainModel->getPersonHistory();
            $participantData = [
                'school_id' => $history->school_id,
                'study_year' => $history->study_year,
            ];
        } else {
            $participantData = $formValues;
        }
        if (
            !($this->schoolService->isCzSkSchool($participantData['school_id'])
                && $this->isStudent($participantData['study_year']))
        ) {
            /** @var PersonHasFlagModel $personHasFlag */
            $personHasFlag = $values[$holder->primaryHolder->name]['person_id_container']['person_has_flag'];
            $personHasFlag->offsetUnset('spam_mff');
//                $a=$c;
//                $values[$name]['person_id_1']['person_has_flag']['spam_mff'] = null;
//                $a=$c;
            //unset($values[$name]['person_id_1']['person_has_flag']);
        }
    }

    private function isStudent(?int $studyYear): bool
    {
        return !is_null($studyYear);
    }
}
