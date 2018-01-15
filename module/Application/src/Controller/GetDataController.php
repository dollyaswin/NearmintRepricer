<?php

/***************************************
 * This is the Controller, and where most of the logic should live.
 *
 * Public methods in this class which end in Action can be called by loading the URL
 * localhost/application/converted-method-name   for method convertedMethodName()
 *
 * It is expected the user will first
 * getCrystalCommerceDataAction() and getSelleryPricingAction() in any order
 * and then run updateCrystalCommercePricesAction()
 *
 ******************************************/


namespace Application\Controller;

use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\SellerEngine;
use Application\Databases\CrystalCommerceRepository;
use Application\Databases\PricesRepository;
use Application\Databases\RunTimeRepository;
use Application\Databases\SellerEngineRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class GetDataController extends AbstractActionController
{


    /**
     * @var \Zend\Log\Logger
     */
    protected $logger;
    protected $debug;

    protected $startTime;

    protected $tempFileName;

    public function __construct()
    {
        ini_set('memory_limit','1024M');
        //date_default_timezone_set ('America/Chicago');  //This is set in php.ini now.
        $this->startTime = date('Y-m-d H:i:s');
    }

    // Each public action must instantiate a logger and a temp file name in order to use logScript()

    public function testScriptAction()
    {
        $this->setLogger('testScriptLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempTestScriptLog.txt';
        $this->addTempLogger($this->tempFileName);

        $message = "Successfully ran test Script.";
        //$this->logger->info($message);
        $this->logScript('Test Script Update', $message);
    }

    public function getSelleryPricingAction()
    {
        set_time_limit(0);

        $this->setLogger('SelleryPricesUpdateLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempSelleryLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipDownload = $this->params()->fromQuery('skipDownload', false);
        $jumpToExportId = $this->params()->fromQuery('jumpToExportId', false);

        $sellery = new SellerEngine($this->logger, $this->debug);

        // The downloader always saves to the same location.  You can skip the download
        // while testing, or if you just made a download.
        if (!$skipDownload) {
            $pricesArray = $sellery->downloadReportAndReturnArray($jumpToExportId);
            $this->logger->info("There are " . count($pricesArray) . " prices to be updated");
        } else {
            $pricesArray = $sellery->createArrayfromFile();
        }

        $pricesRepo = new SellerEngineRepository($this->logger, $this->debug);

        if ($pricesRepo->importFromArray($pricesArray)) {
            $message = "Successfully imported CSV File.";
            $this->logger->info($message);
            $this->logSelleryScript($message);
        } else {
            $message = "Failed to import CSV File.";
            $this->logger->info($message);
            $this->logSelleryScript($message);
        }
    }

    protected function logSelleryScript($message)
    {
        $this->logScript('Sellery Price Update', $message);
    }


    /******************************************
     * Request a new data download from Crystal Commerce
     * Wait for the download to be ready.
     * Download it into a temporary file.
     *
     * Load that temporary file into an array.
     * Load that array into the database using the mapping in the config file.
     * Currently that means everything but sale price (which comes from Sellery
     *
     * Note: This method uses a LOT of RAM as the file gets bigger
     * However, if you try to use mysql command LOAD DATA INFILE to import directly
     * you are open to errors from the product descriptions.
     ****************************************/
    public function getCrystalCommerceDataAction()
    {
        set_time_limit(0);

        $this->setLogger('CrystalCommerceGetPricesLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempCCLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipDownload = $this->params()->fromQuery('skipDownload', false);
        $includeOutOfStock = $this->params()->fromQuery('includeOutOfStock', false);
        $skipImport = $this->params()->fromQuery('skipImport', false);

        if ($includeOutOfStock) {
            $this->logger->info("Downloading All records, including Out of Stock");
        }

        $crystal = new CrystalCommerce($this->logger, $this->debug);
        if (!$skipDownload) {
            $csvFile = $crystal->downloadCsv($includeOutOfStock);
            if ($csvFile) {
                $this->logger->info("Successfully downloaded a CSV File.");
            } else {
                $message = "Attempted to download a CSV File and failed.";
                $this->logger->err($message);
                $this->logCrystalCommerceScript($message);
                return false;
            }
        }

        $pricesArray = $crystal->getMostRecentCsvAsArray();
        $this->logger->info("There are " . count($pricesArray) . " prices to be updated");

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {

            $pricesRepo = new CrystalCommerceRepository($this->logger, $this->debug);
            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
                $this->logger->info($message);
                $this->logCrystalCommerceScript($message);
            } else {
                $message = "Failed to import CSV File.";
                $this->logger->err($message);
                $this->logCrystalCommerceScript($message);
            }
        }
        return true;
    }

    protected function logCrystalCommerceScript($message)
    {
        $scriptName  = 'Crystal Commerce Price Update';
        $this->logScript($scriptName, $message);
    }


    protected function logScript($scriptName, $message)
    {
        $runTimes = new RunTimeRepository($this->logger, $this->debug);
        $this->logger->info($message);
        $errorLog = substr(file_get_contents($this->tempFileName),0,1400);
        $runTimes->logScriptRun($scriptName, $message, $errorLog, $this->startTime);
        file_put_contents($this->tempFileName,'');
    }

    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }

    private function addTempLogger($tempFileName)
    {
        $this->logger = LoggerFactory::addWriterToLogger($this->logger, $tempFileName);
    }

}