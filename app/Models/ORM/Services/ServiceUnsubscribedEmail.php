<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Services;

use FKSDB\Models\ORM\Services\Exceptions\UnsubscribedEmailException;
use Fykosak\NetteORM\AbstractService;

class ServiceUnsubscribedEmail extends AbstractService
{
    /**
     * @throws UnsubscribedEmailException
     */
    public function checkEmail(string $email): void
    {
        $row = $this->getTable()->where('email_hash = SHA1(?)', $email)->fetch();
        if ($row) {
            throw new UnsubscribedEmailException();
        }
    }
}
