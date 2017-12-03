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
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    protected $logger;
    protected $debug = true;

    public function __construct()
    {
        $this->logger = LoggerFactory::createLogger('updateLog.txt', false, $this->debug);
    }

    public function indexAction()
    {
        $scripts = [
            'Get Prices From Crystal Commerce' => '/application/get-crystal-commerce-data',
            'Get Prices From Sellery' => '/application/get-sellery-pricing',
            'Update Prices From Database to Crystal Commerce (errors on CC side)' => '/application/update-crystal-commerce-prices',
        ];
        if (getenv('APPLICATION_ENV') == 'development') {
            $scripts['Load Crystal Commerce Prices From Local File'] = '/application/get-crystal-commerce-data?skipDownload=true';
            $scripts['Load Sellery Prices From Local File'] = '/application/get-sellery-pricing?skipDownload=true';
        }

        $downloads = [
            'Download Full Price List' => '/download/prices-to-update',
            'Download Prices All Changed prices in Last day' => '/download/prices-to-update?daysLimit=1',
            'Download Prices With > 2% and > $0.05 changes' => '/download/prices-to-update?daysLimit=1&changesOnly=true',
            'Download Price List for Quick Upload' => '/download/prices-to-update?quickUploadOnly=true&changesOnly=true',
        ];

        $variables = [
            'scripts' => $scripts,
            'downloads' => $downloads,
        ];
        // This just shows the user the default Zend Skeleton home page if they load http://localhost/
        return new ViewModel($variables);
    }




    public function testAction()
    {

    }

    public function updateCrystalCommercePricesAction()
    {
        set_time_limit(0);

        $this->setLogger('CrystalCommercePricesUpdateLog.txt');

        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        $pricesArray = $pricesRepo->getRecordsWithPriceChanges(true);

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
            $pricesArray = $sellery->createArrayfromFile();
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
        $crystal = new CrystalCommerce($this->logger, $this->debug);
        if (!$skipDownload) {
            $csvFile = $crystal->downloadCsv();
            if ($csvFile) {
                $this->logger->info("Successfully downloaded a CSV File.");
            }
        }

        $pricesArray = $crystal->getMostRecentCsvAsArray();

        $this->logger->info("There are " . count($pricesArray) . " prices to be updated");
        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        if($pricesRepo->importPricesFromCC($pricesArray)) {
            $this->logger->info ("Successfully imported CSV File.");
        } else {
            $this->logger->info ("Failed to import CSV File.");
        }

        print("</pre>");
    }

    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }


}
