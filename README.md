# Snowflake writer
[![Build Status](https://travis-ci.com/keboola/wr-snowflake-workspace.svg?branch=master)](https://travis-ci.org/keboola/db-writer-snowflake)

> Writes data from Keboola Connection Storage to Snowflake database

# Example configurations

#### Simple write:
```json
{
  "parameters": {
    "workspaceId": "xyz",
    "tableId": "in.bucket.table",
    "dbName": "exampleTable",
    "primaryKey": [
      "id"
    ],
    "items": [
      {
        "name": "id",
        "dbName": "id",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "name",
        "dbName": "name",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "glasses",
        "dbName": "glasses",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "age",
        "dbName": "age",
        "type": "varchar",
        "size": "10",
        "nullable": false,
        "default": ""
      }
    ]
  }
}
```

#### Incremental write:

for using incremental write, you must have defined primary key on the table

```json
{
  "parameters": {
    "workspaceId": "xyz",
    "tableId": "in.bucket.table",
    "dbName": "exampleTable",
    "incremental": true,
    "primaryKey": [
      "id"
    ],
    "items": [
      {
        "name": "id",
        "dbName": "id",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "name",
        "dbName": "name",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "glasses",
        "dbName": "glasses",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "age",
        "dbName": "age",
        "type": "varchar",
        "size": "10",
        "nullable": false,
        "default": ""
      }
    ]
  }
}
```

### Filtering data
```json
{
  "parameters": {
    "workspaceId": "xyz",
    "tableId": "in.bucket.table",
    "dbName": "exampleTable",
    "items": [
      {
        "name": "id",
        "dbName": "id",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "name",
        "dbName": "name",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "glasses",
        "dbName": "glasses",
        "type": "varchar",
        "size": "255",
        "nullable": false,
        "default": ""
      },
      {
        "name": "age",
        "dbName": "age",
        "type": "varchar",
        "size": "10",
        "nullable": false,
        "default": ""
      }
    ]
  },
  "storage": {
    "input": {
      "tables": [
        {
          "where_column": "glasses",
          "where_values": [
            "yes"
          ],
          "where_operator": "eq"
        }
      ]
    }
  }
}

```

## Development

Clone this repository and init the workspace with following command::

```
git clone https://github.com/keboola/wr-snowflake-workspace
cd wr-snowflake-workspace
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Create `.env` file:

```dotenv
KBC_URL=
KBC_TOKEN=
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
