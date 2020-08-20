<?php

namespace FKSDB\DBReflection\LinkFactories;

use FKSDB\ORM\AbstractModelSingle;
use Nette\Application\UI\Presenter;

/**
 * Interface ILinkFactory
 * @author Michal Červeňák <miso@fykos.cz>
 */
interface ILinkFactory {

    public function create(Presenter $presenter, AbstractModelSingle $model): string;

    public function createLinkParameters(AbstractModelSingle $model): array;

    public function getText(): string;
}
