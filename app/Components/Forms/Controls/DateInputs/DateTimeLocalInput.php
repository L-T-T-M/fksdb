<?php

namespace FKSDB\Components\Forms\Controls\DateInputs;

/**
 * Class DateTimeLocalInput
 * *
 */
class DateTimeLocalInput extends AbstractDateInput {

    /**
     * @return string
     */
    protected function getFormat(): string {
        return 'Y-m-d\TH:i:s';
    }

    /**
     * @return string
     */
    protected function getType(): string {
        return 'datetime-local';
    }
}
