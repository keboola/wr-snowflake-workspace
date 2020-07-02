<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\Tests;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\SnowflakeWorkspace\Config;
use Keboola\DbWriter\SnowflakeWorkspace\Configuration\ConfigDefinition;
use Keboola\DbWriter\SnowflakeWorkspace\Configuration\TestConnectionConfigDefinition;
use Keboola\DbWriter\SnowflakeWorkspace\Writer\Snowflake;
use Keboola\SnowflakeDbAdapter\Connection;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Workspaces;
use Keboola\Temp\Temp;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    private Client $client;

    private array $workspace;

    private ?string $bucketId;

    private const BUCKET_NAME = 'wr-snowflake-workspace';

    protected function setUp(): void
    {
        $this->bucketId = null;
        $this->client = new Client([
            'url' => getenv('KBC_URL'),
            'token' => getenv('KBC_TOKEN'),
        ]);

        $this->prepareWorkspace();
    }

    private function prepareWorkspace(): void
    {
        $workspaces = new Workspaces($this->client);
        $this->workspace = $workspaces->createWorkspace(['backend' => 'snowflake']);
    }

    private function prepareBucketAndTable(array $tableOptions = []): void
    {
        $bucket = $this->client->getBucketId(self::BUCKET_NAME, 'in');
        if (!$bucket) {
            $bucket = $this->client->createBucket(self::BUCKET_NAME, 'in');
        }

        $this->bucketId = (string) $bucket;

        $csvFile = new CsvFile(__DIR__ . '/data/sales.csv');

        $this->client->createTable($this->bucketId, 'sales', $csvFile, $tableOptions);
    }

    protected function tearDown(): void
    {
        // cleanup workspace
        $workspaces = new Workspaces($this->client);
        $workspaces->deleteWorkspace($this->workspace['id']);

        // cleanup tables and bucket
        if ($this->bucketId) {
            $this->client->dropBucket($this->bucketId, ['force' => 'force']);
        }
    }

    public function testConnection(): void
    {
        $config = new Config(
            [
                'action' => 'testConnection',
                'parameters' => [
                    'workspaceId' => (string) $this->workspace['id'],
                ],
            ],
            new TestConnectionConfigDefinition()
        );
        $writer = new Snowflake($this->client, $config);
        $result = $writer->testConnectionAction();

        Assert::assertArrayHasKey('status', $result);
        Assert::assertEquals('success', $result['status']);
    }

    public function testSimpleWrite(): void
    {
        $this->prepareBucketAndTable();

        $config = new Config(
            [
                'data_dir' => __DIR__ . '/data/',
                'parameters' => [
                    'workspaceId' => (string) $this->workspace['id'],
                    'tableId' => sprintf('%s.sales', $this->bucketId),
                    'dbName' => 'sales',
                    'items' => [
                        [
                            'name' => 'id',
                            'dbName' => 'id',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'name',
                            'dbName' => 'name',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'glasses',
                            'dbName' => 'glasses',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'age',
                            'dbName' => 'age',
                            'type' => 'varchar',
                            'size' => '10',
                            'nullable' => false,
                            'default' => '',
                        ],
                    ],
                ],
            ],
            new ConfigDefinition()
        );

        $writer = new Snowflake($this->client, $config);
        $writer->runAction();

        $data = $this->getConnection()->fetchAll('select * from "sales";');

        array_walk($data, function (&$item): void {
            $item = array_values($item);
        });

        $expectedData = iterator_to_array(new CsvFile(__DIR__ . '/data/sales.csv'));
        array_shift($expectedData); // shift header

        Assert::assertEquals(count($expectedData), count($data));
        Assert::assertEquals($expectedData, $data);
    }

    public function testIncrementalWrite(): void
    {
        $this->prepareBucketAndTable(['primaryKey' => 'id']);

        $config = new Config(
            [
                'data_dir' => __DIR__ . '/data/',
                'parameters' => [
                    'workspaceId' => (string) $this->workspace['id'],
                    'tableId' => sprintf('%s.sales', $this->bucketId),
                    'dbName' => 'sales',
                    'items' => [
                        [
                            'name' => 'id',
                            'dbName' => 'id',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'name',
                            'dbName' => 'name',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'glasses',
                            'dbName' => 'glasses',
                            'type' => 'varchar',
                            'size' => '255',
                            'nullable' => false,
                            'default' => '',
                        ],
                        [
                            'name' => 'age',
                            'dbName' => 'age',
                            'type' => 'varchar',
                            'size' => '10',
                            'nullable' => false,
                            'default' => '',
                        ],
                    ],
                ],
            ],
            new ConfigDefinition()
        );

        $writer = new Snowflake($this->client, $config);
        $writer->runAction();

        $dataBeforeIncrementalWrite = $this->getConnection()->fetchAll('select * from "sales";');

        $temp = new Temp('incrementalWrite');
        $tempFile = $temp->createTmpFile();
        $incrementalData = [
            [
                'id' => '12',
                'name' => 'Test incremental write',
                'glasses' => 'no',
                'age' => '33',
            ],
            [
                'id' => '13',
                'name' => 'Test incremental write 2',
                'glasses' => 'yes',
                'age' => '44',
            ],
        ];
        $incrementalDataFile = new CsvFile($tempFile->getBasename());
        $incrementalDataFile->writeRow([
            'id',
            'name',
            'glasses',
            'age',
        ]);
        foreach ($incrementalData as $item) {
            $incrementalDataFile->writeRow($item);
        }

        $this->client->writeTable(
            sprintf('%s.sales', $this->bucketId),
            $incrementalDataFile,
            [
                'incremental' => true,
            ]
        );

        $writer = new Snowflake($this->client, $config);
        $writer->runAction();

        $dataAfterIncrementalWrite = $this->getConnection()->fetchAll('select * from "sales";');

        $dataDiff = array_values(
            array_filter($dataAfterIncrementalWrite, function ($item) use ($dataBeforeIncrementalWrite) {
                return !is_int(array_search($item['id'], array_column($dataBeforeIncrementalWrite, 'id')));
            })
        );

        Assert::assertCount(10, $dataBeforeIncrementalWrite);
        Assert::assertCount(12, $dataAfterIncrementalWrite);
        Assert::assertEquals($incrementalData, $dataDiff);
    }

    private function getConnection(): Connection
    {
        $dbParams = array_filter($this->workspace['connection'], function ($key): bool {
            return !in_array($key, ['backend']);
        }, ARRAY_FILTER_USE_KEY);

        return new Connection($dbParams);
    }
}
