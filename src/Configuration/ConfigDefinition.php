<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('workspaceId')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('tableId')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('dbName')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('incremental')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('items')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('dbName')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('type')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('size')->end()
                            ->scalarNode('nullable')->end()
                            ->scalarNode('default')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
