<?php

namespace Application\Factory;

use Zend\Log\Filter\Priority;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class LoggerFactory
{

    static protected $formatString = '%timestamp% %priorityName% : %message%';

    public function __invoke($path = '', $inBrowser = true, $debug = true)
    {
        return LoggerFactory::createLogger($path, $inBrowser, $debug);
    }

    /**
     * @param string $path
     * @param bool $inBrowser
     * @param bool $debug
     * @return Logger
     */
    static public function createLogger($path = '', $inBrowser = true, $debug = true)
    {

        if ($inBrowser) {
            $path = 'php://output';
            $formatter = new Simple(self::$formatString . '<br>');
        } else {
            $path = __DIR__ . '/../../../../logs/' . $path;
            $path = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $path);
            $formatter = new Simple(self::$formatString);
        }

        $formatter->setDateTimeFormat('Y-m-d H:i:s');

        $writer = new Stream($path);
        $writer->setFormatter($formatter);

        $messageLevel = Logger::INFO;
        if ($debug) {
            $messageLevel = Logger::DEBUG;
        }
        $filter = new Priority($messageLevel);
        $writer->addFilter($filter);

        $logger =  new Logger();
        $logger->addWriter($writer);
        // Log PHP errors
        Logger::registerErrorHandler($logger);

        // Log exceptions
        Logger::registerExceptionHandler($logger);
        return $logger;
    }


    /************************************************
     * This is used to add to a logger to create temporary log files
     * The log file is always set to log all debug statements
     *
     * @param Logger $logger
     * @param string $path
     *
     * @return Logger
     ************************************************/
    static public function addWriterToLogger(Logger $logger, $path)
    {
        $formatter = new Simple(self::$formatString);
        $formatter->setDateTimeFormat('Y-m-d H:i:s');

        $writer = new Stream($path);
        $writer->setFormatter($formatter);

        $messageLevel = Logger::DEBUG;
        $filter = new Priority($messageLevel);
        $writer->addFilter($filter);

        $logger->addWriter($writer);

        return $logger;
    }

}