<?php

namespace FKSDB\Models\ORM\Services;

use FKSDB\Models\ORM\Models\OldAbstractModelSingle;
use Fykosak\NetteORM\AbstractModel;
use Fykosak\NetteORM\Exceptions\ModelException;
use FKSDB\Models\ORM\Models\ModelPerson;
use Nette\Database\Table\ActiveRow;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 * @method ModelPerson|null findByPrimary($key)
 * @method ModelPerson createNewModel(array $data)
 */
class ServicePerson extends OldAbstractServiceSingle {

    public function findByEmail(?string $email): ?ModelPerson {
        if (!$email) {
            return null;
        }
        /** @var ModelPerson|null $result */
        $result = $this->getTable()->where(':person_info.email', $email)->fetch();
        return $result;
    }

    /**
     * @param ModelPerson|AbstractModel|null $model
     * @param array $data
     * @return OldAbstractModelSingle
     */
    public function store(?AbstractModel $model, array $data): OldAbstractModelSingle {
        if (is_null($model) && is_null($data['gender'])) {
            $data['gender'] = ModelPerson::inferGender($data);
        }
        return parent::store($model, $data);
    }
}
