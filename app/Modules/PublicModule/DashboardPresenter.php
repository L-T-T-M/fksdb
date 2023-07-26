<?php

declare(strict_types=1);

namespace FKSDB\Modules\PublicModule;

use FKSDB\Models\News;
use Fykosak\Utils\UI\PageTitle;

final class DashboardPresenter extends BasePresenter
{
    private News $news;

    final public function injectNews(News $news): void
    {
        $this->news = $news;
    }

    public function titleDefault(): PageTitle
    {
        return new PageTitle(null, _('Dashboard'), 'fas fa-chalkboard');
    }

    public function authorizedDefault(): bool
    {
        return (bool)$this->getLoggedPerson();
    }

    final public function renderDefault(): void
    {
        foreach ($this->news->getNews($this->getSelectedContest(), $this->translator->lang) as $new) {
            $this->flashMessage($new);
        }
    }
}
