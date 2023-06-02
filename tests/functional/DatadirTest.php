<?php

declare(strict_types=1);

namespace Keboola\DbWriter\SnowflakeWorkspace\FunctionalTests;

use Keboola\Csv\CsvFile;
use Keboola\DatadirTests\DatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\DatadirTests\DatadirTestsProviderInterface;
use Keboola\SnowflakeDbAdapter\Connection;
use Keboola\SnowflakeDbAdapter\QueryBuilder;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Workspaces;
use Symfony\Component\Filesystem\Filesystem;

class DatadirTest extends DatadirTestCase
{
    private Client $client;

    private array $workspace;

    private ?Connection $connection = null;

    private ?string $bucketId = null;

    private const BUCKET_NAME = 'wr-snowflake-workspace';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client([
            'url' => getenv('KBC_URL'),
            'token' => getenv('KBC_TOKEN'),
        ]);
        $workspaces = new Workspaces($this->client);
        $this->workspace = $workspaces->createWorkspace(['backend' => 'snowflake']);
    }

    protected function tearDown(): void
    {
        // cleanup workspace
        $workspaces = new Workspaces($this->client);
        $workspaces->deleteWorkspace($this->workspace['id']);

        // cleanup tables and bucket
        if ($this->bucketId) {
            foreach ($this->client->listTables($this->bucketId) as $table) {
                $this->client->dropTable($table['id'], ['force' => 'force']);
            }
            $this->client->dropBucket($this->bucketId, ['force' => 'force']);
        }
    }

    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);
        $this->createBucketAndTable($tempDatadir->getTmpFolder());
        $this->replacePartOfConfig($tempDatadir->getTmpFolder());

        $process = $this->runScript($tempDatadir->getTmpFolder());

        // Dump database data & create statement after running the script
        $this->dumpAllTables($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $this->cleanupTables();
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

    private function dumpAllTables(string $tempDatadir): void
    {
        // Create output dir
        $dumpDir = $tempDatadir . '/out/db-dump';
        $fs = new Filesystem();
        $fs->mkdir($dumpDir, 0777);

        $tables = $this->getConnection()->fetchAll(
            sprintf(
                'select * from "INFORMATION_SCHEMA"."TABLES" where "TABLE_SCHEMA" = %s;',
                QueryBuilder::quote($this->workspace['connection']['schema'])
            )
        );

        foreach ($tables as $table) {
            $csv = new CsvFile(sprintf('%s/%s.csv', $dumpDir, $table['TABLE_NAME']));

            $data = $this->getConnection()->fetchAll(
                sprintf(
                    'select * from %s.%s',
                    QueryBuilder::quoteIdentifier($table['TABLE_SCHEMA']),
                    QueryBuilder::quoteIdentifier($table['TABLE_NAME'])
                )
            );

            // write header
            $columns = array_keys(current($data));
            $csv->writeRow($columns);

            //write all data from snflk table
            foreach ($data as $item) {
                $csv->writeRow($item);
            }
        }
    }

    private function replacePartOfConfig(string $tempDataDir): void
    {
        $configFile = $tempDataDir . '/config.json';
        $config = json_decode((string) file_get_contents($configFile), true);
        $config['parameters'] = array_merge(
            $config['parameters'],
            [
                'workspaceId' => (string) $this->workspace['id'],
            ]
        );
        if (isset($config['parameters']['dbName'])) {
            $config['parameters']['tableId'] = sprintf(
                '%s.%s',
                $this->bucketId,
                $config['parameters']['dbName']
            );
        }
        file_put_contents($configFile, json_encode($config));
    }

    private function cleanupTables(): void
    {
        $tables = $this->getConnection()->fetchAll(
            sprintf(
                'select * from "INFORMATION_SCHEMA"."TABLES" where "TABLE_SCHEMA" = %s;',
                QueryBuilder::quote($this->workspace['connection']['schema'])
            )
        );

        foreach ($tables as $table) {
            $this->getConnection()->query(
                sprintf(
                    'DROP TABLE IF EXISTS %s.%s',
                    QueryBuilder::quoteIdentifier($table['TABLE_SCHEMA']),
                    QueryBuilder::quoteIdentifier($table['TABLE_NAME'])
                )
            );
        }
    }

    private function getConnection(): Connection
    {
        if (!$this->connection) {
            $dbParams = array_filter($this->workspace['connection'], function ($key): bool {
                return !in_array($key, ['backend']);
            }, ARRAY_FILTER_USE_KEY);
            $this->connection = new Connection($dbParams);
        }
        return $this->connection;
    }

    private function createBucketAndTable(string $tempDatadir): void
    {
        $configFile = $tempDatadir . '/config.json';
        $config = json_decode((string) file_get_contents($configFile), true);

        if (isset($config['parameters']['dbName'])) {
            $bucket = $this->client->getBucketId('c-' . self::BUCKET_NAME, 'in');
            if (!$bucket) {
                $bucket = $this->client->createBucket(self::BUCKET_NAME, 'in');
            }
            $this->bucketId = (string) $bucket;

            $csvFile = new CsvFile(sprintf(
                '%s/in/tables/%s.csv',
                $tempDatadir,
                $config['parameters']['dbName']
            ));

            $this->client->createTable($this->bucketId, $config['parameters']['dbName'], $csvFile);
        }
    }
}
