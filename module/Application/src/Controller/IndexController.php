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
 * */


namespace Application\Controller;

use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\SellerEngine;
use Application\Databases\PricesRepository;
use Composer\Downloader\DownloadManager;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        // This just shows the user the default Zend Skeleton home page if they load http://localhost/
        //return new ViewModel();
    }

    public function testAction()
    {

    }

    public function updateCrystalCommercePricesAction()
    {
        set_time_limit(0);
        print("<pre>");

        $pricesRepo = new PricesRepository();
        $pricesArray = $pricesRepo->getRecordsWithPriceChanges();

        if ($pricesArray) {
            print "There are " . count($pricesArray) . " prices to be uploaded" . PHP_EOL;
            $crystal = new CrystalCommerce();
            $crystal->createFileForImport($pricesArray);

            if($ouput = $crystal->uploadFileToImportForm()) {
                print ("Successfully uploaded CSV File." . PHP_EOL);
            } else {
                print ("Failed to upload CSV File." . PHP_EOL);
            }
        } else {
            print ("There are no prices which need to be updated" . PHP_EOL);
        }
        print("</pre>");

    }

    public function getSelleryPricingAction()
    {
        print("<pre>");

        set_time_limit(0);

        $sellery = new SellerEngine();
        $pricesArray = $sellery->downloadReportAndReturnArray();

        print ("There are " . count($pricesArray) . " prices to be updated" . PHP_EOL);

        $pricesRepo = new PricesRepository();
        if($pricesRepo->importPricesFromSellery($pricesArray)) {
            print ("Successfully imported CSV File." . PHP_EOL);
        } else {
            print ("Failed to import CSV File." . PHP_EOL);
        }
        print("</pre>");
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
        print("<pre>");

        set_time_limit(0);

        $crystal = new CrystalCommerce();
        $csvFile = $crystal->downloadCsv();
        if ($csvFile) {
            print ("Successfully downloaded a CSV File." . PHP_EOL);
        }

        $pricesArray = $crystal->getMostRecentCsvAsArray();

        print ("There are " . count($pricesArray) . " prices to be updated" . PHP_EOL);
        $pricesRepo = new PricesRepository();
        if($pricesRepo->importPricesFromCC($pricesArray)) {
            print ("Successfully imported CSV File." . PHP_EOL);
        } else {
            print ("Failed to import CSV File." . PHP_EOL);
        }

        print("</pre>");
    }

}
