<?php

declare(strict_types=1);

namespace FKSDB\Models\Email\Source\Tabor;

use FKSDB\Models\Transitions\Holder\ParticipantHolder;
use FKSDB\Models\Transitions\Transition\Transition;

class NoConfirmEmail extends TaborTransitionEmail
{
    protected function getTemplatePath(ParticipantHolder $holder, Transition $transition): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'no_confirm.latte';
    }

    /**
     * @phpstan-return array{
     *     sender:string,
     * }
     */
    protected function getData(ParticipantHolder $holder, Transition $transition): array
    {
        return [
            'sender' => 'Výfuk <vyfuk@vyfuk.org>',
        ];
    }
}
