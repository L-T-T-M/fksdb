<?php

declare(strict_types=1);

namespace FKSDB\Models\Transitions\Callbacks\Fof;

use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\AuthTokenModel;
use FKSDB\Models\ORM\Models\Fyziklani\TeamTeacherModel;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\Transitions\Callbacks\MailCallback;
use FKSDB\Models\Transitions\Holder\TeamHolder;
use FKSDB\Models\Transitions\Holder\ModelHolder;

class TeamTeacherMailCallback extends MailCallback
{
    protected function getPersonsFromHolder(ModelHolder $holder): array
    {
        if (!$holder instanceof TeamHolder) {
            throw new BadTypeException(TeamHolder::class, $holder);
        }
        $persons = [];
        /** @var TeamTeacherModel $teacher */
        foreach ($holder->getModel()->getTeachers() as $teacher) {
            $persons[] = $teacher->person;
        }
        return $persons;
    }

    /**
     * @throws BadTypeException
     */
    protected function createToken(PersonModel $person, ModelHolder $holder): AuthTokenModel
    {
        if (!$holder instanceof TeamHolder) {
            throw new BadTypeException(TeamHolder::class, $holder);
        }
        return $this->authTokenService->createToken(
            $this->resolveLogin($person),
            AuthTokenModel::TYPE_EVENT_NOTIFY,
            $holder->getModel()->event->registration_end,
            null,
            true
        );
    }

    protected function getTemplatePath(ModelHolder $holder): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'teacher.latte';
    }

    /**
     * @param TeamHolder $holder
     * @return string[]
     */
    protected function getData(ModelHolder $holder): array
    {
        if ($holder->getModel()->game_lang->value === 'cs') {
            $subject = 'Registrace na Fyziklání – ' . $holder->getModel()->name;
        } else {
            $subject = 'Fyziklani Registration – ' . $holder->getModel()->name;
        }
        if ($holder->getModel()->game_lang->value === 'cs') {
            $sender = 'Fyziklání <fyziklani@fykos.cz>';
        } else {
            $sender = 'Fyziklani <fyziklani@fykos.cz>';
        }
        return [
            'subject' => $subject,
            'blind_carbon_copy' => 'FYKOS <fyziklani@fykos.cz>',
            'sender' => $sender,
        ];
    }
}
