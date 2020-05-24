<?php

namespace OrgModule;

use FKSDB\Components\Controls\SeriesChooser;
use FKSDB\Exceptions\BadTypeException;
use FKSDB\SeriesCalculator;
use FKSDB\CoreModule\ISeriesPresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Http\Response;

/**
 * Presenter providing series context and a way to modify it.
 *
 */
abstract class SeriesPresenter extends BasePresenter implements ISeriesPresenter {

    /**
     * @var int
     * @persistent
     */
    public $series;

    /**
     * @var SeriesCalculator
     */
    protected $seriesCalculator;

    /**
     * @param SeriesCalculator $seriesCalculator
     * @return void
     */
    public function injectSeriesCalculator(SeriesCalculator $seriesCalculator) {
        $this->seriesCalculator = $seriesCalculator;
    }

    /**
     * @throws BadRequestException
     * @throws BadTypeException
     * @throws AbortException
     * @throws ForbiddenRequestException
     * @throws \Exception
     */
    protected function startup() {
        parent::startup();
        $control = $this->getComponent('seriesChooser');
        if (!$control instanceof SeriesChooser) {
            throw new BadTypeException(SeriesChooser::class, $control);
        }
        if (!$control->isValid()) {
            throw new BadRequestException('Nejsou dostupné žádné série.', Response::S500_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return int
     * @throws BadRequestException
     * @throws \Exception
     */
    public function getSelectedSeries() {
        $control = $this->getComponent('seriesChooser');
        if (!$control instanceof SeriesChooser) {
            throw new BadTypeException(SeriesChooser::class, $control);
        }
        return $control->getSeries();
    }

    public function createComponentSeriesChooser(): SeriesChooser {
        return new SeriesChooser($this->getContext());
    }

    /**
     * @param string $title
     * @param string $icon
     * @param string $subTitle
     * @throws BadRequestException
     */
    protected function setTitle(string $title, string $icon = '', string $subTitle = '') {
        parent::setTitle($title, $icon, $subTitle . ' ' . sprintf(_('%d. series'), $this->getSelectedSeries()));
    }
}
