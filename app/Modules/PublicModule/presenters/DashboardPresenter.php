<?php

namespace FKSDB\Modules\PublicModule;

use FKSDB\Exceptions\BadTypeException;
use FKSDB\Localization\UnsupportedLanguageException;
use FKSDB\Modules\CoreModule\AuthenticationPresenter;
use FKSDB\UI\PageTitle;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use News;

/**
 * Just proof of concept.
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class DashboardPresenter extends BasePresenter {
    /**
     * @var News
     */
    private $news;

    /**
     * @param News $news
     * @return void
     */
    public function injectNews(News $news) {
        $this->news = $news;
    }

    /**
     * @throws AbortException
     * @throws ForbiddenRequestException
     */
    protected function unauthorizedAccess() {
        if ($this->getParam(AuthenticationPresenter::PARAM_DISPATCH)) {
            parent::unauthorizedAccess();
        } else {
            $this->redirect(':Core:Authentication:login'); // ask for a central dispatch
        }
    }

    public function authorizedDefault() {
        $login = $this->getUser()->getIdentity();
        $access = (bool)$login;
        $this->setAuthorized($access);
    }

    public function titleDefault() {
        $this->setPageTitle(new PageTitle(_('Dashboard'), 'fa fa-dashboard'));
    }

    /**
     * @throws BadTypeException
     * @throws ForbiddenRequestException
     * @throws UnsupportedLanguageException
     */
    public function renderDefault() {
        foreach ($this->news->getNews($this->getSelectedContest(), $this->getLang()) as $new) {
            $this->flashMessage($new);
        }
    }

}
