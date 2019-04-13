<?php

namespace FKSDB\Components\DatabaseReflection\EventParticipant;
/**
 * Class DietRow
 * @package FKSDB\Components\DatabaseReflection\EventParticipant
 */
class DietRow extends AbstractParticipantRow {
    /**
     * @return string
     */
    public static function getTitle(): string {
        return _('Diet');
    }
}
