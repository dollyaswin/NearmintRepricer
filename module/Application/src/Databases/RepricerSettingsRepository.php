<?php

namespace Application\Databases;

use Application\Databases;

class RepricerSettingsRepository extends Databases
{
    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/repricer-settings-repository.config.php');
        return array_merge($parent, $child);
    }

    /***************************************
     * The sellery Repricing engine can be configured to create a new report on a daily schedule.
     * These reports all have the same URL except for their key which is a simple auto incremented number.
     *
     * This method gets the last known number for use in searching for the most recent download.
     * @param string $settingName
     * @return bool|integer false on failure, or the last known download number on success.
     */
    public function getSetting($settingName)
    {
        $query = "SELECT setting_value FROM {$this->config['table name']} WHERE setting_name = '$settingName';";
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
     * @param string $settingName
     * @param integer $value
     * @return bool success or failure
     */
    public function setSetting($settingName, $value)
    {
        $this->logger->debug("Inside " . __METHOD__ );
        $query = "REPLACE INTO {$this->config['table name']} (setting_value, setting_name)  
            VALUES (:value, :settingName);";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':value', $value);
        $statement->bindValue(':settingName', $settingName);
        if (!$statement->execute()) {
            $this->logger->err(implode('\n', $statement->errorInfo()));
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

}