<?php

namespace Application\Controller;


use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\TrollandToad;
use Application\Databases\LastEvoPriceUpdateRepository;
use Application\Databases\LastPriceUpdatedRepository;
use Application\Databases\PricesRepository;
use Application\Databases\PriceUpdatesRepository;
use Application\Databases\RunTimeRepository;
use Application\Factory\LoggerFactory;
use Application\ScriptNames;
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
        $this->startTime = date('Y-m-d H:i:s');
        $this->debug = true;
    }

    public function trollEvoUpdateAction()
    {
        $this->setLogger('uploadEvoLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempEvoPriceUpdateLog.txt';
        $this->addTempLogger($this->tempFileName);

        $updateLimit = intval($this->params()->fromQuery('updateLimit', 20));
        $maxPrice = intval($this->params()->fromQuery('maxPrice', 0));

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getProductsToUpdateOnTrollEvo($updateLimit, $maxPrice);

        if (count($productsToUpdate) > 0 ) {
            $productsToUpdate = $this->calculateEvoPrices($productsToUpdate);

            $troll = new TrollandToad($this->logger, $this->debug);
            $result = $troll->evoUploadArray($productsToUpdate);

            if ($result) {
                if($this->logAndMarkEvoProductsUpdated($productsToUpdate)) {
                    $message = "Upload Successful to EVO";
                } else {
                    $message = "Upload Successful to EVO, but database update failed.";
                }
            } else {
                $message = "Failed to Upload prices to Troll Evo";
            }

        } else {
            $message = "No Prices to update";
        }

        $scriptName = ScriptNames::SCRIPT_UPDATE_TROLL_EVO_INVENTORY;
        $this->logScript($scriptName,$message);
        return new ViewModel();
    }

    private function calculateEvoPrices($productsToUpdate)
    {
        $priceUpdates = [];

        foreach($productsToUpdate as $key => $product) {

            $sellPrice = $this->calculateEvoSellPrice($product);

            $quantity = $product['evo_quantity'];
            $holdQuantity = $product['evo_hold_quantity'];

            if ($quantity == 0) {
                if ($holdQuantity < 2) {
                    $holdQuantity = 0;
                } else {
                    if ($holdQuantity > 12 ) {
                        // Release One Third
                        $holdQuantity = round($holdQuantity / 3, 0, PHP_ROUND_HALF_DOWN);
                    } else {
                        // Hold quantity between 2 and 11, Release half, max 4
                        if ($holdQuantity < 8) {
                            $holdQuantity = round($holdQuantity / 2, 0, PHP_ROUND_HALF_DOWN);
                        } else {
                            $holdQuantity = $holdQuantity - 4;
                        }
                    }
                }
                // Product was just released from hold.  Price it up 10% for a day
                $sellPrice *= 1.1;
                // record new quantity
                $quantity += $product['evo_hold_quantity'] - $holdQuantity;
            }

            // Build Update Array
            $priceUpdates[$key]['product_detail_id'] = $product['product_detail_id'];
            $priceUpdates[$key]['sell_price_old'] = $product['evo_sell_price'];
            $priceUpdates[$key]['sell_price_new'] = $sellPrice;
            $priceUpdates[$key]['hold_qty_old'] = $product['evo_hold_quantity'];
            $priceUpdates[$key]['hold_qty_new'] = $holdQuantity;
            //For logging only, not actually updated
            $priceUpdates[$key]['product_name'] = $product['product_name'];
            $priceUpdates[$key]['quantity_old'] = $product['evo_quantity'];
            $priceUpdates[$key]['quantity_new'] = $quantity;
            $priceUpdates[$key]['evo_cost'] = $product['evo_cost'];
        }
        return $priceUpdates;
    }

    private function calculateEvoSellPrice($product)
    {
        $sellPrice = $product['evo_sell_price'];

        if ($product['troll_sell_price']) {
            if ($product['troll_sell_price'] < 10) {
                $sellPrice = $product['troll_sell_price'] - 0.10;
            } else {
                $sellPrice = $product['troll_sell_price'] * 0.99;
            }
        }
        // lowest_evo_competitor_sell_price is actually lowest price and can include yourself
        // don't try to price below yourself
        if ($product['lowest_evo_competitor_sell_price'] && $product['lowest_evo_competitor_sell_price'] < $sellPrice) {
            $sellPrice = $product['lowest_evo_competitor_sell_price'];
        }
        // Sellery price being higher should override troll prices
        // In the future, only use this if Troll is sold out
        if ($product['sellery_sell_price'] && $product['sellery_sell_price'] > $sellPrice) {
            $sellPrice = $product['sellery_sell_price'];
        }
        // Buy price warning catch all. If you are about to price too low compared to troll Buy
        // Price above troll buy.
        if ($product['troll_buy_price']) {
            if ($sellPrice < $product['troll_buy_price'] * $this->warningBuyPriceMultiplier)
                $sellPrice = $product['troll_buy_price'] * $this->warningBuyPriceMultiplier;
        }

        // Price Floor of $0.69 for anything not already priced below $0.59
        if ($sellPrice < 0.69 ) {
            if ($product['evo_sell_price'] > 0.59) {
                $sellPrice = 0.69;
            } else {
                $sellPrice = $product['evo_sell_price'];
            }
        }
        $sellPrice = round($sellPrice, 2, PHP_ROUND_HALF_DOWN);

        $this->logger->debug("Inside Repricer : " . $product['product_name'] .
            " : sellPrice : $sellPrice : Old Sell Price : " . $product['evo_sell_price']);

        return $sellPrice;
    }


    public function indexAction()
    {
        $this->setLogger('uploadCCLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempCCPriceUpdateLog.txt';
        $this->addTempLogger($this->tempFileName);

        $this->updateLimit = intval($this->params()->fromQuery('updateLimit', 15));
        $mode = $this->params()->fromQuery('mode', 'instock');

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getPricesToUpdate($mode, $this->updateLimit);

        if (count($productsToUpdate) > 0 ) {
            $productsToUpdate = $this->calculatePrices($productsToUpdate);

            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $result = $crystal->updateProductPrices($productsToUpdate);

            if ($result) {
                if($this->logAndMarkProductsUpdated($productsToUpdate)) {
                    $message = "Upload Successful in " . $mode . " mode";
                } else {
                    $message = "Upload Successful in " . $mode . " mode, but database update failed.";
                }
            } else {
                $message = "Failed to Upload prices to CC";
            }

        } else {
            $message = "No Prices to update in " . $mode. " mode";
        }

        if ($mode == 'instock') {
            $scriptName = ScriptNames::SCRIPT_PRICES_TO_CC_INSTOCK;
        } else {
            $scriptName = ScriptNames::SCRIPT_PRICES_TO_CC_BUY;
        }

        $this->logScript($scriptName,$message);
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
    private function logAndMarkEvoProductsUpdated($productsToUpdate)
    {
        $updatedProducts = [];
        foreach ($productsToUpdate as $key => $product) {
            $this->logger->info("{$product['product_detail_id']} : {$product['product_name']} : has been updated to : " .
                "sell : {$product['sell_price_new']} : hold : {$product['hold_qty_new']}");
        }

        $repositoryLU = new LastEvoPriceUpdateRepository($this->logger, $this->debug);

        return $repositoryLU->importFromArray($productsToUpdate);
    }

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