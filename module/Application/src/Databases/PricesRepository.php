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

        $this->debug = $debug;
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
     * @param bool $quickUploadOnly
     * @param bool|int $daysLimit
     * @param bool $changesOnly - restrict results to only records which need updated.
     *
     * @return array|bool false on failure, an associative array on success
     *********************************************/
    public function getRecordsWithPriceChanges($dropDownParameters, $quickUploadOnly, $daysLimit, $changesOnly)
    {
        $timeLimitClause = '';
        if ($daysLimit) {
            $timeLimitClause = " AND SE.last_updated > DATE_SUB(now(), interval $daysLimit day)";
        }

        $selectClause = "SELECT * ";
        if ($quickUploadOnly) {
            $selectClause = "SELECT product_name as 'Product Name', 
                category_name as 'Category' ";
        }

        /**********************************************************
         * This is the heart of the repricing calculation. The logic for what price is uploaded to CC
         * is determined in this clause of the query.  Replace or modify here and update the below comment
         *
         * Use the sell_price first if available, this comes from 'Live price on Near Mint Games' from sellery
         * if it is not available, average the follow three prices together
         * amazon_avg_new_price, amazon_lowest_new_price, amazon_buy_box_price
         * In MySQL if one is NULL then the result would be null, COALESCE is used
         *     But because that could throw off the number of prices that exist, the amount you divide by must be adjusted.
         *     So normally you would add three values and divide by three. Instead count up how much to divide by.
         **********************************************************/
        $selectClause .= ",
        format(
            COALESCE(SE.sellery_sell_price, 
                (COALESCE(SE.amazon_avg_new_price, 0) + COALESCE(SE.amazon_lowest_new_price, 0) + COALESCE(SE.amazon_buy_box_price, 0) ) /
                    (1 + CASE WHEN SE.amazon_lowest_new_price IS NOT NULL THEN 1 ELSE 0 END +
                    CASE WHEN SE.amazon_buy_box_price IS NOT NULL THEN 1 ELSE 0 END )
            ), 2
        )  as 'Sell Price'";


        $query = "$selectClause
            FROM crystal_commerce as CC 
            INNER JOIN sellery as SE on (SE.asin=CC.asin)
            WHERE (SE.amazon_avg_new_price IS NOT NULL OR SE.sellery_sell_price IS NOT NULL) 
            AND CC.product_name is NOT NULL
            $timeLimitClause
        ";

        if ($changesOnly) {
            $query .= ' AND (   
                (ABS(CC.cc_sell_price - SE.sellery_sell_price) > CC.cc_sell_price*0.02
                AND ABS(CC.cc_sell_price - SE.sellery_sell_price) > 0.05)
             OR 
                 SE.sellery_sell_price IS NULL    
                 ) ';
        }

        if (!empty($dropDownParameters)) {
            foreach ($dropDownParameters as $column => $value) {
                $query .= " AND $column = '$value' ";
            }
        }

        if ($this->debug) {
            $query .= "LIMIT 10";
        }
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $this->logger->info("The query : $query");

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
     * @param bool $restrictFileSize
     *
     * @return array|bool false on failure, an associative array on success
     *********************************************/
    public function getAllPriceRecords($restrictFileSize = false)
    {

        $query = "SELECT product_name as 'Product Name', 
                category_name as 'Category', 
                sell_price as 'Sell Price',
                PR.*                
            FROM PRICES as PR
            WHERE sell_price is NOT NULL
            AND product_name is NOT NULL 
        ";

        if ($restrictFileSize) {
            $query .= ' AND ABS(cc_sell_price - sell_price) > cc_sell_price*0.02
                AND ABS(cc_sell_price - sell_price) > 0.05';
        }

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

    public function getOptionsForColumn($columnName)
    {
        list($table, $column) = explode('.', $columnName);
        $query = "SELECT $column as option_name, count(*) as the_count
            FROM $table 
            GROUP BY $column
            ORDER BY the_count DESC;";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            $this->logger->info("Column information not available for $columnName");
            return false;
        }
        return $result;
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
    private function convertAssociateiveArrayToCsvString($dataArray)
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


