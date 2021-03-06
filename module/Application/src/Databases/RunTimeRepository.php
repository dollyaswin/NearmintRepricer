<?php

namespace Application\Databases;

use Application\Databases;
use Zend\Log\Logger;

class RunTimeRepository extends Databases
{
    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/run-time-repository.config.php');
        return array_merge($parent, $child);
    }

    public function getRecentRunTimeInformation($scriptId, $limit = 20)
    {
        $limit = intval($limit);
        $scriptId = intval($scriptId);

        $query = "SELECT * 
            FROM SCRIPT_RUN_LOG 
            WHERE script_id = $scriptId
            ORDER BY completion_time DESC 
            LIMIT $limit";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        if ($statement->rowCount() == 0) {
            return false;
        }
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
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

    public function getLastRunInformation()
    {
        $query = "SELECT newest.*
            FROM SCRIPT_RUN_LOG as newest
            LEFT JOIN SCRIPT_RUN_LOG as newer on (newer.script_id=newest.script_id AND newest.start_time < newer.start_time)
            WHERE newer.script_id IS NULL
            AND newest.start_time > DATE_SUB(CURRENT_TIME, interval 30 day)
            ORDER BY completion_time DESC ;";
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
        // Lookup ScriptId default to 1, ?? is coalesce operator
        $scriptId = $this->config['scriptList'][$scriptName] ?? 1;

        $this->logger->debug("Inside " . __METHOD__ );
        $query = "INSERT INTO SCRIPT_RUN_LOG (script_id, script_name, script_result, script_error_message, start_time)  
            VALUES (:scriptId, :scriptName, :scriptResult, :scriptErrorMessage, :startTime);";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':scriptId', $scriptId);
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

}