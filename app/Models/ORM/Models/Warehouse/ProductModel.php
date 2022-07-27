<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models\Warehouse;

use Fykosak\NetteORM\Model;
use Nette\Security\Resource;

/**
 * @property-read int product_id
 * @property-read int producer_id
 * @property-read ProducerModel|null producer
 * @property-read ProductCategory category
 * @property-read string name_cs
 * @property-read string name_en
 * @property-read string description_cs
 * @property-read string description_en
 * @property-read string note neverejná poznámka
 * @property-read string url URL k objednaniu produktu
 */
class ProductModel extends Model implements Resource
{
    public const RESOURCE_ID = 'warehouse.product';

    public function getResourceId(): string
    {
        return self::RESOURCE_ID;
    }

    /**
     * @return mixed
     */
    public function &__get(string $key)
    {
        $value = parent::__get($key);
        switch ($key) {
            case 'category':
                $value = new ProductCategory($value);
                break;
        }
        return $value;
    }
}
