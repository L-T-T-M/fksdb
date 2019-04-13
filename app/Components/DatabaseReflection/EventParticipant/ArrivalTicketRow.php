<?php

namespace FKSDB\Components\DatabaseReflection\EventParticipant;

use FKSDB\Components\DatabaseReflection\ValuePrinters\BinaryPrinter;
use FKSDB\ORM\AbstractModelSingle;
use FKSDB\ORM\Models\ModelEventParticipant;
use Nette\Utils\Html;

/**
 * Class ArrivalTicketRow
 * @package FKSDB\Components\DatabaseReflection\EventParticipant
 */
class ArrivalTicketRow extends AbstractParticipantRow {
    /**
     * @return string
     */
    public static function getTitle(): string {
        return _('Arrival ticket');
    }

    /**
     * @param AbstractModelSingle|ModelEventParticipant $model
     * @param string $fieldName
     * @return Html
     */
    public function createHtmlValue(AbstractModelSingle $model, string $fieldName): Html {
        return (new BinaryPrinter)($model->arrival_ticket);
    }
}
