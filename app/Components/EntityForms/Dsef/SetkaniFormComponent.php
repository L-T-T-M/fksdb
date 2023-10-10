<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms\Dsef;

use FKSDB\Components\Forms\Containers\Models\ReferencedPersonContainer;
use FKSDB\Modules\Core\BasePresenter;
use Nette\Neon\Exception;

/**
 * @method BasePresenter getPresenter($need = true)
 * @phpstan-import-type EvaluatedFieldsDefinition from ReferencedPersonContainer
 */
final class SetkaniFormComponent extends SingleFormComponent
{
    /**
     * @throws Exception
     * @phpstan-return EvaluatedFieldsDefinition
     */
    final protected function getPersonFieldsDefinition(): array
    {
        return [
            'person'=> [
                'other_name' => ['required' => true],
                'family_name' => ['required' => true]
            ],
            'person_history' => [
                'school_id' => ['required' => true]
            ],
            'person_info' => [
                'email' => ['required' => true],
                'born' => ['required' => true],
                'id_number' => [
                    'required' => false,
                    'description' => _('Číslo OP/pasu, pokud máš')
                ],
                'phone_parent_m' => ['required' => false],
                'phone_parent_d' => ['required' => false],
                'phone' => ['required' => true]
            ],
            'person_schedule' => [
                'apparel' => ['required' => true],
                'transport' => ['required'=> true],
                'ticket' => ['required' => true]
            ]
        ];
    }

    /**
     * @phpstan-return array<string, array<string, mixed>>
     */
    final protected function getParticipantFieldsDefinition(): array
    {
        return [
            'diet' => [
                'required' => false,
                'description' => "Máš nějaké speciální stravovací návyky – vegetariánství, veganství, diety, …?
                Pokud ano, máš zájem o speciální stravu nebo si (zejména v případě veganů) dovezeš jídlo vlastní?"
            ],
            'health_restrictions' => [
                'required' => false,
                'description' => "Máš nějaká zdravotní omezení, která by tě mohla omezovat v pobytu na setkání?
                Například různé alergie (a jejich projevy), cukrovka, epilepsie, dlouhodobější obtíže, … Bereš
                nějaké léky, ať už pravidelně, nebo v případě obtíží? Jaké to jsou? Jsou nějaké další informace
                ohledně tvého zdravotního stavu, co bychom měli vědět?"],
            'note' => ['required' => false]
        ];
    }
}
