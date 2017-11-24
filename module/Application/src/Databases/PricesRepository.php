<?php

/***********************************************
 * This class handles all connections to the local MySQL database
 * Prices table and settings table.
 *
 * There should be no other class which updates the table PRICES.
 *
 *************************************************/

namespace Application\Databases;


use Zend\Log\Logger;

class PricesRepository
{

    protected $debug;
    protected $debugImportLimit = 500;
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

    protected $crystalCommerceMapping;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/prices-repository.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct(Logger $logger, $debug = false)
    {
        $this->logger = $logger;

        $this->setConfig();
        $this->config['password'] = getenv('MYSQL_PASS');

        try {
            $this->conn = new \PDO("mysql:dbname=" . $this->config['defaultDb'] .
                ";host=" . $this->config['host'],
                $this->config['username'],
                $this->config['password']);
        } catch (\PDOException $e) {
            $this->logger->err("Error!: " . $e->getMessage() );
            exit();
        }

        if (!$this->checkPricesTable()) {
            throw new \Exception("Unable to create prices table.");
        }
        $this->checkSettingsTable();
        $this->debug = $debug;

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
     * @param int $daysFrequency default 1
     * @return array|bool false on failure, an associative array on success
     *********************************************/
    public function getRecordsWithPriceChanges($daysFrequency = 1)
    {
        $query = "SELECT product_name as 'Product Name', 
                category_name as 'Category', 
                sell_price as 'Sell Price' 
            FROM PRICES
            WHERE last_updated > DATE_SUB(now(), interval $daysFrequency day)
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
            $this->logger->info("No prices to update" );
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
                sell_price as 'Sell Price',
                PR.*                
            FROM PRICES as PR
            WHERE sell_price is NOT NULL
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
            $this->logger->info("No prices to update");
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
            $this->logger->info("Prices table doesn't exist, building now.");
            if ($this->buildPricesTable() == false) {
                $this->logger->err("Unable to prices table.");
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
            $this->logger->info("Settings table doesn't exist, building now.");
            if ($this->buildSettingsTable() == false) {
                $this->logger->err ("Unable to create REPRICER_SETTINGS table." );
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
            $this->logger->err(implode('\n', $statement->errorInfo()));
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
            amazon_title varchar(255),
            amazon_avg_new_price numeric(9,2),
            amazon_lowest_new_price numeric(9,2),
            amazon_avg_used_price numeric(9,2),
            amazon_avg_price numeric(9,2),
            amazon_buy_box_percentage numeric(9,8),
            amazon_buy_box_price numeric (9,2),
            amazon_num_new_offers integer,
            amazon_sales_rank integer,
            sellery_estimated_profit numeric(9,2),
            amazon_fees numeric(9,2),
            amazon_competition_price numeric(9,2),
            amazon_we_own_buy_box boolean,
            sellery_last_reprice_date timestamp,
            sellery_minimum_price numeric(9,2),
            sellery_minimum_ship numeric(9,2),
            sellery_minimum_price_plus_ship numeric(9,2),
            sellery_pricing_rule varchar(255),
            sellery_pricing_strategy varchar(255),
            sellery_shipping_carrier varchar(255),
            sellery_shipping_credit numeric(9,2),
            sellery_smartlist_name varchar(255),
            amazon_sales_per_day NUMERIC(9,2),
            amazon_sold_in_7 integer,
            amazon_sold_in_15 integer,
            amazon_sold_in_30 integer,
            amazon_sold_in_60 integer,
            amazon_sold_in_90 integer,
            amazon_sold_in_120 integer,
            amazon_sold_in_180 integer,
            sellery_cost numeric(9,2),
            sellery_cost_source varchar(255),
            sellery_days_of_stock integer,
            amazon_condition varchar(100),
            amazon_last_restock_date timestamp,
            amazon_buy_box_seller varchar(255),
            amazon_num_offers integer,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            unique key (product_name, category_name),
            unique key amazon_id (asin)
        );";
        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            $this->logger->err("PDO::errorInfo():");
            $this->logger->err(print_r($this->conn->errorInfo(), true) );
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
            $this->logger->info("Prices array is empty. Unable to import.");
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

            //$this->logger->debug($insertQuery . PHP_EOL);

            $stmt = $this->conn->prepare($insertQuery);

            $debugCounter = 0;

            foreach ($pricesArray as $priceLine) {
                foreach ($columnArray as $arrayIndex => $columnName) {
                    if (isset($priceLine[$arrayIndex])) {
                        $stmt->bindValue(':' . $columnName, $priceLine[$arrayIndex]);
                    }
                }
                if(!$stmt->execute()){
                    $this->logger->err(implode('\n', $stmt->errorInfo()) );
                    $warning = true;
                }
                $debugCounter++;
                if ($this->debug && $debugCounter > $this->debugImportLimit) {
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


