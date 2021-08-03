<?php

declare(strict_types=1);

namespace FKSDB\Modules\OrgModule;


use FKSDB\Components\PDFGenerators\Provider\AbstractProviderComponent;
use FKSDB\Components\PDFGenerators\TeamSeating\Single\Provider;
use FKSDB\Models\ORM\Services\ServiceEvent;

class PdfGeneratorPresenter extends BasePresenter
{
    protected function createComponentTest(): AbstractProviderComponent
    {
        return new AbstractProviderComponent(
            new Provider(
                $this->getContext()->getByType(ServiceEvent::class)->findByPrimary(145),
                $this->getContext()
            ),
            $this->getContext()
        );
    }
}
