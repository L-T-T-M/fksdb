<?php

declare(strict_types=1);

namespace FKSDB\Components\Controls\Navigation;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class NavigationExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::arrayOf(
            Expect::arrayOf(
                Expect::arrayOf(Expect::scalar()->nullable(), Expect::string()),
                Expect::string()
            ),
            Expect::string()
        );
    }

    public function loadConfiguration(): void
    {
        parent::loadConfiguration();

        $config = $this->getConfig();
        $navbar = $this->getContainerBuilder()->addDefinition('navbar')
            ->setType(NavigationFactory::class);

        $navbar->addSetup('setStructure', [$this->createFromStructure($config)]);
    }

    private function createFromStructure(array $structure): array
    {
        $structureData = [];
        foreach ($structure as $nodeId => $children) {
            $structureData[$nodeId] = $this->createNode($nodeId, []);
            $structureData[$nodeId]['parents'] = [];
            foreach ($children as $key => $arguments) {
                $structureData[$nodeId]['parents'][$key] = $this->createNode($key, $arguments);
            }
        }
        return $structureData;
    }

    private function createNode(string $nodeId, array $arguments): array
    {
        $fullQualityAction = str_replace('.', ':', $nodeId);
        $a = strrpos($fullQualityAction, ':');
        return [
            'presenter' => substr($fullQualityAction, 0, $a),
            'action' => substr($fullQualityAction, $a + 1),
            'params' => $arguments,
        ];
    }
}
