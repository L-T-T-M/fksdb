<?php

namespace FKSDB\ORM;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
abstract class AbstractModelSingle extends ActiveRow implements IModel {
    private $tmpData = [];

    /**
     * AbstractModelSingle constructor.
     * @param array $data
     * @param Selection $table
     */
    public function __construct(array $data, Selection $table) {
        parent::__construct($data, $table);
        $this->tmpData = $data;
    }

    /**
     * @var bool
     * @deprecated
     */
    protected $stored = true;

    /**
     * @return bool
     * @deprecated
     */
    public function isNew(): bool {
        return !$this->stored;
    }

    /**
     * @param bool $value
     * @deprecated
     */
    public function setNew(bool $value = true) {
        $this->stored = !$value;
    }

    /**
     * @param ActiveRow $row
     * @return static
     * @deprecated use createFromActiveRow
     */
    public static function createFromTableRow(ActiveRow $row): self {
        return static::createFromActiveRow($row);
    }

    /**
     * @param ActiveRow $row
     * @return static
     */
    public static function createFromActiveRow(ActiveRow $row): self {
        if ($row instanceof static) {
            return $row;
        }
        $model = new static($row->toArray(), $row->getTable());
        if ($model->getPrimary(false)) {
            $model->setNew(false);
        }
        return $model;
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value) {
        $this->tmpData[$key] = $value;
    }

    /**
     * @param $key
     * @return bool|mixed|ActiveRow|\Nette\Database\Table\Selection|null
     */
    public function &__get($key) {
        if (array_key_exists($key, $this->tmpData)) {
            return $this->tmpData[$key];
        }
        return parent::__get($key);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key): bool {
        if (array_key_exists($key, $this->tmpData)) {
            return true;
        }
        return parent::__isset($key);
    }

    /**
     * @return array
     */
    public function getTmpData() {
        return $this->tmpData;
    }

    /**
     * @param $key
     */
    public function __unset($key) {
        unset($this->tmpData[$key]);
        return parent::__unset($key);
    }

    /**
     * @return array|mixed
     */
    public function toArray() {
        $data = parent::toArray();
        return array_merge($data, $this->tmpData);
    }
}
