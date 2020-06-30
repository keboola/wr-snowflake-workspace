<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\FunctionalTests;

use Keboola\DatadirTests\DatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\DatadirTests\DatadirTestsProviderInterface;

class DatadirTest extends DatadirTestCase
{
    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);
        $this->replacePartOfConfig($tempDatadir->getTmpFolder());

        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    /**
     * @return DatadirTestsProviderInterface[]
     */
    protected function getDataProviders(): array
    {
        return [
            new DatadirTestProvider($this->getTestFileDir()),
        ];
    }

    private function replacePartOfConfig(string $tempDataDir): void
    {
        $configFile = $tempDataDir . '/config.json';
        $config = json_decode((string) file_get_contents($configFile), true);
        $config['parameters'] = array_merge(
            $config['parameters'],
            [
                'workspaceId' => getenv('WORKSPACE_ID'),
            ]
        );
        file_put_contents($configFile, json_encode($config));
    }
}
