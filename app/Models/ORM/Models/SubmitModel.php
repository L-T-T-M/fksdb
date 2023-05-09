<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models;

use Nette\Security\Resource;
use Fykosak\NetteORM\Model;

/**
 * @property-read int submit_id
 * @property-read int contestant_id
 * @property-read ContestantModel contestant
 * @property-read int task_id
 * @property-read TaskModel task
 * @property-read \DateTimeInterface submitted_on
 * @property-read SubmitSource source
 * @property-read string note
 * @property-read float|null raw_points
 * @property-read float|null calc_points
 * @property-read int corrected FUCK MARIADB
 */
class SubmitModel extends Model implements Resource
{

    public const RESOURCE_ID = 'submit';

    public function getResourceId(): string
    {
        return self::RESOURCE_ID;
    }

    public function getFingerprint(): string
    {
        return md5(
            implode(':', [
                $this->submit_id,
                $this->submitted_on,
                $this->source,
                $this->note,
                $this->raw_points,
            ])
        );
    }

    public function canRevoke(): bool
    {
        if ($this->source->value != SubmitSource::UPLOAD) {
            return false;
        }
        $now = time();
        $start = $this->task->submit_start ? $this->task->submit_start->getTimestamp() : 0;
        $deadline = $this->task->submit_deadline ? $this->task->submit_deadline->getTimestamp() : ($now + 1);
        return ($now <= $deadline) && ($now >= $start);
    }

    public function calculateQuestionSum(): ?int
    {
        // task does not have questions
        if (!$this->isQuiz()) {
            return null;
        }

        $sum = 0;
        // TODO rewrite to do sum directly in sql
        /** @var SubmitQuestionModel $question */
        foreach ($this->task->getQuestions() as $question) {
            /** @var SubmitQuestionAnswerModel $answer */
            $answer = $this->contestant->getAnswer($question);
            if (!isset($answer)) {
                continue;
            }
            if ($answer->answer === $question->answer) {
                $sum += $question->points;
            }
        }

        return $sum;
    }

    public function isQuiz(): bool
    {
        return $this->source->value === SubmitSource::QUIZ;
    }

    /**
     * @return SubmitSource|mixed|null
     * @throws \ReflectionException
     */
    public function &__get(string $key) // phpcs:ignore
    {
        $value = parent::__get($key);
        switch ($key) {
            case 'source':
                $value = SubmitSource::tryFrom($value);
                break;
        }
        return $value;
    }

    public function __toArray(): array
    {
        return [
            'submitId' => $this->submit_id,
            'taskId' => $this->task_id,
            'source' => $this->source->value,
            'rawPoints' => $this->raw_points,
            'calcPoints' => $this->calc_points,
        ];
    }
}
