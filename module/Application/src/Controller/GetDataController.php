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

use Application\ApiConnection\CrystalApi;
use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\SellerEngine;
use Application\ApiConnection\TrollandToad;
use Application\Databases\CrystalCommerceRepository;
use Application\Databases\PricesRepository;
use Application\Databases\RunTimeRepository;
use Application\Databases\SellerEngineRepository;
use Application\Databases\TrollBuyListRepository;
use Application\Databases\TrollEvoInventoryRepository;
use Application\Databases\TrollProductRepository;
use Application\Factory\LoggerFactory;
use Application\ScriptNames;
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
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        //date_default_timezone_set ('America/Chicago');  //This is set in php.ini now.
        $this->startTime = date('Y-m-d H:i:s');
        $this->debug = true;
    }

    // Each public action must instantiate a logger and a temp file name in order to use logScript()

    public function testScriptAction()
    {
        $this->setLogger('testScriptLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempTestScriptLog.txt';
        $this->addTempLogger($this->tempFileName);

        $message = "Successfully ran test Script.";
        $this->logScript(ScriptNames::SCRIPT_TEST, $message);
    }

    /**
     *  Loads the troll and toad buy list CSV download files into the database
     *  but only for Troll's categories specified in the config file.
     */
    public function trollBuyPricesAction()
    {
        $this->setLogger('TrollBuyPriceUpdateLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempTrollLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipDownload = $this->params()->fromQuery('skipDownload', false);
        $skipImport = $this->params()->fromQuery('skipImport', false);

        // Get API Connection
        $troll = new TrollandToad($this->logger, $this->debug);
        if (!$skipDownload) {
            $pricesArray = $troll->getBuyListArray();
            $this->logger->info("There are " . count($pricesArray) . " prices to be updated");
        } else {
            $pricesArray = $troll->createArrayfromFile();
        }

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {
            // Get Database Connection
            $pricesRepo = new TrollBuyListRepository($this->logger, $this->debug);

            // This is important, because not every product will be listed on the buy list at all times.
            $pricesRepo->wipeOutCurrentBuyQuantity();

            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript(ScriptNames::SCRIPT_GET_TROLL_BUY,$message);
        }
    }

    /**
     *  Loads the merchant's inventory from the troll and toad EVO program.
     *
     */
    public function trollEvoInventoryAction()
    {
        $this->setLogger('TrollEvoInventoryUpdateLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempTrollEvoLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipDownload = $this->params()->fromQuery('skipDownload', false);
        $skipImport = $this->params()->fromQuery('skipImport', false);

        // Get API Connection
        $troll = new TrollandToad($this->logger, $this->debug);
        if (!$skipDownload) {
            $pricesArray = $troll->evoDownload();
            $this->logger->info("There are " . count($pricesArray) . " Evo Listings to be updated");
        } else {
            $pricesArray = $troll->createArrayfromFile();
        }

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {
            // Get Database Connection
            $pricesRepo = new TrollEvoInventoryRepository($this->logger, $this->debug);

            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript(ScriptNames::SCRIPT_GET_TROLL_EVO_INVENTORY, $message);
        }
    }


    /**
     *  Loads the troll and toad buy list CSV download files into the database
     *  but only for Troll's categories specified in the config file.
     */
    public function trollProductsAction()
    {
        $scriptName = ScriptNames::SCRIPT_LOAD_TROLL_TRODUCTS;

        if (!$this->getRequest()->isPost()) {
            return new ViewModel();
        } else {

            $this->setLogger('TrollProductsUpdateLog.txt');
            $this->tempFileName = __DIR__ . '/../../../../logs/tempTrollProductLog.txt';
            $this->addTempLogger($this->tempFileName);

            $key = $this->params()->fromPost('key', false);
            if ($key != getenv('IMPORT_KEY')) {
                $message = "Bad Key. Import not Accepted. $key ";
                $this->logScript($scriptName, $message);
                return new ViewModel(['message' => $message]);
            }

            $destPath = __DIR__ . '/../../../../data/mostRecentUploadTrollProducts.csv';
            $result = move_uploaded_file($_FILES['myfile']['tmp_name'], $destPath);
            if(!$result) {
                $message = 'Uploaded file would not save to disk';
                $this->logScript($scriptName, $message);
                return new ViewModel(['message' => $message]);
            }

            // Get API Connection just to parse the file.
            $troll = new TrollandToad($this->logger, $this->debug);
            $pricesArray = $troll->createArrayfromFile($destPath);

            // Get Database Connection
            $pricesRepo = new TrollProductRepository($this->logger, $this->debug);

            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript($scriptName, $message);
            return new ViewModel(['message' => $message]);
        }
    }


    public function getSelleryPricingAction()
    {
        $scriptName = ScriptNames::SCRIPT_GET_SELLERY_PRICES;

        $this->setLogger('SelleryPricesUpdateLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempSelleryLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipDownload = $this->params()->fromQuery('skipDownload', false);
        $skipImport = $this->params()->fromQuery('skipImport', false);

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

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {
            $pricesRepo = new SellerEngineRepository($this->logger, $this->debug);

            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript($scriptName, $message);
        }
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
        $scriptName  = ScriptNames::SCRIPT_GET_CC_PRICES;

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
                $this->logScript($scriptName, $message);
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
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript($scriptName, $message);
        }
        return true;
    }
/*
    public function getCrystalProductsUsingApiAction()
    {
        $scriptName  = ScriptNames::SCRIPT_GET_CC_PRICES_API;

        $this->setLogger('CrystalCommerceGetDataLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempCCLog.txt';
        $this->addTempLogger($this->tempFileName);

        $skipImport = $this->params()->fromQuery('skipImport', false);

        $crystalApi = new CrystalApi\ProductDownload($this->logger,$this->debug);
        $pricesArray = $crystalApi->downloadProducts();

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {
            $pricesRepo = new CrystalCommerceRepository($this->logger, $this->debug);
            if ($pricesRepo->importFromArray($pricesArray)) {
                $message = "Successfully imported CSV File.";
            } else {
                $message = "Failed to import CSV File.";
            }
            $this->logScript($scriptName, $message);
        }

        return true;
    }
*/

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