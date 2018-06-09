<?php

namespace Application\Repricer;

use Application\Databases\LastPriceUpdatedRepository;
use Application\Databases\PriceUpdatesRepository;
use Application\Repricer;
use Zend\Log\Logger;

class CrystalFromSellery extends Repricer
{
    private $warningBuyPriceMultiplier = 1.3;
    private $createBuyFromTrollBuyMultiplier = 1;

    public function calculatePrices(Array $productsToUpdate)
    {
        $priceUpdates = [];

        foreach($productsToUpdate as $key => $product) {

            list($sellPrice, $repriceRuleId) = $this->determineSellPrice($product);

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

    public function markProductsUpdated(Array $productsToUpdate)
    {
        $this->printToLog($productsToUpdate);
        $repositoryPU = new PriceUpdatesRepository($this->logger, $this->debug);
        $repositoryLU = new LastPriceUpdatedRepository($this->logger, $this->debug);

        $result = $this->saveToRepository($productsToUpdate, $repositoryLU);

        return $this->saveToRepository($productsToUpdate, $repositoryPU) && $result;
    }

    private function determineSellPrice($product)
    {
        $repriceRuleId = Repricer::RR_NO_CHANGE;

        if ($product['sellery_sell_price'] && $product['total_qty'] >0) {
            if ($product['troll_buy_price']*$this->warningBuyPriceMultiplier  > $product['sellery_sell_price']) {
                $sellPrice = $this->getSellPriceFromBuyPrice($product['troll_buy_price']);
                $repriceRuleId = Repricer::RR_TROLL_BUY_PRICE_LOWER_BOUND;
            } else {
                $sellPrice = $product['sellery_sell_price'];
                $repriceRuleId = Repricer::RR_SET_TO_SELLERY_PRICE;
            }
        } else {
            if ($product['troll_buy_price']) {
                $sellPrice = $this->getSellPriceFromBuyPrice($product['troll_buy_price']);
                $repriceRuleId = Repricer::RR_TROLL_BUY_PRICE_LOWER_BOUND;
            } else {
                // in order words, do not change the price
                $sellPrice = $product['cc_sell_price'];
            }
        }
        return [$sellPrice, $repriceRuleId];
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


}