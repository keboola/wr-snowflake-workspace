<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\Writer;

use Keboola\Component\UserException;
use Keboola\DbWriter\SnowflakeWorkspace\Config;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Workspaces;

class Snowflake
{
    private Client $client;

    private Config $config;

    public function __construct(Client $client, Config $config)
    {
        $this->config = $config;
        $this->client = $client;
    }

    public function testConnectionAction(): array
    {
        $workspaces = new Workspaces($this->client);

        try {
            $workspaces->getWorkspace($this->config->getWorkspaceId());
        } catch (ClientException $clientException) {
            throw new UserException($clientException->getMessage(), 0, $clientException);
        }

        return [
            'status' => 'success',
        ];
    }

    public function runAction(): void
    {
        $workspaces = new Workspaces($this->client);

        $columns = [];
        foreach ($this->config->getItems() as $item) {
            $columns[] = [
                'source' => $item['name'],
                'destination' => $item['dbName'],
                'type' => $item['type'],
                'length' => $item['size'],
                'nullable' => $item['nullable'],
                'convertEmptyValuesToNull' => $item['nullable'],
            ];
        }

        $options = [
            'input' => [
                [
                    'source' => $this->config->getTableId(),
                    'destination' => $this->config->getDbName(),
                    'incremental' => $this->config->getIncremental(),
                    'columns' => $columns,
                ],
            ],
        ];

        try {
            $workspaces->loadWorkspaceData($this->config->getWorkspaceId(), $options);
        } catch (ClientException $clientException) {
            throw new UserException($clientException->getMessage(), 0, $clientException);
        }
    }
}
