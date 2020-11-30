<?php

namespace FKSDB\Components\Controls\Choosers;

use FKSDB\ORM\Models\ModelContest;
use FKSDB\UI\Title;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Container;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class ContestChooser2 extends Chooser {

    private iterable $availableContests;
    private ModelContest $contest;

    public function __construct(Container $container, ModelContest $contest, iterable $availableContests) {
        parent::__construct($container);
        $this->contest = $contest;
        $this->availableContests = $availableContests;
    }

    protected function getTitle(): Title {
        return new Title($this->contest->name);
    }

    protected function getItems(): iterable {
        return $this->availableContests;
    }

    /**
     * @param ModelContest $item
     * @return bool
     */
    public function isItemActive($item): bool {
        return $this->contest->contest_id === $item->contest_id;
    }

    /**
     * @param ModelContest $item
     * @return Title
     */
    public function getItemTitle($item): Title {
        return new Title($this->contest->name);
    }

    /**
     * @param ModelContest $item
     * @return string
     * @throws InvalidLinkException
     */
    public function getItemLink($item): string {
        return $this->getPresenter()->link('this', ['contestId' => $item->contest_id]);
    }
}
