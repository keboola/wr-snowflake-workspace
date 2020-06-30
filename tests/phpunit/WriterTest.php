<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\Tests;

use Keboola\DbWriter\SnowflakeWorkspace\Config;
use Keboola\DbWriter\SnowflakeWorkspace\ConfigDefinition;
use Keboola\DbWriter\SnowflakeWorkspace\Writer\Snowflake;
use Keboola\StorageApi\Client;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'url' => getenv('KBC_URL'),
            'token' => getenv('KBC_TOKEN'),
        ]);
    }

    public function testConnection(): void
    {
        $config = new Config(
            [
                'action' => 'testConnection',
                'parameters' => [
                    'workspaceId' => getenv('WORKSPACE_ID'),
                ],
            ],
            new ConfigDefinition()
        );
        $writer = new Snowflake($this->client, $config);
        $result = $writer->testConnectionAction();

        Assert::assertArrayHasKey('status', $result);
        Assert::assertEquals('success', $result['status']);
    }
}
