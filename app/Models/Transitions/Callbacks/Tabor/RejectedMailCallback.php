<?php

declare(strict_types=1);

namespace FKSDB\Models\Transitions\Callbacks\Tabor;

use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Transitions\Callbacks\EventParticipantCallback;
use FKSDB\Models\Transitions\Holder\ModelHolder;
use FKSDB\Models\Transitions\Transition\Transition;

/**
 * @phpstan-extends EventParticipantCallback<BaseHolder>
 */
class RejectedMailCallback extends EventParticipantCallback
{
    /**
     * @param BaseHolder $holder
     * @phpstan-param Transition<BaseHolder> $transition
     */
    protected function getTemplatePath(ModelHolder $holder, Transition $transition): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'rejected.latte';
    }

    /**
     * @phpstan-return array{
     *     blind_carbon_copy?:string,
     *     subject:string,
     *     sender:string,
     * }
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
