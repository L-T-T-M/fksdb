<?php

declare(strict_types=1);

namespace FKSDB\Models\DataTesting\Tests\ModelPerson;

use FKSDB\Models\DataTesting\TestLog;
use FKSDB\Models\ORM\Models\PersonHistoryModel;
use FKSDB\Models\ORM\Models\PersonModel;
use Fykosak\Utils\Logging\Logger;
use Fykosak\Utils\Logging\Message;

class StudyYearTest extends PersonTest
{
    public function __construct()
    {
        parent::__construct('study_year', _('Study years'));
    }

    public function run(Logger $logger, PersonModel $person): void
    {
        $histories = $person->getHistories()->order('ac_year');
        /** @var PersonHistoryModel|null $firstValid */
        $firstValid = null;
        $hasError = false;
        /** @var PersonHistoryModel|null $postgraduate */
        $postgraduate = null;
        /** @var PersonHistoryModel $history */
        foreach ($histories as $history) {
            if ($history->getGraduationYear() === null) {
                $postgraduate = $history;
                continue;
            }
            if ($firstValid === null) {
                $firstValid = $history;
                continue;
            }
            if ($postgraduate) {
                $hasError = true;
                $logger->log(
                    new TestLog(
                        $this->title,
                        sprintf(
                            'Before %d found postgraduate study year in %d',
                            $history->ac_year,
                            $postgraduate->ac_year
                        ),
                        Message::LVL_ERROR
                    )
                );
            }
            if ($firstValid->getGraduationYear() !== $history->getGraduationYear()) {
                $hasError = true;
                if (
                    $firstValid->study_year_new->value === StudyYear::Primary5 &&
                    $history->study_year_new->value === StudyYear::Primary5
                ) {
                    $level = Message::LVL_WARNING;
                } else {
                    $level = Message::LVL_ERROR;
                }
                $logger->log(
                    new TestLog(
                        $this->title,
                        sprintf(
                            'In %d expected graduated "%s" given "%s"',
                            $history->ac_year,
                            $firstValid->getGraduationYear(),
                            $history->getGraduationYear()
                        ),
                        $level
                    )
                );
                $firstValid = $history;
            }
        }
        if (!$hasError) {
            $logger->log(
                new TestLog(
                    $this->title,
                    'Study years OK',
                    Message::LVL_SUCCESS
                )
            );
        }
    }
}
