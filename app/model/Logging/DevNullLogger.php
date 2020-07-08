<?php

namespace FKSDB\Logging;

use FKSDB\Messages\Message;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class DevNullLogger extends StackedLogger {

    /**
     * @param Message $message
     * @return void
     */
    protected function doLog(Message $message) {
        /* empty */
    }

}
