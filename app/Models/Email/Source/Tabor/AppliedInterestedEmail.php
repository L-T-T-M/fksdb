<?php

declare(strict_types=1);

namespace FKSDB\Models\Email\Source\Tabor;

use FKSDB\Models\Transitions\Holder\ParticipantHolder;
use FKSDB\Models\Transitions\Transition\Transition;

class AppliedInterestedEmail extends TaborTransitionEmail
{
    protected function getTemplatePath(ParticipantHolder $holder, Transition $transition): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'applied_interested.latte';
    }
}
