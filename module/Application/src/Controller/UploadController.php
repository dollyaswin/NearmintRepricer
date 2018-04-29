<?php

namespace Application\Controller;


use Application\ApiConnection\CrystalCommerce;
use Application\Databases\PricesRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UploadController extends AbstractActionController
{
    private $debug;

    public function indexAction()
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $logger = LoggerFactory::createLogger('uploadLog.txt', true, true);
        $prices = new PricesRepository($logger);
        $productsToUpdate = $prices->getPricesToUpdate(1);
        $logger->info(print_r($productsToUpdate, true));


        $crystal = new CrystalCommerce($logger, $this->debug);
        $result = $crystal->updateProductPrices($productsToUpdate);

        if ($result) {
            $logger->info("Upload Successful");
        }

        return new ViewModel();
    }


}