<?php

namespace FKSDB\Components\Controls\Badges;

use FKSDB\Components\Controls\BaseComponent;
use Nette\Templating\FileTemplate;
use Nette\Utils\Html;

/**
 * Class Badge
 * @author Michal Červeňák <miso@fykos.cz>
 * @property-read FileTemplate $template
 */
abstract class Badge extends BaseComponent {

    abstract public static function getHtml(...$args): Html;

    /**
     * @param mixed ...$args
     * @return void
     */
    public function render(...$args) {
        $this->template->html = static::getHtml(...$args);
        $this->template->setFile(__DIR__ . '/layout.latte');
        $this->template->render();
    }
}
