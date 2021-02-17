<?php

namespace FKSDB\Models\ORM\Services\StoredQuery;

use FKSDB\Models\ORM\Services\AbstractServiceSingle;
use FKSDB\Models\ORM\Tables\TypedTableSelection;

/**
 * @author Lukáš Timko <lukast@fykos.cz>
 */
class ServiceStoredQueryTag extends AbstractServiceSingle {

    public function findByTagTypeId(?int $tagTypeId): ?TypedTableSelection {
        if (!$tagTypeId) {
            return null;
        }
        return $this->getTable()->where('tag_type_id', $tagTypeId);
    }
}
