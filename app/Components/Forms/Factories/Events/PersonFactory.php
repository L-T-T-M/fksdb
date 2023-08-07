<?php

declare(strict_types=1);

namespace FKSDB\Components\Forms\Factories\Events;

use FKSDB\Components\Forms\Containers\Models\ReferencedPersonContainer;
use FKSDB\Components\Forms\Controls\ReferencedId;
use FKSDB\Components\Forms\Factories\ReferencedPerson\ReferencedPersonFactory;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Events\Model\Holder\Field;
use FKSDB\Models\Events\Model\PersonContainerResolver;
use FKSDB\Models\Expressions\Helpers;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\Persons\Resolvers\SelfResolver;
use Nette\Forms\Controls\BaseControl;
use Nette\Security\User;

/**
 * @phpstan-import-type EvaluatedFieldsDefinition from ReferencedPersonContainer
 */
class PersonFactory extends AbstractFactory
{
    private const VALUE_LOGIN = 'fromLogin';
    /**
     * @phpstan-var EvaluatedFieldsDefinition
     */
    private array $fieldsDefinition;
    private string $searchType;
    /** @var (callable(BaseHolder):bool)|bool */
    private $allowClear;
    /** @var (callable(BaseHolder):bool)|bool */
    private $modifiable;
    /** @var (callable(BaseHolder):bool)|bool */
    private $visible;
    private ReferencedPersonFactory $referencedPersonFactory;
    private User $user;

    /**
     * @param (callable(BaseHolder):bool)|bool $allowClear
     * @param (callable(BaseHolder):bool)|bool $modifiable
     * @param (callable(BaseHolder):bool)|bool $visible
     * @phpstan-param array<string,array<string,mixed>> $fieldsDefinition
     */
    public function __construct(
        array $fieldsDefinition,
        string $searchType,
        $allowClear,
        $modifiable,
        $visible,
        ReferencedPersonFactory $referencedPersonFactory,
        User $user
    ) {
        $this->fieldsDefinition = $fieldsDefinition;
        $this->searchType = $searchType;
        $this->allowClear = $allowClear;
        $this->modifiable = $modifiable;
        $this->visible = $visible;
        $this->referencedPersonFactory = $referencedPersonFactory;
        $this->user = $user;
    }

    /**
     * @throws \ReflectionException
     * @phpstan-return ReferencedId<PersonModel>
     */
    public function createComponent(Field $field): ReferencedId
    {
        $resolver = new PersonContainerResolver(
            $field,
            $this->modifiable,
            $this->visible,
            new SelfResolver($this->user)
        );
        $fieldsDefinition = $this->evaluateFieldsDefinition($field);
        $referencedId = $this->referencedPersonFactory->createReferencedPerson(
            $fieldsDefinition,
            $field->holder->event->getContestYear(),
            $this->searchType,
            is_callable($this->allowClear) ? ($this->allowClear)($field->holder) : $this->allowClear,
            $resolver,
            $field->holder->event
        );
        $referencedId->searchContainer->setOption('label', $field->label);
        $referencedId->searchContainer->setOption('description', $field->description);
        $referencedId->referencedContainer->setOption('label', $field->label);
        $referencedId->referencedContainer->setOption('description', $field->description);
        return $referencedId;
    }

    protected function setDefaultValue(BaseControl $control, Field $field): void
    {
        $default = $field->getValue();
        if ($default == self::VALUE_LOGIN) {
            if ($this->user->isLoggedIn() && $this->user->getIdentity()->person) { // @phpstan-ignore-line
                $default = $this->user->getIdentity()->person->person_id;
            } else {
                $default = null;
            }
        }
        $control->setDefaultValue($default);
    }

    /**
     * @throws \ReflectionException
     * @phpstan-return EvaluatedFieldsDefinition
     */
    private function evaluateFieldsDefinition(Field $field): array
    {
        $fieldsDefinition = Helpers::resolveArrayExpression($this->fieldsDefinition);

        foreach ($fieldsDefinition as &$sub) {
            foreach ($sub as &$metadata) {
                if (!is_array($metadata)) {
                    $metadata = ['required' => $metadata];
                }
                foreach ($metadata as &$value) {
                    $value = is_callable($value) ? ($value)($field->holder) : $value;
                }
            }
        }
        return $fieldsDefinition;
    }
}
