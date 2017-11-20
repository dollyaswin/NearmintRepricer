<?php

/***********************************************
 * This class handles all connections to the local MySQL database
 * Prices table and settings table.
 *
 * There should be no other class which updates the table PRICES.
 *
 *************************************************/

namespace Application\Databases;


class PricesRepository
{

    protected $debug = false;
    protected $debugImportLimit = 500;

    /**
     * @var \PDO
     */
    protected $conn;
    /**
     * @var array
     */
    protected $config;

    protected $crystalCommerceMapping;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/prices-repository.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct()
    {
        $this->setConfig();
        $this->config['password'] = getenv('MYSQL_PASS');

        try {
            $this->conn = new \PDO("mysql:dbname=" . $this->config['defaultDb'] .
                ";host=" . $this->config['host'],
                $this->config['username'],
                $this->config['password']);
        } catch (\PDOException $e) {
            print "Error!: " . $e->getMessage() . PHP_EOL;
            exit();
        }

        if (!$this->checkPricesTable()) {
            throw new \Exception("Unable to create prices table.");
        }
        $this->checkSettingsTable();

        $this->crystalCommerceMapping = $this->config['crystalCommerceMapping'];
        $this->selleryMapping = $this->config['selleryMapping'];
    }


    /*********************************************
     * Get Prices updated in the last # hours
     *
     * The aliases for the columns in this query are very important.  They must match the
     * expected column names for uploading into Crystal Commerce.
     *
     * IF THIS FUNCTION IS USED FOR ANOTHER SERVICE, you must leave the column aliases alone
     * or introduce a mapping for the crystal commerce update.
     *
     * @param int $hoursFrequency default 2
     * @return array|bool false on failure, an associative array on success
     *********************************************/
    public function getRecordsWithPriceChanges($hoursFrequency = 2)
    {
        $query = "SELECT product_name as 'Product Name', 
                category_name as 'Category', 
                sell_price as 'Sell Price' 
            FROM PRICES
            WHERE last_updated > DATE_SUB(now(), interval $hoursFrequency hour)
            AND sell_price is NOT NULL
            AND product_name is NOT NULL
            ORDER BY last_updated DESC
        ";
        if ($this->debug) {
            $query .= "LIMIT 10";
        }
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) == 0) {
            print "No prices to update" . PHP_EOL;
            return false;
        }
        return $result;
    }


    /*********************************************
     * Get All Records
     *
     * The aliases for the columns in this query are very important.  They must match the
     * expected column names for uploading into Crystal Commerce.
     *
     * IF THIS FUNCTION IS USED FOR ANOTHER SERVICE, you must leave the column aliases alone
     * or introduce a mapping for the crystal commerce update.
     *
     * @param int $hoursFrequency default 2
     * @return array|bool false on failure, an associative array on success
     *********************************************/
    public function getAllPriceRecords()
    {
        $query = "SELECT product_name as 'Product Name', 
                category_name as 'Category', 
                sell_price as 'Sell Price' 
            FROM PRICES
            WHERE last_updated > DATE_SUB(now(), interval $hoursFrequency hour)
            AND sell_price is NOT NULL
            AND product_name is NOT NULL
            ORDER BY last_updated DESC
        ";
        if ($this->debug) {
            $query .= "LIMIT 10";
        }
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) == 0) {
            print "No prices to update" . PHP_EOL;
            return false;
        }
        return $result;
    }


    /**********************************
     *  Check if the prices table exists in the default database
     *  if not create it.
     *
     *  Ensures that after this function is run, there is a prices table.
     * @return bool success or failure
     */
    protected function checkPricesTable()
    {
        // test if table exists, if not then create table
        $result = $this->conn->query("SHOW TABLES LIKE 'PRICES';");
        if ($result->rowCount() == 0) {
            print ("Prices table doesn't exist, building now." . PHP_EOL);
            if ($this->buildPricesTable() == false) {
                print ("Unable to prices table." . PHP_EOL);
                return false;
            }
        }
        return true;
    }

    /**********************************************
     * Check if the settings table exists in the default database.
     * If not create it.
     *********************************************/
    protected function checkSettingsTable()
    {
        // test if table exists, if not then create table
        $result = $this->conn->query("SHOW TABLES LIKE 'REPRICER_SETTINGS';");
        if ($result->rowCount() == 0) {
            print ("Settings table doesn't exist, building now." . PHP_EOL);
            if ($this->buildSettingsTable() == false) {
                print ("Unable to create REPRICER_SETTINGS table." . PHP_EOL);
                exit();
            }
        }
    }


    /***************************************
     * The sellery Repricing engine can be configured to create a new report on a daily schedule.
     * These reports all have the same URL except for their key which is a simple auto incremented number.
     *
     * This method gets the last known number for use in searching for the most recent download.
     *
     * @return bool|integer false on failure, or the last known download number on success.
     */
    public function getMostRecentSelleryDownloadNumber()
    {
        $query = "SELECT setting_value FROM REPRICER_SETTINGS WHERE setting_name = 'sellery_download_number';";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        if ($statement->rowCount() == 0) {
            return false;
        }
        $result = $statement->fetch(\PDO::FETCH_BOTH);
        return $result['setting_value'];
    }

    /*********************************
     * This method updates the database after the latest download number has been found
     *
     * @param integer $downloadNumber
     * @return bool success or failure
     */
    public function setMostRecentSelleryDownloadNumber($downloadNumber)
    {
        $query = "REPLACE INTO REPRICER_SETTINGS (setting_value, setting_name)  
            VALUES (:downloadNumber, 'sellery_download_number');";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':downloadNumber', $downloadNumber);
        if (!$statement->execute()) {
            print(implode('\n', $statement->errorInfo()) . PHP_EOL);
            return false;
        }
        return true;
    }


    /****************************************
     * Creates prices table.  Contains table definition.
     *
     * @return bool success or failure
     ******************************/
    private function buildPricesTable()
    {
        $createTableQuery = "CREATE TABLE PRICES (
            record_id integer NOT NULL AUTO_INCREMENT primary key,
            product_name varchar(255),
            category_name varchar(255),
            total_qty integer NOT NULL DEFAULT 0,
            wishlists integer,
            buy_price numeric(9,2),
            sell_price numeric(9,2),
            product_url varchar(1000),
            barcode varchar(255),
            manufacturer_sku varchar(255),
            asin varchar(25),
            msrp numeric(9,2),
            brand varchar(255),
            weight numeric(8,4),
            description varchar(4000),
            max_quantity integer,
            domestic_only boolean,
            tax_exempt boolean,
            sellery_sku varchar(255),
            amazon_title varchar(255),
            sellery_cost numeric(9,2),
            amazon_avg_price numeric(9,2),
            amazon_num_offers integer,
            amazon_sales_rank integer,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            unique key (product_name, category_name),
            unique key amazon_id (asin)
        )_;";
        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            return false;
        }
        return true;
    }

    /****************************
     * Creates settings table. Contains table definition
     *
     * @return bool success or failure
     */
    private function buildSettingsTable()
    {
        $createTableQuery = "CREATE TABLE REPRICER_SETTINGS (
            record_id integer NOT NULL AUTO_INCREMENT primary key,
            setting_name varchar(255),
            setting_value varchar(255),
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            unique key (setting_name)
        );";
        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            return false;
        }
        return true;
    }

    /******************************************************
     * This is a wrapper functions for  importPrices()
     * which add the mapping.
     *
     * @param array $pricesArray - and array of records, which are arrays with keys which match the mapping keys
     * @return bool success or failure.
     **************************************/
    public function importPricesFromSellery($pricesArray)
    {
        return $this->importPrices($pricesArray, $this->selleryMapping);
    }

    /******************************************************
     * This is a wrapper functions for  importPrices()
     * which add the mapping.
     *
     * @param array $pricesArray - and array of records, which are arrays with keys which match the mapping keys
     * @return bool success or failure.
     **************************************/
    public function importPricesFromCC($pricesArray)
    {
        return $this->importPrices($pricesArray, $this->crystalCommerceMapping);
    }

    /****************************************
     * Import / Update records from in the PRICES table
     * Using an array of values and a mapping array of array keys to column names.
     *
     * If the record matches on any of the unique or primary keys it will update the record instead.
     *
     * There is a last updated field, which will be set to the current timestamp
     * If all the data matches, the last updated field will not be updated.
     *
     * @param array $pricesArray - and array of records, which are arrays with keys which match the mapping keys
     * @param array $dataMapping - key match the keys from the pricesArray and values match the columns in the Prices table.
     * @return bool success or failure.
     *************************************/
    protected function importPrices($pricesArray, $dataMapping)
    {
        $warning = false;
        if(count($pricesArray) < 1) {
            print ("Prices array is empty. Unable to import.");
            return false;
        }

        $columnArray = [];
        $headerRow = array_keys(array_values($pricesArray)[0]);
        foreach ($headerRow as $headerName) {
            if (isset($dataMapping[$headerName])) {
                $columnArray[$headerName] = $dataMapping[$headerName];
            }
        }

        try {
            // Build query with :name in place of each variable.
            $insertQuery = "INSERT INTO PRICES (" . implode(',', $columnArray) . " ) VALUES ( ";
            $duplicateKeyClause = '';
            foreach ($columnArray as $columnName) {
                $insertQuery .= "\n :$columnName,";
                $duplicateKeyClause .= "\n $columnName=VALUES($columnName),";
            }
            $insertQuery = trim($insertQuery,','); // trim off last comma
            $duplicateKeyClause = trim($duplicateKeyClause,','); // trim off last comma

            $insertQuery .= " ) ON DUPLICATE KEY UPDATE $duplicateKeyClause ;";

            //print($insertQuery . PHP_EOL);

            $stmt = $this->conn->prepare($insertQuery);

            $debugCounter = 0;

            foreach ($pricesArray as $priceLine) {
                foreach ($columnArray as $arrayIndex => $columnName) {
                    if (isset($priceLine[$arrayIndex])) {
                        $stmt->bindValue(':' . $columnName, $priceLine[$arrayIndex]);
                    }
                }
                if(!$stmt->execute()){
                    print(implode('\n', $stmt->errorInfo()) . PHP_EOL);
                    $warning = true;
                }
                $debugCounter++;
                if ($this->debug && $debugCounter > $this->debugImportLimit) {
                    print ("There have been $debugCounter prices imported. Stopping" . PHP_EOL);
                    break;
                }

            }
        } catch (\PDOException $e) {
            print "Error!: " . $e->getMessage() . PHP_EOL;
            return false;
        }
        if ($warning) {
            return false;
        }
        return true;
    }


    /*********************************************
     * Convert an array into a CSV formatted string with it's keys as the
     * header row of the CSV file.
     *
     * @param array $dataArray - associative array
     *
     *
     * @return bool|string false on failure, a string on success
     **********************************************/
    public function convertAssociateiveArrayToCsvString($dataArray)
    {
        if(empty($dataArray)) {
            return false;
        }

        $fp = tmpfile();
        $firstRow = array_shift($dataArray);
        array_unshift($dataArray, $firstRow);

        $headers = array_keys($firstRow);
        fputcsv($fp,$headers);
        foreach ($dataArray as $row) {
            fputcsv($fp,$row);
        }
        // Reset the pointer and read back the data.
        fseek($fp,0);

        $dataString = '';
        while($line = fread($fp,8192)) {
            $dataString .= $line;
        }
        return $dataString;
    }



}


