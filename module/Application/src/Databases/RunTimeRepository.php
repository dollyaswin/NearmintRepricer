<?php

namespace Application\Databases;

use Application\Databases;
use Zend\Log\Logger;

class RunTimeRepository extends Databases
{

    public function __construct(Logger $logger, bool $debug = false)
    {
        parent::__construct($logger, $debug);
        $this->checkLogTable();
    }

    public function getLastRunTime($scriptName)
    {
        $query = "SELECT * 
            FROM SCRIPT_RUN_LOG 
            WHERE script_name = '{$scriptName}'
            ORDER BY completion_time DESC 
            LIMIT 1
            ;";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        if ($statement->rowCount() == 0) {
            return false;
        }
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getRunInformation($scriptCount = 1)
    {
        $scriptCount = intval($scriptCount);

        $query = "SELECT * 
            FROM SCRIPT_RUN_LOG 
            ORDER BY completion_time DESC 
            LIMIT $scriptCount
            ;";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        if ($statement->rowCount() == 0) {
            return false;
        }
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }


    /*********************************************************
     * Add this script run instance to the log.
     *
     * Note: bindValue handles all escaping required, but can increase the string size beyond the
     * number of characters allowed in the field.
     *
     * @param string $scriptName
     * @param string $scriptResult
     * @param string $scriptErrorMessage
     * @param string $startTime - date('Y-m-d H:i:s') formatted date string
     * @return bool success or failure
     *********************************************************/
    public function logScriptRun($scriptName, $scriptResult, $scriptErrorMessage, $startTime)
    {
        $this->logger->debug("Inside " . __METHOD__ );
        $query = "INSERT INTO SCRIPT_RUN_LOG (script_name, script_result, script_error_message, start_time)  
            VALUES (:scriptName, :scriptResult, :scriptErrorMessage, :startTime);";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':scriptName', $scriptName);
        $statement->bindValue(':scriptResult', $scriptResult);
        $statement->bindValue(':scriptErrorMessage', $scriptErrorMessage);
        $statement->bindValue(':startTime', $startTime);

        if (!$statement->execute()) {
            $this->logger->err(implode('\n', $statement->errorInfo()));
            return false;
        }
        return true;
    }

    /**********************************************
     * Check if the script log table exists in the default database.
     * If not create it.
     *********************************************/
    protected function checkLogTable()
    {
        // test if table exists, if not then create table
        $result = $this->conn->query("SHOW TABLES LIKE 'SCRIPT_RUN_LOG';");
        if ($result->rowCount() == 0) {
            $this->logger->info("SCRIPT RUN LOG table doesn't exist, building now.");
            if ($this->buildLogTable() == false) {
                $this->logger->err ("Unable to create SCRIPT_RUN_LOG table." );
                exit();
            }
        }
    }


    protected function buildLogTable()
    {
        $createTableQuery = "CREATE TABLE SCRIPT_RUN_LOG (
            record_id integer NOT NULL AUTO_INCREMENT primary key,
            script_name varchar(255),
            script_result varchar(255),
            script_error_message varchar(1600),
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            completion_time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        $result = $this->conn->exec($createTableQuery);
        if ($result === false) {
            return false;
        }
        return true;
    }


}