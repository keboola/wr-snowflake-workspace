<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace;

use Keboola\Component\Config\BaseConfig;
use Keboola\Component\UserException;

class Config extends BaseConfig
{
    public function getStorageApiUrl(): string
    {
        if (!getenv('KBC_URL')) {
            throw new UserException('Missing KBC API url');
        }
        return getenv('KBC_URL');
    }

    public function getStorageApiToken(): string
    {
        if (!getenv('KBC_TOKEN')) {
            throw new UserException('Missing KBC API token');
        }
        return getenv('KBC_TOKEN');
    }

    public function getWorkspaceId(): string
    {
        return $this->getValue(['parameters', 'workspaceId']);
    }
}
