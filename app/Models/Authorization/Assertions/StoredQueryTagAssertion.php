<?php

declare(strict_types=1);

namespace FKSDB\Models\Authorization\Assertions;

use FKSDB\Models\StoredQuery\StoredQuery;
use Nette\InvalidArgumentException;
use Nette\Security\Permission;
use Nette\SmartObject;

// TODO isnt used anymore
class StoredQueryTagAssertion implements Assertion
{
    use SmartObject;

    private array $tagNames;

    public function __construct(array $tagNames)
    {
        $this->tagNames = $tagNames;
    }

    public function __invoke(Permission $acl, ?string $role, ?string $resourceId, ?string $privilege): bool
    {
        $storedQuery = $acl->getQueriedResource();
        if (!$storedQuery instanceof StoredQuery) {
            throw new InvalidArgumentException('Expected StoredQuery, got \'' . get_class($storedQuery) . '\'.');
        }
        foreach ($storedQuery->getQueryPattern()->getStoredQueryTagTypes() as $tagType) {
            if (in_array($tagType->name, $this->tagNames)) {
                return true;
            }
        }
        return false;
    }
}
