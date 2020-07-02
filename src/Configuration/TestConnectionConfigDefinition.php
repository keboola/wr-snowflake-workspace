<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class TestConnectionConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('workspaceId')->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
