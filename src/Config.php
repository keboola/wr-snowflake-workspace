<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace;

use Keboola\Component\Config\BaseConfig;
use Keboola\Component\UserException;

class Config extends BaseConfig
{
    public function getStorageApiUrl(): string
    {
        $url = getenv('KBC_URL');
        if (!$url) {
            throw new UserException('Missing KBC API url');
        }
        return $url;
    }

    public function getStorageApiToken(): string
    {
        $token = getenv('KBC_TOKEN');
        if (!$token) {
            throw new UserException('Missing KBC API token');
        }
        return $token;
    }

    public function getWorkspaceId(): string
    {
        return $this->getValue(['parameters', 'workspaceId']);
    }

    public function getTableId(): string
    {
        return $this->getValue(['parameters', 'tableId']);
    }

    public function getDbName(): string
    {
        return $this->getValue(['parameters', 'dbName']);
    }

    public function getItems(): array
    {
        return $this->getValue(['parameters', 'items']);
    }

    public function isIncremental(): bool
    {
        return (bool) $this->getValue(['parameters', 'incremental']);
    }
}
