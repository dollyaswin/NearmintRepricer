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
use Application\Databases\PricesRepository;
use Application\Databases\RunTimeRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    protected $logger;
    protected $debug = true;

    public function __construct()
    {
        ini_set('memory_limit','1024M');
        $this->logger = LoggerFactory::createLogger('updateLog.txt', false, $this->debug);
    }

    public function indexAction()
    {
        $scripts = [
            'Get Prices From Crystal Commerce' => '/get-data/get-crystal-commerce-data',
            'Get Prices From Crystal Commerce, Include OOS' => '/get-data/get-crystal-commerce-data?includeOutOfStock=true',
            'Get Prices From Sellery' => '/get-data/get-sellery-pricing',
            'Get Buy Prices From Troll and Toad' => '/get-data/troll-buy-prices',
            'Update Prices From Database to Crystal Commerce (errors on CC side)' => '/application/update-crystal-commerce-prices',
        ];
        if (getenv('APPLICATION_ENV') == 'development') {
            $scripts['Crystal Commerce Data'] = '/get-data/get-crystal-ids?inBrowser=true&debug=true';
            $scripts['Download Crystal Commerce Prices Skip Import'] = '/get-data/get-crystal-commerce-data?skipImport=true&inBrowser=true&debug=true';
            $scripts['Download Crystal Commerce Prices Skip Import Include OOS'] = '/get-data/get-crystal-commerce-data?skipImport=true&includeOutOfStock=true&inBrowser=true&debug=true';
            $scripts['Load Crystal Commerce Prices From Local File'] = '/get-data/get-crystal-commerce-data?skipDownload=true&inBrowser=true&debug=true';
            $scripts['Load Sellery Prices From Local File'] = '/get-data/get-sellery-pricing?skipDownload=true&inBrowser=true&debug=true';
            $scripts['Download Sellery Prices Skip Import'] = '/get-data/get-sellery-pricing?skipImport=true&inBrowser=true&debug=true';
            //$scripts['Test Script'] = 'http://localhost:8080/get-data/test-script';
        }

        $downloads = [
            'Spreadsheet Generator' => '/download',
            'Import Troll Product List' => '/get-data/troll-products',
            //'Download Prices All Changed prices in Last day' => '/download/prices-to-update?daysLimit=1',
            //'Download Prices With > 2% and > $0.05 changes' => '/download/prices-to-update?daysLimit=1&changesOnly=true',
            //'Download Price List for Quick Upload' => '/download/prices-to-update?quickUploadOnly=true&changesOnly=true',
        ];

        $scriptRunRepo = new RunTimeRepository($this->logger, $this->debug);
        $recentRunData = $scriptRunRepo->getRunInformation(10);

        $variables = [
            'scripts' => $scripts,
            'downloads' => $downloads,
            'recentScriptRunData' => $recentRunData,
        ];

        // This just shows the user the default Zend Skeleton home page if they load http://localhost/
        return new ViewModel($variables);
    }




    public function testAction()
    {

    }


    // This function needs rewritten to use getRecords() differently
    // and to use the CC API
    private function updateCrystalCommercePricesAction()
    {
        set_time_limit(0);

        $this->setLogger('CrystalCommercePricesUpdateLog.txt');

        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        $pricesArray = $pricesRepo->getRecords([],true, false, true);

        if ($pricesArray) {
            $this->logger->info("There are " . count($pricesArray) . " prices to be uploaded");
            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $crystal->createFileForImport($pricesArray);

            if($ouput = $crystal->uploadFileToImportForm()) {
                $this->logger->info("Successfully uploaded CSV File.");
            } else {
                $this->logger->info("Failed to upload CSV File.");
            }
        } else {
            $this->logger->info("There are no prices which need to be updated");
        }

    }

    public function getSelleryPricingAction()
    {
        set_time_limit(0);

        $this->setLogger('SelleryPricesUpdateLog.txt');

        $skipDownload = $this->params()->fromQuery('skipDownload', false);

        $jumpToExportId = $this->params()->fromQuery('jumpToExportId', false);

        $sellery = new SellerEngine($this->logger, $this->debug);

        // The downloader always saves to the same location.  You can skip the download
        // while testing, or if you just made a download.
        if (!$skipDownload) {
            $pricesArray = $sellery->downloadReportAndReturnArray($jumpToExportId);
            $this->logger->info("There are " . count($pricesArray) . " prices to be updated");
        } else {
            $pricesArray = $sellery->createArrayFromFile();
        }

        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        if($pricesRepo->importPricesFromSellery($pricesArray)) {
            $this->logger->info("Successfully imported CSV File.");
        } else {
            $this->logger->info("Failed to import CSV File.");
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
        set_time_limit(0);

        $this->setLogger('CrystalCommerceGetPricesLog.txt');

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
                $this->logger->err("Attempted to download a CSV File and failed.");
                print("</pre>");
                return false;
            }
        }

        $pricesArray = $crystal->getMostRecentCsvAsArray();
        $this->logger->info("There are " . count($pricesArray) . " prices to be updated");

        if ($skipImport) {
            $this->logger->info("Skipping importing the CSV File.");
        } else {

            $pricesRepo = new PricesRepository($this->logger, $this->debug);
            if ($pricesRepo->importPricesFromCC($pricesArray)) {
                $this->logger->info("Successfully imported CSV File.");
            } else {
                $this->logger->info("Failed to import CSV File.");
            }
        }

        print("</pre>");
        return true;
    }


    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }


}
