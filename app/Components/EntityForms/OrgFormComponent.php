<?php

declare(strict_types=1);

namespace FKSDB\Components\EntityForms;

use FKSDB\Components\Forms\Containers\Models\ContainerWithOptions;
use FKSDB\Components\Forms\Factories\SingleReflectionFormFactory;
use FKSDB\Models\Authorization\ContestAuthorizator;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\ContestYearModel;
use FKSDB\Models\ORM\Models\OrgModel;
use FKSDB\Models\ORM\OmittedControlException;
use FKSDB\Models\ORM\Services\OrgService;
use FKSDB\Models\Persons\Resolvers\AclResolver;
use FKSDB\Models\Utils\FormUtils;
use Fykosak\Utils\Logging\Message;
use Nette\DI\Container;
use Nette\Forms\Form;

/**
 * @phpstan-extends EntityFormComponent<OrgModel>
 */
class OrgFormComponent extends EntityFormComponent
{
    use ReferencedPersonTrait;

    public const CONTAINER = 'org';
    private ContestYearModel $contestYear;
    private ContestAuthorizator $contestAuthorizator;
    private OrgService $orgService;
    private SingleReflectionFormFactory $singleReflectionFormFactory;

    public function __construct(Container $container, ContestYearModel $contestYear, ?OrgModel $model)
    {
        parent::__construct($container, $model);
        $this->contestYear = $contestYear;
    }

    final public function injectPrimary(
        SingleReflectionFormFactory $singleReflectionFormFactory,
        OrgService $orgService,
        ContestAuthorizator $contestAuthorizator
    ): void {
        $this->singleReflectionFormFactory = $singleReflectionFormFactory;
        $this->orgService = $orgService;
        $this->contestAuthorizator = $contestAuthorizator;
    }

    /**
     * @throws BadTypeException
     * @throws OmittedControlException
     */
    protected function configureForm(Form $form): void
    {
        $container = $this->createOrgContainer();
        $referencedId = $this->createPersonId(
            $this->contestYear,
            !isset($this->model),
            new AclResolver($this->contestAuthorizator, $this->contestYear->contest),
            $this->getContext()->getParameters()['forms']['adminOrg']
        );
        $container->addComponent($referencedId, 'person_id', 'since');
        $form->addComponent($container, self::CONTAINER);
    }

    protected function handleFormSuccess(Form $form): void
    {
        /**
         * @phpstan-var array{org:array{
         *      since:int,
         *      until:int|null,
         *      role:string,
         *      tex_signature:string,
         *      domain_alias:string,
         *      order:int,
         *      contribution:string,
         *     contest_id?:int,
         * }} $values
         */
        $values = $form->getValues('array');
        $data = FormUtils::emptyStrToNull2($values[self::CONTAINER]);
        if (!isset($data['contest_id'])) {
            $data['contest_id'] = $this->contestYear->contest_id;
        }
        $this->orgService->storeModel($data, $this->model);
        $this->getPresenter()->flashMessage(
            isset($this->model) ? _('Organizer has been updated.') : _('Organizer has been created.'),
            Message::LVL_SUCCESS
        );
        $this->getPresenter()->redirect('list');
    }

    protected function setDefaults(Form $form): void
    {
        if (isset($this->model)) {
            $form->setDefaults([self::CONTAINER => $this->model->toArray()]);
        }
    }

    /**
     * @throws BadTypeException
     * @throws OmittedControlException
     */
    private function createOrgContainer(): ContainerWithOptions
    {
        $container = new ContainerWithOptions($this->container);

        foreach (['since', 'until'] as $field) {
            $control = $this->singleReflectionFormFactory->createField(
                'org',
                $field,
                $this->contestYear->contest->getFirstYear(),
                $this->contestYear->contest->getLastYear()
            );
            $container->addComponent($control, $field);
        }

        foreach (['role', 'tex_signature', 'domain_alias', 'order', 'contribution'] as $field) {
            $control = $this->singleReflectionFormFactory->createField('org', $field);
            $container->addComponent($control, $field);
        }
        return $container;
    }
}
