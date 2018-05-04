<?php

namespace Application\Controller;


use Application\ApiConnection\CrystalCommerce;
use Application\Databases\CrystalCommerceRepository;
use Application\Databases\LastPriceUpdatedRepository;
use Application\Databases\PricesRepository;
use Application\Databases\PriceUpdatesRepository;
use Application\Databases\RunTimeRepository;
use Application\Databases\SellerEngineRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UploadController extends AbstractActionController
{
    private $debug;
    private $updateLimit;
    private $logger;

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

    public function indexAction()
    {
        $this->setLogger('uploadLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempCCPriceUpdateLog.txt';
        $this->addTempLogger($this->tempFileName);

        $this->updateLimit = intval($this->params()->fromQuery('updateLimit', 15));
        $this->mode = $this->params()->fromQuery('mode', 'instock');

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getPricesToUpdate($this->mode, $this->updateLimit);



        if (count($productsToUpdate) > 0 ) {
            $productsToUpdate = $this->calculatePrices($productsToUpdate);
            //$this->logger->debug(print_r($productsToUpdate, true));

            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $result = $crystal->updateProductPrices($productsToUpdate);

            if ($result) {
                if($this->logAndMarkProductsUpdated($productsToUpdate)) {
                    $message = "Upload Successful in " . $this->mode . " mode";
                } else {
                    $message = "Upload Successful in " . $this->mode . " mode, but database update failed.";
                }
            } else {
                $message = "Failed to Upload prices to CC";
            }

        } else {
            $message = "No Prices to update";
        }
        $this->logScript('Prices to CC Update',$message);
        return new ViewModel();
    }

    private $warningBuyPriceMultiplier = 1.3;
    private $createBuyFromTrollBuyMultiplier = 1;


    /**
     * Update the database with  the buy price and sell price which were just sent to Crystal Commerce
     *
     * @param array $productsToUpdate numerically indexed array of products from Database
     * @return bool success or failure
     */
    private function logAndMarkProductsUpdated($productsToUpdate)
    {
        $updatedProducts = [];
        foreach ($productsToUpdate as $key => $product) {
            $this->logger->info("{$product['asin']} : {$product['product_name']} : has been updated to : " .
                "sell : {$product['sell_price_new']} : buy : {$product['buy_price_new']}");
        }

        $repositoryPU = new PriceUpdatesRepository($this->logger, $this->debug);
        $repositoryLU = new LastPriceUpdatedRepository($this->logger, $this->debug);

        $result = $repositoryLU->importFromArray($productsToUpdate);

        return $repositoryPU->importFromArray($productsToUpdate) && $result;
    }

    private function calculatePrices($productsToUpdate)
    {
        $priceUpdates = [];

        foreach($productsToUpdate as $key => $product) {

            $sellPrice = $this->determineSellPrice($product);
            if ($product['troll_buy_price']) {
                $buyPrice = $product['troll_buy_price'] * $this->createBuyFromTrollBuyMultiplier;
            } else {
                if ($product['sellery_sell_price'] && $product['total_qty'] >0 ) {
                    $buyPrice = $this->getBuyPrice($sellPrice);
                } else {
                    // buy price unchanged
                    $buyPrice = $product['cc_buy_price'];
                }
            }
            $priceUpdates[$key]['product_name'] = $product['product_name'];
            $priceUpdates[$key]['sell_price_old'] = $product['cc_sell_price'];
            $priceUpdates[$key]['sell_price_new'] = $sellPrice;
            $priceUpdates[$key]['asin'] = $product['asin'];
            $priceUpdates[$key]['productId'] = $product['productId'];
            $priceUpdates[$key]['buy_price_old'] = $product['cc_buy_price'];
            $priceUpdates[$key]['buy_price_new'] = $buyPrice;

        }
        return $priceUpdates;
    }

    private function determineSellPrice($product)
    {
        if ($product['sellery_sell_price'] && $product['total_qty'] >0) {
            if ($product['troll_buy_price']*$this->warningBuyPriceMultiplier  > $product['sellery_sell_price']) {
                $sellPrice = $this->getSellPriceFromBuyPrice($product['troll_buy_price']);
            } else {
                $sellPrice = $product['sellery_sell_price'];
            }
        } else {
            if ($product['troll_buy_price']) {
                $sellPrice = $this->getSellPriceFromBuyPrice($product['troll_buy_price']);
            } else {
                // in order words, do not change the price
                $sellPrice = $product['cc_sell_price'];
            }
        }
        return $sellPrice;
    }

    /**
     *  For a given sell price, calculate the buy price
     *
     * @param float $sellPrice
     * @return float 2 decimal point precision
     */
    private function getBuyPrice($sellPrice)
    {
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
        $buyPrice = $sellPrice * $percentage;
        return number_format($buyPrice, 2);
    }

    /**
     *  For a given sell price, calculate the buy price
     *
     * @param float $buyPrice
     * @return float 2 decimal point precision
     */
    private function getSellPriceFromBuyPrice($buyPrice)
    {
        switch ($buyPrice ) {
            case $buyPrice < 2:
                $percentage = 4;
                break;
            case $buyPrice < 4 :
                $percentage = 3;
                break;
            default :
                $percentage = 2;
                break;
        }
        $sellPrice = $buyPrice * $percentage;
        return number_format($sellPrice, 2);
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