<?php

namespace Application\Repricer;

use Application\Databases\LastEvoPriceUpdateRepository;
use Application\Repricer;
use Zend\Log\Logger;

class TrollEvo extends Repricer
{

    protected $warningBuyPriceMultiplier = 1.3;

    public function calculatePrices(Array $productsToUpdate)
    {
        $priceUpdates = [];

        foreach($productsToUpdate as $key => $product) {

            list($sellPrice, $repriceRuleId) = $this->calculateEvoSellPrice($product);

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
                $repriceRuleId =
                // record new quantity
                $quantity += $product['evo_hold_quantity'] - $holdQuantity;
            }

            // If the current live price is the lowest price, put all on hold instead
            if ($product['evo_sell_price'] == $product['lowest_evo_competitor_sell_price']) {
                $holdQuantity = $product['evo_hold_quantity'] + $product['evo_quantity'];
                $sellPrice = $product['evo_sell_price'];
                $repriceRuleId = Repricer::RR_HOLD_ALL;
            }

            // Build Update Array
            $priceUpdates[$key]['product_detail_id'] = $product['product_detail_id'];
            $priceUpdates[$key]['product_name'] = $product['product_name'];
            $priceUpdates[$key]['reprice_rule'] = $repriceRuleId;

            $priceUpdates[$key]['sell_price_old'] = $product['evo_sell_price'];
            $priceUpdates[$key]['sell_price_new'] = $sellPrice;
            $priceUpdates[$key]['hold_qty_old'] = $product['evo_hold_quantity'];
            $priceUpdates[$key]['hold_qty_new'] = $holdQuantity;
            //For logging only, not actually updated
            $priceUpdates[$key]['quantity_old'] = $product['evo_quantity'];
            $priceUpdates[$key]['quantity_new'] = $quantity;
            $priceUpdates[$key]['evo_cost'] = $product['evo_cost'];
        }
        return $priceUpdates;
    }

    private function calculateEvoSellPrice($product)
    {
        $repriceRuleId = Repricer::RR_NO_CHANGE;
        $sellPrice = $product['evo_sell_price'];

        if ($product['troll_sell_price']) {
            if ($product['troll_sell_price'] < 10) {
                $sellPrice = $product['troll_sell_price'] - 0.10;
                $repriceRuleId = Repricer::RR_BEAT_TROLL_CHEAP;
            } else {
                $sellPrice = $product['troll_sell_price'] * 0.99;
                $repriceRuleId = Repricer::RR_BEAT_TROLL_EXPENSIVE;
            }
        }
        // lowest_evo_competitor_sell_price is actually lowest price and can include yourself
        // don't try to price below yourself
        if ($product['lowest_evo_competitor_sell_price'] && $product['lowest_evo_competitor_sell_price'] < $sellPrice) {
            $sellPrice = $product['lowest_evo_competitor_sell_price'];
            $repriceRuleId = Repricer::RR_BEAT_TROLL_EXPENSIVE;
        }
        // Sellery price being higher should override troll prices
        // In the future, only use this if Troll is sold out
        if ($product['sellery_sell_price'] && $product['sellery_sell_price'] > $sellPrice) {
            $sellPrice = $product['sellery_sell_price'];
            $repriceRuleId = Repricer::RR_SET_TO_SELLERY_PRICE;
        }
        // Buy price warning catch all. If you are about to price too low compared to troll Buy
        // Price above troll buy.
        if ($product['troll_buy_price']) {
            if ($sellPrice < (($product['troll_buy_price'] * $this->warningBuyPriceMultiplier) + 0.35 )) {
                $sellPrice = $product['troll_buy_price'] * $this->warningBuyPriceMultiplier + 0.35;
                $repriceRuleId = Repricer::RR_TROLL_BUY_PRICE_LOWER_BOUND;
            }
        }

        // Price Floor of $0.69 for anything not already priced below $0.59
        if ($sellPrice < 0.69 ) {
            if ($product['evo_sell_price'] > 0.59) {
                $sellPrice = 0.69;
                $repriceRuleId = Repricer::RR_SELL_PRICE_FLOOR;
            } else {
                $sellPrice = $product['evo_sell_price'];
                $repriceRuleId = Repricer::RR_EXCEPTION_SELL_PRICE_FLOOR;
            }
        }
        $sellPrice = $this->roundPrice($sellPrice);

        return [$sellPrice, $repriceRuleId];
    }

    /**
     * Update the database with  the buy price and sell price which were just sent to Crystal Commerce
     *
     * @param array $productsToUpdate numerically indexed array of products from Database
     * @return bool success or failure
     */
    public function markProductsUpdated(Array $productsToUpdate)
    {
        $this->printToLog($productsToUpdate);
        $repositoryLU = new LastEvoPriceUpdateRepository($this->logger, $this->debug);
        return $this->saveToRepository($productsToUpdate, $repositoryLU);
    }


}