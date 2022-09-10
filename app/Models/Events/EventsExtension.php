<?php

declare(strict_types=1);

namespace FKSDB\Models\Events;

use FKSDB\Models\Events\Exceptions\MachineDefinitionException;
use FKSDB\Models\Events\Machine\BaseMachine;
use FKSDB\Models\Events\Machine\Transition;
use FKSDB\Models\Events\Model\Holder\BaseHolder;
use FKSDB\Models\Events\Model\Holder\Field;
use FKSDB\Models\Events\Semantics\Count;
use FKSDB\Models\Events\Semantics\EventWas;
use FKSDB\Models\Events\Semantics\Parameter;
use FKSDB\Models\Events\Semantics\RegOpen;
use FKSDB\Models\Events\Semantics\Role;
use FKSDB\Models\Events\Semantics\State;
use FKSDB\Components\Forms\Factories\Events\ArrayOptions;
use FKSDB\Components\Forms\Factories\Events\CheckboxFactory;
use FKSDB\Components\Forms\Factories\Events\ChooserFactory;
use FKSDB\Components\Forms\Factories\Events\PersonFactory;
use FKSDB\Models\Expressions\Helpers;
use FKSDB\Models\Expressions\NeonSchemaException;
use FKSDB\Models\Expressions\NeonScheme;
use FKSDB\Models\ORM\Models\EventParticipantStatus;
use FKSDB\Models\Transitions\Transition\BehaviorType;
use FKSDB\Models\Transitions\TransitionsExtension;
use Nette\DI\Config\Loader;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\InvalidArgumentException;
use Nette\Utils\Strings;

/**
 * It's a f**** magic!
 */
class EventsExtension extends CompilerExtension
{
    public const FIELD_FACTORY = 'Field_';
    public const MACHINE_PREFIX = 'Machine_';
    public const HOLDER_PREFIX = 'Holder_';


    /** @const Regexp for configuration section names */
    public const NAME_PATTERN = '/[a-z0-9_]/i';

    public static array $semanticMap = [
        'RefPerson' => PersonFactory::class,
        'Chooser' => ChooserFactory::class,
        'Checkbox' => CheckboxFactory::class,
        'Options' => ArrayOptions::class,
        'role' => Role::class,
        'regOpen' => RegOpen::class,
        'eventWas' => EventWas::class,
        'state' => State::class,
        'param' => Parameter::class,
        'parameter' => Parameter::class,
        'count' => Count::class,
    ];

    private array $scheme;

    private string $schemeFile;

    public function __construct(string $schemaFile)
    {
        $this->schemeFile = $schemaFile;
        Helpers::registerSemantic(self::$semanticMap);
    }

    /**
     * @throws MachineDefinitionException
     * @throws NeonSchemaException
     */
    public function loadConfiguration(): void
    {
        parent::loadConfiguration();

        $this->loadScheme();

        $config = $this->getConfig();

        $eventDispatchFactory = $this->getContainerBuilder()
            ->addDefinition('event.dispatch')->setFactory(EventDispatchFactory::class);

        $eventDispatchFactory->addSetup(
            'setTemplateDir',
            [$this->getContainerBuilder()->parameters['events']['templateDir']]
        );
        foreach ($config as $definitionName => $definition) {
            $this->validateConfigName($definitionName);
            $definition = NeonScheme::readSection($definition, $this->scheme['definition']);

            $keys = $this->createAccessKeys($definition);
            $machine = $this->createMachineFactory($definitionName);
            $holder = $this->createHolderFactory($definitionName, $definition);
            $eventDispatchFactory->addSetup(
                'addEvent',
                [$keys, Container::getMethodName($holder->getName()), $machine->getName(), $definition['formLayout']]
            );
        }
    }

    private function loadScheme(): void
    {
        $loader = new Loader();
        $this->getContainerBuilder()->addDependency($this->schemeFile);
        $this->scheme = $loader->load($this->schemeFile);
    }

    private function getBaseMachineConfig(string $eventName): array
    {
        return $this->getConfig()[$eventName]['baseMachine'];
    }

