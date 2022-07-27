<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models;

use Fykosak\NetteORM\Model;
use Nette\Database\Table\ActiveRow;

/**
 * @property-read string token
 * @property-read ModelLogin login
 * @property-read int login_id
 * @property-read string data
 * @property-read string type
 * @property-read \DateTimeInterface until
 */
class ModelAuthToken extends Model
{

    /** @const The first login for setting up a password. */
    public const TYPE_INITIAL_LOGIN = 'initial_login';
    /** @const Password recovery login */
    public const TYPE_RECOVERY = 'recovery';
    /** @const Notification about an event application. */
    public const TYPE_EVENT_NOTIFY = 'event_notify';
}
