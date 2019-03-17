<?php

namespace FKSDB\ORM\Services\StoredQuery;
use AbstractServiceSingle;
use FKSDB\ORM\DbNames;
use Nette;

/**
 * @author Lukáš Timko <lukast@fykos.cz>
 */
class ServiceStoredQueryTag extends AbstractServiceSingle {

    protected $tableName = DbNames::TAB_STORED_QUERY_TAG;
    protected $modelClassName = 'FKSDB\ORM\Models\StoredQuery\ModelStoredQueryTag';

    /**
     * @param int|null $tagTypeId
     * @return Nette\Database\Table\Selection|null
     */
    public function findByTagTypeId($tagTypeId) {
        if (!$tagTypeId) {
            return null;
        }
        $result = $this->getTable()->where('tag_type_id', $tagTypeId);
        return $result ?: null;
    }
}