    private function validateConfigName(string $name): void
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidArgumentException("Section name '$name' in events configuration is invalid.");
        }
    }

    private function createTransitionService(string $baseName, string $mask, array $definition): array
    {
        [$sources, $target] = TransitionsExtension::parseMask($mask, EventParticipantStatus::class);
        $factories = [];
        foreach ($sources as $source) {
            if (!$definition['label'] && $definition['visible'] !== false) {
                throw new MachineDefinitionException(
                    "Transition $mask with non-false visibility must have label defined."
                );
            }

            $factory = $this->getContainerBuilder()->addDefinition(
                $this->getTransitionName($baseName, $mask . '__' . $source)
            );
            $factory->setFactory(Transition::class, [$definition['label']])
                ->addSetup('setSourceStateEnum', [$source])
                ->addSetup('setTargetStateEnum', [$target])
                ->addSetup(
                    'setBehaviorType',
                    [
                        BehaviorType::tryFrom($transitionConfig['behaviorType'] ?? BehaviorType::DEFAULT),
                    ]
                );
            $parameters = array_keys($this->scheme['transition']);
            foreach ($parameters as $parameter) {
                switch ($parameter) {
                    case 'label':
                    case 'onExecuted':
                    case 'behaviorType':
                        break;
                    default:
                        if (isset($definition[$parameter])) {
                            $factory->addSetup('set' . ucfirst($parameter), [$definition[$parameter]]);
                        }
                }
            }
            $factory->addSetup('setEvaluator', ['@events.expressionEvaluator']);
            foreach ($definition['onExecuted'] as $cb) {
                $factory->addSetup('addAfterExecute', [$cb]);
            }
            $factories[] = $factory;
        }
        return $factories;
    }

    private function createFieldService(array $fieldDefinition): ServiceDefinition
    {
        $field = $this->getContainerBuilder()
            ->addDefinition($this->getFieldName())
            ->setFactory(Field::class, [$fieldDefinition['0'], $fieldDefinition['label']]);

        $field->addSetup('setEvaluator', ['@events.expressionEvaluator']);
        foreach ($fieldDefinition as $key => $parameter) {
            if (is_numeric($key)) {
                continue;
            }
            switch ($key) {
                case 'name':
                case 'label':
                    break;
                default:
                    $field->addSetup('set' . ucfirst($key), [$parameter]);
            }
        }
        return $field;
    }

    /**
     * @param string[][] $definition
     * @return string[]
     */
    private function createAccessKeys(array $definition): array
    {
        $keys = [];
        foreach ($definition['eventTypeIds'] as $eventTypeId) {
            if ($definition['eventYears'] === true) {
                $keys[] = (string)$eventTypeId;
            } else {
                foreach ($definition['eventYears'] as $year) {
                    $key = $eventTypeId . '-' . $year;
                    $keys[] = $key;
                }
            }
        }
        return $keys;
    }

    /**
     * @throws MachineDefinitionException
     * @throws NeonSchemaException
     */
    private function createMachineFactory(string $eventName): ServiceDefinition
    {
        $factoryName = $this->getMachineName($eventName);
        $definition = $this->getBaseMachineConfig($eventName);
        $factory = $this->getContainerBuilder()->addDefinition($factoryName);

        $factory->setFactory(BaseMachine::class);

        $definition = NeonScheme::readSection($definition, $this->scheme['baseMachine']);

        foreach ($definition['transitions'] as $mask => $transitionRawDef) {
            $transitionDef = NeonScheme::readSection($transitionRawDef, $this->scheme['transition']);
            $transitions = $this->createTransitionService($factoryName, $mask, $transitionDef);
            foreach ($transitions as $transition) {
                $factory->addSetup(
                    'addTransition',
                    [$transition]
                );
            }
        }
        return $factory;
    }

    /*
     * Specialized data factories
     */

    /**
     * @throws MachineDefinitionException
     * @throws NeonSchemaException
     */
    private function createHolderFactory(string $eventName, array $definition): ServiceDefinition
    {
        $machineDef = NeonScheme::readSection($definition['machine'], $this->scheme['machine']);

        $instanceDef = NeonScheme::readSection($machineDef['baseMachine'], $this->scheme['bmInstance']);
        $factoryName = $this->getHolderName($eventName);
        $definition = $this->getBaseMachineConfig($eventName);
        $factory = $this->getContainerBuilder()->addDefinition($factoryName);
        $factory->setFactory(BaseHolder::class);

        $parameters = array_keys($this->scheme['bmInstance']);

        foreach ($parameters as $parameter) {
            switch ($parameter) {
                case 'modifiable':
                case 'label':
                    $factory->addSetup('set' . ucfirst($parameter), [$instanceDef[$parameter]]);
                    break;
                default:
                    break;
            }
        }

        $definition = NeonScheme::readSection($definition, $this->scheme['baseMachine']);

        $factory->addSetup('setService', [$definition['service']]);
        $factory->addSetup('setEvaluator', ['@events.expressionEvaluator']);
        $factory->addSetup('setValidator', ['@events.dataValidator']);

        $config = $this->getConfig();
        $paramScheme = $definition['paramScheme'] ?? $config[$eventName]['paramScheme'];
        foreach (array_keys($paramScheme) as $paramKey) {
            $this->validateConfigName($paramKey);
        }
        $factory->addSetup('setParamScheme', [$paramScheme]);

        $hasNonDetermining = false;
        foreach ($definition['fields'] as $name => $fieldDef) {
            $fieldDef = NeonScheme::readSection($fieldDef, $this->scheme['field']);
            if ($fieldDef['determining']) {
                if ($fieldDef['required']) {
                    throw new MachineDefinitionException(
                        "Field '$name' cannot be both required and determining. Set required on the base holder."
                    );
                }
                if ($hasNonDetermining) {
                    throw new MachineDefinitionException(
                        "Field '$name' cannot be preceded by non-determining fields. Reorder the fields."
                    );
                }
                $fieldDef['required'] = $instanceDef['required'];
            } else {
                $hasNonDetermining = true;
            }
            array_unshift($fieldDef, $name);
            $factory->addSetup('addField', [new Statement($this->createFieldService($fieldDef))]);
        }
        foreach ($machineDef['processings'] as $processing) {
            $factory->addSetup('addProcessing', [$processing]);
        }

        foreach ($machineDef['formAdjustments'] as $formAdjustment) {
            $factory->addSetup('addFormAdjustment', [$formAdjustment]);
        }
        return $factory;
    }

    /* **************** Naming **************** */

    private function getMachineName(string $name): string
    {
        return $this->prefix(self::MACHINE_PREFIX . $name);
    }

    private function getHolderName(string $name): string
    {
        return $this->prefix(self::HOLDER_PREFIX . $name);
    }

    private function getTransitionName(string $baseName, string $mask): string
    {
        return uniqid($baseName . '_transition_' . str_replace('-', '_', Strings::webalize($mask)) . '__');
    }

    private function getFieldName(): string
    {
        return $this->prefix(uniqid(self::FIELD_FACTORY));
    }
}
