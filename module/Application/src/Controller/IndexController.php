<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

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

}
