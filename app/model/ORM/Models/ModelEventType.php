<?php

namespace FKSDB\ORM\Models;

use FKSDB\ORM\AbstractModelSingle;
use Nette\Database\Table\ActiveRow;

/**
 *
 * @author Michal Koutný <xm.koutny@gmail.com>
 * @property-read ActiveRow contest
 * @property-read int contest_id
 * @property-read int event_type_id
 */
class ModelEventType extends AbstractModelSingle {

    const FYZIKLANI = 1;

}
