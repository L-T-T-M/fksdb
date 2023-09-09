<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\Person\Detail;

use FKSDB\Components\Grids\Components\Container\RowContainer;
use FKSDB\Components\Grids\Components\Referenced\TemplateItem;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\FieldLevelPermission;
use FKSDB\Models\ORM\Models\OrganizerModel;
use Fykosak\NetteORM\TypedGroupedSelection;
use Fykosak\Utils\UI\Title;

/**
 * @phpstan-extends DetailComponent<OrganizerModel>
 */
class OrganizerListComponent extends DetailComponent
{
    protected function getMinimalPermissions(): int
    {
        return FieldLevelPermission::ALLOW_RESTRICT;
    }

    /**
     * @phpstan-return TypedGroupedSelection<OrganizerModel>
     */
    protected function getModels(): TypedGroupedSelection
    {
        return $this->person->getOrganizers();
    }

    protected function getHeadline(): Title
    {
        return new Title(null, _('Organizers'));
    }

    /**
     * @throws BadTypeException
     * @throws \ReflectionException
     */
    protected function configure(): void
    {
        $this->classNameCallback = fn(OrganizerModel $model) => 'alert alert-' . $model->contest->getContestSymbol();
        /** @phpstan-var RowContainer<OrganizerModel> $row0 */
        $row0 = new RowContainer($this->container);
        $this->addRow($row0, 'row0');
        $row0->addComponent(new TemplateItem($this->container, '@contest.name'), 'contest_name');
        $row0->addComponent(
            new TemplateItem($this->container, _('@org.since - @org.until')),
            'duration'
        );
        /** @phpstan-var RowContainer<OrganizerModel> $row1 */
        $row1 = new RowContainer($this->container);
        $row1->addComponent(
            new TemplateItem($this->container, '@org.domain_alias', '@org.domain_alias:title'),
            'domain_alias'
        );
        $row1->addComponent(
            new TemplateItem($this->container, '\signature{@org.tex_signature}', '@org.tex_signature:title'),
            'tex_signature'
        );
        $this->addRow($row1, 'row1');
        if ($this->isOrganizer) {
            $this->addPresenterButton(
                ':Organizer:Organizer:edit',
                'edit',
                _('Edit'),
                false,
                ['contestId' => 'contest_id', 'id' => 'org_id']
            );
            $this->addPresenterButton(
                ':Organizer:Organizer:detail',
                'detail',
                _('Detail'),
                false,
                ['contestId' => 'contest_id', 'id' => 'org_id']
            );
        }
    }
}
