<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Services;

use FKSDB\Models\ORM\DbNames;
use FKSDB\Models\ORM\Models\ContestantModel;
use FKSDB\Models\ORM\Models\QuizModel;
use FKSDB\Models\ORM\Models\SubmitQuizModel;
use Nette\Utils\DateTime;
use Fykosak\NetteORM\Service;

class ServiceSubmitQuiz extends Service
{

    public function findByContestant(QuizModel $question, ContestantModel $contestant): ?SubmitQuizModel
    {
        /** @var SubmitQuizModel $result */
        $result = $contestant->related(DbNames::TAB_SUBMIT_QUIZ)
            ->where('question_id', $question->question_id)
            ->fetch();
        return $result ? SubmitQuizModel::createFromActiveRow($result) : null;
    }

    public function saveSubmittedQuestion(QuizModel $question, ContestantModel $contestant, ?string $answer): void
    {
        $submit = $this->findByContestant($question, $contestant);
        if ($submit) {
            $this->updateModel($submit, [
                'submitted_on' => new DateTime(),
                'answer' => $answer,
            ]);
        } else {
            $this->createNewModel([
                'question_id' => $question->question_id,
                'ct_id' => $contestant->ct_id,
                'submitted_on' => new DateTime(),
                'answer' => $answer,
            ]);
        }
    }
}
