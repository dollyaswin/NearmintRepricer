<?php

namespace Application;

use Zend\Log\Logger;

abstract class Databases
{
    protected $debug;
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
        } catch (\PDOException $e) {
            $this->logger->err("Error!: " . $e->getMessage() );
            exit();
        }


    }

}
