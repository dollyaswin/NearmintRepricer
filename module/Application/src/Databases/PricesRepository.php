<?php

namespace Application\Databases;


class PricesRepository
{

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

        $this->checkPricesTable();
        $this->checkSettingsTable();

        $this->crystalCommerceMapping = $this->config['crystalCommerceMapping'];
    }

    protected function checkPricesTable()
    {
        // test if table exists, if not then create table
        $result = $this->conn->query("SHOW TABLES LIKE 'PRICES';");
        if ($result->rowCount() == 0) {
            print ("Prices table doesn't exist, building now." . PHP_EOL);
            if ($this->buildSettingsTable() == false) {
                print ("Unable to prices table." . PHP_EOL);
                exit();
            }
        }
    }

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
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            unique key (product_name, category_name),
            key amazon_id (asin)
        );";
        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            return false;
        }
        return true;
    }

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

    public function importPricesFromCC($pricesArray)
    {
        $warning = false;
        if(count($pricesArray) < 1) {
            print ("Prices array is empty. Unable to import.");
            return false;
        }

        $columnArray = [];
        $headerRow = array_keys(array_values($pricesArray)[0]);
        foreach ($headerRow as $headerName) {
            if (isset($this->crystalCommerceMapping[$headerName])) {
                $columnArray[$headerName] = $this->crystalCommerceMapping[$headerName];
            }
        }

        try {
            // Build query with :name in place of each variable.
            $insertQuery = "INSERT INTO PRICES (" . implode(',', $columnArray) . " ) VALUES ( ";
            $duplicateKeyClause = '';
            foreach ($columnArray as $columnName) {
                $insertQuery .= "\n :$columnName,";
                $duplicateKeyClause .= "\n $columnName=$columnName,";
            }
            $insertQuery = trim($insertQuery,','); // trim off last comma
            $duplicateKeyClause = trim($duplicateKeyClause,','); // trim off last comma

            $insertQuery .= " ) ON DUPLICATE KEY UPDATE $duplicateKeyClause ;";

            //print($insertQuery . PHP_EOL);

            $stmt = $this->conn->prepare($insertQuery);

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




}


