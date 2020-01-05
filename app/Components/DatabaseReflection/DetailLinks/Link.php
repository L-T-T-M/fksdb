<?php

namespace FKSDB\Components\DatabaseReflection\Links;

use Nette\Application\BadRequestException;

/**
 * Class Link
 * @package FKSDB\Components\DatabaseReflection\Links
 */
class Link extends AbstractLink {
    /**
     * @var string
     */
    private $destination;
    /**
     * @var array
     */
    private $params;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $modelClassName;

    /**
     * @param string $destination
     * @param array $params
     * @param string $title
     * @param string $modelClassName
     */
    public function setParams(string $destination, array $params, string $title, string $modelClassName) {
        $this->destination = $destination;
        $this->params = $params;
        $this->title = $title;
        $this->modelClassName = $modelClassName;
    }

    /**
     * @inheritDoc
     */
    public function getText(): string {
        return _($this->title);
    }

    /**
     * @return string
     */
    public function getDestination(): string {
        return $this->destination;
    }

    public function prepareParams($model): array {
        $urlParams = [];
        foreach ($this->params as $key => $accessKey) {
            $urlParams[$key] = $model->{$accessKey};
        }
        return $urlParams;
    }

    /**
     * @inheritDoc
     */
    public function createLink($model): string {
        if (!$model instanceof $this->modelClassName) {
            throw new BadRequestException();
        }
        $urlParams = $this->prepareParams($model);
        return $this->presenterComponent->getPresenter()->link($this->destination, $urlParams);
    }
}
