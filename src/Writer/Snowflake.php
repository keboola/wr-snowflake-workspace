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

    public function testConnectionAction(Config $config): array
    {
        $workspaces = new Workspaces($this->client);

        try {
            $workspaces->getWorkspace($config->getWorkspaceId());
        } catch (ClientException $clientException) {
            throw new UserException($clientException->getMessage(), 0, $clientException);
        }

        return [
            'status' => 'success',
        ];
    }
}
