<?php

declare(strict_types=1);

namespace FKSDB\Models\Transitions\Callbacks\Tabor;

use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Transitions\Callbacks\EventParticipantCallback;
use FKSDB\Models\Transitions\Holder\ModelHolder;

class AppliedMailCallback extends EventParticipantCallback
{
    /**
     * @param BaseHolder $holder
     */
    protected function getTemplatePath(ModelHolder $holder): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'applied.latte';
    }

    /**
     * @param BaseHolder $holder
     */
    protected function getData(ModelHolder $holder): array
    {
        return [
            'subject' => 'Letní tábor Výfuku',
            'blind_carbon_copy' => 'Letní tábor Výfuku <vyfuk@vyfuk.org>',
            'sender' => 'Výfuk <vyfuk@vyfuk.org>',
        ];
    }
}
