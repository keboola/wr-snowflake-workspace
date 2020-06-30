<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace;

use Keboola\Component\BaseComponent;
use Keboola\DbWriter\SnowflakeWorkspace\Writer\Snowflake;
use Keboola\StorageApi\Client;
use Psr\Log\LoggerInterface;

class Component extends BaseComponent
{
    private const ACTION_TEST_CONNECTION = 'testConnection';

    private Client $client;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);

        /** @var Config $config */
        $config = $this->getConfig();
        $this->client = new Client([
            'url' => $config->getStorageApiUrl(),
            'token' => $config->getStorageApiToken(),
        ]);
    }

    protected function run(): void
    {
        // @TODO implement
    }

    public function testConnectionAction(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $writer = new Snowflake($this->client, $config);

        return $writer->testConnectionAction($config);
    }

    protected function getSyncActions(): array
    {
        return [
            self::ACTION_TEST_CONNECTION => 'testConnectionAction'
        ];
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
