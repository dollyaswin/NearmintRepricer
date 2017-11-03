<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\SellerEngine;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        //$sellery = new SellerEngine();
        //$priceArray = $sellery->downloadReportAndReturnArray();

        //print("<pre>");

        $crystal = new CrystalCommerce();
        $csvFile = $crystal->downloadCsv();
        if ($csvFile) {
            print ("Successfully downloaded a CSV File." . PHP_EOL);
            print ($csvFile);
        }
        print("</pre>");
        //return new ViewModel();
    }
}
