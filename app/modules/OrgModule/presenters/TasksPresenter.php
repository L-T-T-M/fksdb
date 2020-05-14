<?php

namespace OrgModule;

use FKSDB\Astrid\Downloader;
use FKSDB\Components\Controls\FormControl\FormControl;
use FKSDB\Logging\FlashMessageDump;
use FKSDB\SeriesCalculator;
use FKSDB\Submits\UploadException;
use FKSDB\Exceptions\ModelException;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\DeprecatedException;
use Tracy\Debugger;
use Pipeline\PipelineException;
use SimpleXMLElement;
use FKSDB\Tasks\PipelineFactory;
use FKSDB\Tasks\SeriesData;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class TasksPresenter extends BasePresenter {

    const SOURCE_ASTRID = 'astrid';
    const SOURCE_FILE = 'file';

    /**
     * @var SeriesCalculator
     */
    private $seriesCalculator;

    /**
     * @var PipelineFactory
     */
    private $pipelineFactory;

    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @param SeriesCalculator $seriesCalculator
     */
    public function injectSeriesCalculator(SeriesCalculator $seriesCalculator) {
        $this->seriesCalculator = $seriesCalculator;
    }

    /**
     * @param PipelineFactory $pipelineFactory
     */
    public function injectPipelineFactory(PipelineFactory $pipelineFactory) {
        $this->pipelineFactory = $pipelineFactory;
    }

    /**
     * @param Downloader $downloader
     */
    public function injectDownloader(Downloader $downloader) {
        $this->downloader = $downloader;
    }

    /**
     * @throws BadRequestException
     */
    public function authorizedImport() {
        $this->setAuthorized($this->getContestAuthorizator()->isAllowed('task', 'insert', $this->getSelectedContest()));
    }

    public function titleImport() {
        $this->setTitle(_('Import úloh'), 'fa fa-upload');
    }

    /**
     * @return FormControl
     * @throws BadRequestException
     */
    protected function createComponentSeriesForm(): FormControl {
        $control = new FormControl();
        $form = $control->getForm();

        $source = $form->addRadioList('source', _('Zdroj úloh'), [
            self::SOURCE_ASTRID => _('Astrid'),
            self::SOURCE_FILE => _('XML soubor (nové XML)'),
        ]);
        $source->setDefaultValue(self::SOURCE_ASTRID);

        // Astrid download
        $seriesItems = range(1, $this->seriesCalculator->getTotalSeries($this->getSelectedContest(), $this->getSelectedYear()));
        $form->addSelect('series', _('Série'))
            ->setItems($seriesItems, false);

        $upload = $form->addUpload('file', _('XML soubor úloh'));
        $upload->addConditionOn($source, Form::EQUAL, self::SOURCE_FILE)->toggle($upload->getHtmlId() . '-pair');

        $form->addSubmit('submit', _('Importovat'));

        $form->onSuccess[] = function (Form $seriesForm) {
            return $this->validSubmitSeriesForm($seriesForm);
        };
        return $control;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return bool
     */
    private function isLegacyXml(SimpleXMLElement $xml): bool {
        return $xml->getName() === 'problems';
    }

    /**
     * @param Form $seriesForm
     * @throws AbortException
     * @throws BadRequestException
     */
    private function validSubmitSeriesForm(Form $seriesForm) {
        $values = $seriesForm->getValues();
        $series = $values['series'];
        $file = null;

        switch ($values['source']) {
            case self::SOURCE_ASTRID:
                $file = $this->downloader->downloadSeriesTasks($this->getSelectedContest(), $this->getSelectedYear(), $series);
                break;
            case self::SOURCE_FILE:
                if (!$values['file']->isOk()) {
                    throw new UploadException;
                }
                $file = $values['file']->getTemporaryFile();
                break;
            default:
                throw new BadRequestException();
        }

        try {
            $xml = simplexml_load_file($file);

            if ($this->isLegacyXml($xml)) {
                throw new DeprecatedException();
            } else {
                $data = new SeriesData($this->getSelectedContest(), $this->getSelectedYear(), $series, $xml);
                $pipeline = $this->pipelineFactory->create();
                $pipeline->setInput($data);
                $pipeline->run();
                FlashMessageDump::dump($pipeline->getLogger(), $this);
                $this->flashMessage(_('Úlohy pro úspěšně importovány.'), self::FLASH_SUCCESS);
            }
        } catch (PipelineException $exception) {
            $this->flashMessage(sprintf(_('Při ukládání úloh došlo k chybě. %s'), $exception->getMessage()), self::FLASH_ERROR);
            Debugger::log($exception);
        } catch (ModelException $exception) {
            $this->flashMessage(sprintf(_('Při ukládání úloh došlo k chybě.')), self::FLASH_ERROR);
            Debugger::log($exception);
        } catch (DeprecatedException $exception) {
            $this->flashMessage(_('Legacy XML format is deprecated'), self::FLASH_ERROR);
        } finally {
            unlink($file);
        }
        $this->redirect('this');
    }
}
