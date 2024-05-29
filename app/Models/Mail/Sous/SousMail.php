<?php

declare(strict_types=1);

namespace FKSDB\Models\Mail\Sous;

use FKSDB\Models\Transitions\Holder\ParticipantHolder;
use FKSDB\Models\Transitions\Callbacks\EventParticipantCallback;
use FKSDB\Models\Transitions\Holder\ModelHolder;

abstract class SousMail extends EventParticipantCallback
{
    /**
     * @param ParticipantHolder $holder
     * @phpstan-return array{
     *     blind_carbon_copy?:string,
     *     sender:string,
     * }
     */
    final protected function getData(ModelHolder $holder): array
    {
        return [
            'blind_carbon_copy' => 'Soustředění FYKOSu <soustredeni@fykos.cz>',
            'sender' => 'Soustředění FYKOSu <soustredeni@fykos.cz>',
        ];
    }
}
