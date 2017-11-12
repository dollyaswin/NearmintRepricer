<?php

namespace Application\Controller;

use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\SellerEngine;
use Application\Databases\PricesRepository;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        print("<pre>");

        set_time_limit(0);

        $sellery = new SellerEngine();
        $selleryPriceArray = $sellery->downloadReportAndReturnArray();

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
        //return new ViewModel();
    }

    public function testAction()
    {
        print "Test Action";
    }

    public function updateCrystalCommercePricesAction()
    {
        print ("Update Crystal Commerce Prices " . PHP_EOL);
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
