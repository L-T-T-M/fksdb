<?php

namespace FKSDB\Model\Logging;

use FKSDB\Model\Messages\Message;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class DevNullLogger extends StackedLogger {

    protected function doLog(Message $message): void {
        /* empty */
    }

}
