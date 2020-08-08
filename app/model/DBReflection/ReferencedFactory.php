<?php

namespace FKSDB\DBReflection;

use FKSDB\Entity\CannotAccessModelException;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\ORM\AbstractModelSingle;

/**
 * Class ReferencedFactory
 * @author Michal Červeňák <miso@fykos.cz>
 */
final class ReferencedFactory {

    /** @var string */
    private $modelClassName;
    /**
     * @var array
     * modelClassName => string FQN of class/interface that can be access via 'method'-field
     * method => method name, that return Model of $this->modelClassName
     */
    private $referencedAccess;

    /**
     * ReferencedFactory constructor.
     * @param string $modelClassName
     * @param array|null $referencedAccess
     */
    public function __construct(string $modelClassName, array $referencedAccess = null) {
        $this->referencedAccess = $referencedAccess;
        $this->modelClassName = $modelClassName;
    }

    /**
     * @param AbstractModelSingle $model
     * @return AbstractModelSingle|null
     * @throws CannotAccessModelException
     * @throws BadTypeException
     */
    public function accessModel(AbstractModelSingle $model) {
        // model is already instance of desired model
        if ($model instanceof $this->modelClassName) {
            return $model;
        }

        // if referenced access is not set and model is not desired model throw exception
        if (!isset($this->referencedAccess) || is_null($this->referencedAccess)) {
            throw new BadTypeException($this->modelClassName, get_class($model));
        }
        return $this->accessReferencedModel($model);
    }

    public function getModelClassName(): string {
        return $this->modelClassName;
    }

    /**
     * try interface and access via get<Model>()
     * @param AbstractModelSingle $model
     * @return AbstractModelSingle|null
     * @throws CannotAccessModelException
     * @throws BadTypeException
     */
    private function accessReferencedModel(AbstractModelSingle $model) {
        if ($model instanceof $this->referencedAccess['modelClassName']) {
            $referencedModel = $model->{$this->referencedAccess['method']}();
            if ($referencedModel) {
                if ($referencedModel instanceof $this->modelClassName) {
                    return $referencedModel;
                }
                throw new BadTypeException($this->modelClassName, $referencedModel);
            }
            return null;
        }
        throw new CannotAccessModelException($this->modelClassName, $model);
    }
}
