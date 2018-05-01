<?php

namespace Application\Controller;


use Application\ApiConnection\CrystalCommerce;
use Application\Databases\CrystalCommerceRepository;
use Application\Databases\PricesRepository;
use Application\Databases\SellerEngineRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UploadController extends AbstractActionController
{
    private $debug;
    private $inBrowser;
    private $updateLimit;
    private $logger;

    public function indexAction()
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $this->inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger('uploadLog.txt', $this->inBrowser, $this->debug);

        $this->updateLimit = $this->params()->fromQuery('updateLimit', 15);

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getPricesToUpdate($this->updateLimit);



        if (count($productsToUpdate) > 0 ) {
            $productsToUpdate = $this->setBuyPrices($productsToUpdate);
            $this->logger->debug(print_r($productsToUpdate, true));

            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $result = $crystal->updateProductPrices($productsToUpdate);

            if ($result) {
                if($this->logAndMarkProductsUpdated($productsToUpdate)) {
                    $this->logger->info("Upload Successful");
                } else {
                    $this->logger->warn("Upload Successful, but database update failed.");
                }
            }

        }


        return new ViewModel();
    }

    /**
     * Update the database with  the buy price and sell price which were just sent to Crystal Commerce
     *
     * @param array $productsToUpdate numerically indexed array of products from Database
     * @return bool success or failure
     */
    private function logAndMarkProductsUpdated($productsToUpdate)
    {
        $updatedProductsCC = [];
        $updatedProductsSE = [];
        foreach ($productsToUpdate as $key => $product) {
            $this->logger->info(" {$product['product_name']} : has been updated to : " .
                "sell : {$product['cc_sell_price']} : buy : {$product['buy_price']}");
            $updatedProductsCC[$key]['Sell Price'] = $product['cc_sell_price'];
            $updatedProductsCC[$key]['ASIN'] = $product['asin'];
            $updatedProductsCC[$key]['Buy Price'] = $product['buy_price'];

            $updatedProductsSE[$key]['Live price on Near Mint Games'] = $product['cc_sell_price'];
            $updatedProductsSE[$key]['ASIN on amazon.com'] = $product['asin'];

        }

        $repositoryCC = new CrystalCommerceRepository($this->logger, $this->debug);

        $repositorySE = new SellerEngineRepository($this->logger, $this->debug);
        $result = $repositorySE->importFromArray($updatedProductsSE);

        return $repositoryCC->importFromArray($updatedProductsCC) && $result;
    }

    /**
     *  Update and return the array of products with modified by prices.
     *
     * @param array $productsToUpdate
     * @return array
     */
    private function setBuyPrices($productsToUpdate)
    {
        foreach ($productsToUpdate as $key => $product) {
            if ($product['buy_price'] > $product['cc_sell_price'] * 0.75 ) {
                $sellPrice = $product['cc_sell_price'];
                switch ($sellPrice ) {
                    case $sellPrice < 1.5 :
                        $percentage = 0.25;
                        break;
                    case $sellPrice < 3 :
                        $percentage = 0.35;
                        break;
                    case $sellPrice < 6 :
                        $percentage = 0.40;
                        break;
                    case $sellPrice < 15 :
                        $percentage = 0.50;
                        break;
                    case $sellPrice < 30 :
                        $percentage = 0.55;
                        break;
                    case $sellPrice < 60 :
                        $percentage = 0.25;
                        break;
                    default :
                        $percentage = 0.65;
                        break;
                }
                $product['buy_price'] = $sellPrice * $percentage;
            }
            $productsToUpdate[$key]['buy_price'] = number_format($product['buy_price'], 2);
        }
        return $productsToUpdate;
    }

    /*
     * if buy price is above 75% of the retail price follow these,

        up to $1.49. - 25% of retail price
        $1.50 - $2.99 - 35% of retail price.
        $3.00 - $5.99 - 40% of retail price
        $6.00 - $14.99 50% of retail price
        $15.00 - $29.99 - 55% of retail price
        $30.00 - $59.99 - 60% of retail price
        $60.00 + 65% of retail price
    */



}