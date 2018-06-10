<?php

namespace Application;

use Zend\Log\Logger;

abstract class Repricer
{
    protected $debug;
    protected $logger;
    protected $config;

    public const RR_NO_CHANGE = 1;
    public const RR_BEAT_TROLL_CHEAP = 2;
    public const RR_BEAT_TROLL_EXPENSIVE = 3;
    public const RR_MATCH_CHEAPEST_EVO = 4;
    public const RR_SET_TO_SELLERY_PRICE = 5;
    public const RR_TROLL_BUY_PRICE_LOWER_BOUND = 6;
    public const RR_SELL_PRICE_FLOOR = 7;
    public const RR_EXCEPTION_SELL_PRICE_FLOOR = 8;
    public const RR_HOLD_ALL = 9;
    public const RR_RELEASE_FROM_HOLD_PRICE_UP = 10;
    public const RR_OOS_TRY_PRICE_DOWN_NO_CHANGE = 11;

    public function getConfig()
    {
        return include(__DIR__ . '/../config/repricer.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    /***********************************************
     * Create the repository object, make a connection to the database, and confirm the table is built correctly.
     * exit if the table is not built correctly.
     *
     * Databases constructor.
     * @param Logger $logger
     * @param bool $debug
     */
    public function __construct(Logger $logger, $debug = false)
    {
        $this->debug = $debug;
        $this->logger = $logger;
        $this->setConfig();

    }

    /*
     * Takes an array of products, and returns a different array of repriced products
     * */
    abstract public function calculatePrices(Array $array);

    /*
     * Takes an array of repriced products, writes them to the log table, and prints out
     * information to the run log.
     * */
    abstract public function markProductsUpdated(Array $array);

    protected function saveToRepository(Array $productsToUpdate, Databases $repository)
    {
        return $repository->importFromArray($productsToUpdate);
    }

    protected function printToLog(Array $productsToUpdate)
    {
        $header = "";
        $headerWasPrinted = false;
        foreach ($productsToUpdate as $key => $product) {
            $message = "";
            foreach ($product as $label => $productDetail) {
                if ($label == 'reprice_rule') {
                    // Print the reprice rule text
                    $message .= $this->config['repriceRuleList'][$productDetail] . " : ";
                } else {
                    $message .= "$productDetail : ";
                }
                $header .= "$label : ";
            }
            if (!$headerWasPrinted) {
                $headerWasPrinted = true;
                $this->logger->info($header);
            }
            $this->logger->info($message);
        }
    }

    protected function roundPrice($price)
    {
        $price = round($price, 2, PHP_ROUND_HALF_DOWN);
        return $price;
    }

}