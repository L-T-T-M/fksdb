<?php

namespace FKSDB\Components\React\Fyziklani;

use Nette\Utils\Json;

/**
 * Class Routing
 */
class RoutingEdit extends FyziklaniModule {
    /**
     * @var array
     */
    private $data;

    public function setData($data) {
        $this->data = $data;
    }

    public function getData(): string {
        return Json::encode($this->data);
    }

    protected function getMode(): string {
        return null;
    }

    protected function getComponentName(): string {
        return 'routing';
    }
}
