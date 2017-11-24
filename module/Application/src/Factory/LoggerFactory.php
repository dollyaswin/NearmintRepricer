<?php

namespace Application\Factory;

use Zend\Log\Filter\Priority;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class LoggerFactory
{

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
        $formatString = '%timestamp% %priorityName% (%priority%): %message%';

        if ($inBrowser) {
            $path = 'php://output';
            $formatter = new Simple($formatString . '<br>');
        } else {
            $path = __DIR__ . '/../../../../logs/' . $path;
            $path = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $path);
            $formatter = new Simple($formatString . PHP_EOL);
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

}