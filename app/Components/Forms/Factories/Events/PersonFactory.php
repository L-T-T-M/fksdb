<?php

declare(strict_types=1);

namespace FKSDB\Components\Forms\Factories\Events;

use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Factories\ReferencedPerson\ReferencedPersonFactory;
use FKSDB\Models\Events\EventsExtension;
use FKSDB\Models\Events\Model\ExpressionEvaluator;
use FKSDB\Models\Events\Model\Holder\DataValidator;
use FKSDB\Models\Events\Model\Holder\Field;
use FKSDB\Models\Events\Model\PersonContainerResolver;
use FKSDB\Models\Expressions\Helpers;
use FKSDB\Models\ORM\Services\PersonService;
use FKSDB\Models\Persons\Resolvers\SelfResolver;
use Nette\DI\Container as DIContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Security\User;

class PersonFactory extends AbstractFactory
{

    private const VALUE_LOGIN = 'fromLogin';
    /** @var callable */
    private $fieldsDefinition;
    /** @var callable */
    private $searchType;
    /** @var callable */
    private $allowClear;
    /** @var callable */
    private $modifiable;
    /** @var callable */
    private $visible;
    private ReferencedPersonFactory $referencedPersonFactory;
    private ExpressionEvaluator $evaluator;
    private User $user;
    private PersonService $personService;
    private DIContainer $container;

    /**
     * PersonFactory constructor.
     * @param callable|array $fieldsDefinition
     * @param callable|string $searchType
     * @param callable|bool $allowClear
     * @param callable|bool $modifiable
     * @param callable|bool $visible
     */
    public function __construct(
        $fieldsDefinition,
        $searchType,
        $allowClear,
        $modifiable,
        $visible,
        ReferencedPersonFactory $referencedPersonFactory,
        ExpressionEvaluator $evaluator,
        User $user,
        PersonService $personService,
        DIContainer $container
    ) {
        $this->fieldsDefinition = $fieldsDefinition;
        $this->searchType = $searchType;
        $this->allowClear = $allowClear;
        $this->modifiable = $modifiable;
        $this->visible = $visible;
        $this->referencedPersonFactory = $referencedPersonFactory;
        $this->evaluator = $evaluator;
        $this->user = $user;
        $this->personService = $personService;
        $this->container = $container;
    }

    /**
     * @throws \ReflectionException
     */
    public function createComponent(Field $field): ReferencedId
    {
        $searchType = $this->evaluator->evaluate($this->searchType, $field);
        $allowClear = $this->evaluator->evaluate($this->allowClear, $field);

        $event = $field->getBaseHolder()->event;

        $resolver = new PersonContainerResolver(
            $field,
            $this->modifiable,
            $this->visible,
            new SelfResolver($this->user),
            $this->evaluator
        );
        $fieldsDefinition = $this->evaluateFieldsDefinition($field);
        $referencedId = $this->referencedPersonFactory->createReferencedPerson(
            $fieldsDefinition,
            $event->getContestYear(),
            $searchType,
            $allowClear,
            $resolver,
            $event
        );
        $referencedId->getSearchContainer()->setOption('label', $field->getLabel());
        $referencedId->getSearchContainer()->setOption('description', $field->getDescription());
        $referencedId->getReferencedContainer()->setOption('label', $field->getLabel());
        $referencedId->getReferencedContainer()->setOption('description', $field->getDescription());
        return $referencedId;
    }

    protected function setDefaultValue(BaseControl $control, Field $field): void
    {
        $default = $field->getValue();
        if ($default == self::VALUE_LOGIN) {
            if ($this->user->isLoggedIn() && $this->user->getIdentity()->person) {
                $default = $this->user->getIdentity()->person->person_id;
            } else {
                $default = null;
            }
        }
        $control->setDefaultValue($default);
    }

    /**
     * @throws \ReflectionException
     */
    public function validate(Field $field, DataValidator $validator): void
    {
        // check person ID itself
        parent::validate($field, $validator);

        $fieldsDefinition = $this->evaluateFieldsDefinition($field);
        $event = $field->getBaseHolder()->event;
        $contestYear = $event->getContestYear();
        $personId = $field->getValue();
        $person = $personId ? $this->personService->findByPrimary($personId) : null;

        if (!$person) {
            return;
        }

        foreach ($fieldsDefinition as $subName => $sub) {
            foreach ($sub as $fieldName => $metadata) {
                if (!is_array($metadata)) {
                    $metadata = ['required' => $metadata];
                }
                if (
                    $metadata['required']
                    && !ReferencedPersonFactory::isFilled(
                        $person,
                        $subName,
                        $fieldName,
                        $contestYear,
                        $event
                    )
                ) {
                    $validator->addError(
                        sprintf(
                            _('%s: %s is a required field.'),
                            $field->getBaseHolder()->label,
                            $field->getLabel() . '.' . $subName . '.' . $fieldName
                        )
                    ); //TODO better GUI name than DB identifier
                }
            }
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function evaluateFieldsDefinition(Field $field): array
    {
        Helpers::registerSemantic(EventsExtension::$semanticMap);
        $fieldsDefinition = Helpers::evalExpressionArray($this->fieldsDefinition, $this->container);

        foreach ($fieldsDefinition as &$sub) {
            foreach ($sub as &$metadata) {
                if (!is_array($metadata)) {
                    $metadata = ['required' => $metadata];
                }
                foreach ($metadata as &$value) {
                    $value = $this->evaluator->evaluate($value, $field);
                }
            }
        }
        return $fieldsDefinition;
    }
}
