<?php

namespace Application;

use Zend\Log\Logger;

abstract class Databases
{
    protected $debug;
    protected $debugImportLimit = 100;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \PDO
     */
    protected $conn;
    /**
     * @var array
     */
    protected $config;

    protected $tableName;

    public function getConfig()
    {
        return include(__DIR__ . '/../config/databases.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct(Logger $logger, $debug = false)
    {
        $this->debug = $debug;
        $this->logger = $logger;
        $this->setConfig();
        $this->config['password'] = getenv('MYSQL_PASS');

        try {
            $this->conn = new \PDO("mysql:dbname=" . $this->config['defaultDb'] .
                ";host=" . $this->config['host'],
                $this->config['username'],
                $this->config['password']);
            $this->checkTable();
        } catch (\PDOException $e) {
            $this->logger->err("Error!: " . $e->getMessage() );
            exit();
        } catch (\Exception $e) {
            $this->logger->err("Error!: " . $e->getMessage() );
            exit();
        }
    }

    /**********************************
     *  Check if the prices table exists in the default database
     *  if not create it.
     *
     *  Ensures that after this function is run, there is a prices table.
     * @throws \Exception when unable to build table
     */
    protected function checkTable()
    {
        // test if table exists, if not then create table
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->config['table name']}';");
        if ($result->rowCount() == 0) {
            $this->logger->info("Table '{$this->config['table name']}' doesn't exist, building now.");
            if ($this->rebuildTableFromDefinition() == false) {
                throw new \Exception("Unable to create '{$this->config['table name']}' table.");
            }
        }
        return true;
    }

    protected function rebuildTableFromDefinition()
    {
        $createTableQuery = $this->buildCreateTableQuery();

        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            $this->logger->err("Create table Query : $createTableQuery");
            $this->logger->err("PDO::errorInfo():");
            $this->logger->err(print_r($this->conn->errorInfo(), true) );
            return false;
        }
        return true;
    }

    protected function buildCreateTableQuery()
    {
        $tableName = "CREATE TABLE {$this->config['table name']} (";

        $columnsSection = '';
        foreach ($this->config['columns'] as $name => $columnData) {
            $columnsSection .= $name . ' ' . $columnData['definition']  . ",\n";
        }

        $keys = '';
        if (isset($this->config['primary key']) || isset($this->config['unique keys'])) {
            if (isset($this->config['primary key'])) {
                $keys .= 'PRIMARY KEY (' . $this->config['primary key'] . "),\n";
            }
            if (isset($this->config['unique keys'])) {
                foreach ($this->config['unique keys'] as $name => $value) {
                    $keys .= "UNIQUE KEY $name ( $value),";
                }
                $keys = trim ($keys, ',');
            }
        } else {
            $columnsSection = trim($columnsSection, ",\n");
        }
        $engine = ') ENGINE=InnoDB DEFAULT CHARSET=latin1;';

        return $tableName . $columnsSection . $keys . $engine;
    }

    /****************************************
     * Import / Update records into the table for the extending class
     * Using an array of values and a mapping array of array keys to column names.
     *
     * If the record matches on any of the unique or primary keys it will update the record instead.
     *
     * There is a last updated field, which will be set to the current timestamp
     * If all the data matches, the last updated field will not be updated.
     *
     * @param array $pricesArray - and array of records, which are arrays with keys which match the mapping keys
     * @return bool success or failure.
     *************************************/
    public function importFromArray($pricesArray)
    {
        $warning = false;
        if(count($pricesArray) < 1) {
            $this->logger->info("Prices array is empty. Unable to import.");
            return false;
        }

        $dataMapping = $this->buildDataMapping();

        $columnArray = [];
        $headerRow = array_keys(array_values($pricesArray)[0]);
        foreach ($headerRow as $headerName) {
            if (isset($dataMapping[$headerName])) {
                $columnArray[$headerName] = $dataMapping[$headerName];
            }
        }

        $typeData = $this->getTypeInformation($this->config['table name']);

        try {
            // Build query with :name in place of each variable.
            $insertQuery = "INSERT INTO {$this->config['table name']} (" . implode(',', $columnArray) . " ) VALUES ( ";
            $duplicateKeyClause = '';
            foreach ($columnArray as $columnName) {
                $insertQuery .= "\n :$columnName,";
                $duplicateKeyClause .= "\n $columnName=VALUES($columnName),";
            }
            $insertQuery = trim($insertQuery,','); // trim off last comma
            $duplicateKeyClause = trim($duplicateKeyClause,','); // trim off last comma

            $insertQuery .= " ) ON DUPLICATE KEY UPDATE $duplicateKeyClause ;";

            //$this->logger->debug($insertQuery . PHP_EOL);

            $stmt = $this->conn->prepare($insertQuery);

            $debugCounter = 0;

            foreach ($pricesArray as $priceLine) {
                foreach ($columnArray as $arrayIndex => $columnName) {
                    if (isset($priceLine[$arrayIndex])) {
                        $cleanValue = $this->cleanValue($priceLine[$arrayIndex], $typeData[$columnName]);
                        $stmt->bindValue(':' . $columnName, $cleanValue);
                    } else {
                        $stmt->bindValue(':' . $columnName, null);
                    }
                }
                if(!$stmt->execute()){
                    $this->logger->err(implode('\n', $stmt->errorInfo()) );
                    $warning = true;
                }
                $debugCounter++;
                if ($this->debug && $debugCounter >= $this->debugImportLimit) {
                    $this->logger->info ("There have been $debugCounter prices imported. Stopping" );
                    break;
                }

            }
        } catch (\PDOException $e) {
            $this->logger->err("Error!: " . $e->getMessage());
            return false;
        }
        if ($warning) {
            return false;
        }
        return true;
    }

    /**
     * Build Data Mapping Array based on config array 'columns'
     *
     * @return array - an array keys on the mapping name, with value of the column name it maps to.
     */
    protected function buildDataMapping()
    {
        $dataMapping = [];
        foreach ($this->config['columns'] as $columnName => $columnData) {
            if (isset($columnData['mapping'])) {
                $dataMapping[$columnData['mapping']] = $columnName;
            }
        }
        return $dataMapping;
    }


    private function cleanValue($value, $mysqlInfoArray)
    {
        if ($value === '') {
            return null;
        }
        switch ($mysqlInfoArray['DATA_TYPE']) {
            case 'tinyint':
                if ($value) {
                    return 1;
                }
                return 0;
            case 'decimal':
                return trim ($value,'$');
            case 'varchar':
                if ($mysqlInfoArray['CHARACTER_MAXIMUM_LENGTH'] < strlen($value)) {
                    return substr($value, $mysqlInfoArray['CHARACTER_MAXIMUM_LENGTH']);
                }
                return $value;
            default:
                return $value;
        }
    }

    private function getTypeInformation($tableName)
    {
        $returnArray = [];

        $query = "SELECT * FROM information_schema.columns WHERE table_name='$tableName' AND table_schema = '{$this->config['defaultDb']}';";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($result as $data) {
            $returnArray[$data['COLUMN_NAME']] = $data;
        }
        return $returnArray;

    }


}
