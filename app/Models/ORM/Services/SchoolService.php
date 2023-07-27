<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Services;

use FKSDB\Models\ORM\Models\SchoolModel;
use Fykosak\NetteORM\Service;

/**
 * @method SchoolModel storeModel(array $data, ?SchoolModel $model = null)
 * @method SchoolModel|null findByPrimary($key)
 */
final class SchoolService extends Service
{

    public function isCzSkSchool(?int $schoolId): bool
    {
        if (is_null($schoolId)) {
            return false;
        }
        try {
            return in_array($this->findByPrimary($schoolId)->address->country->alpha_2, ['CZ', 'SK']);
        } catch (\Throwable$exception) {
            return false;
        }
    }
}
