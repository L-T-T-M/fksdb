<?php

namespace FKSDB\StoredQuery;

use Nette\SmartObject;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
abstract class StoredQueryPostProcessing {
    use SmartObject;

    /**
     * @var array
     */
    protected $parameters;

    final public function resetParameters() {
        $this->parameters = [];
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    final public function bindValue($key, $value) {
        $this->parameters[$key] = $value; // type is ignored so far
    }

    public function keepsCount(): bool {
        return true;
    }

    /**
     * @param \PDOStatement $data
     * @return iterable
     */
    abstract public function processData(\PDOStatement $data);

    abstract public function getDescription(): string;
}
