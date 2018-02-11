<?php

namespace Application\ApiConnection\CrystalApi;

use Application\ApiConnection\CrystalApi;

class ProductModel extends CrystalApi
{
    // this does not reset
    private $userCollectionId;

    // These must be set for each product
    private $managedInventoryId;  // refers to this product for this store,  ie managed by this store
    private $productId; // refers to the product for all stores
    private $quantityAdjustment = 0; //default 0, because price updates require this is set.

    // these must be submitted in cents, but are stored here in dollars as decimal
    private $buyPrice;
    private $sellPrice;
    private $sellPriceWas; // Required for submission to update a product

    private $currentQuantity; // maintained in this object in order to self regulate inventory above 0



    public function loadProduct($managedInventoryId)
    {
        $url = $this->config['baseUrl'] . '/managed_inventories/' . $managedInventoryId  . '.json';
        $data = [];
        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);
        $product = $resultArray['product'] ?? false;
        if (empty($product['id'])) {
            return false;
        }
        $this->managedInventoryId = $managedInventoryId;
        $this->productId = $product['id'];
        $this->quantityAdjustment = 0;
        $this->currentQuantity =  $product['inventories'][0]['quantity'] ?? 0;
        // Prices must be converted from cents to dollars
        $this->buyPrice        = ($product['inventories'][0]['pricing']['buy_price_cents'] ?? 0 ) / 100;
        $this->sellPrice       = ($product['inventories'][0]['pricing']['sell_price_cents'] ?? 9999999) / 100;
        $this->sellPriceWas    = ($product['inventories'][0]['pricing']['sell_price_cents_was'] ?? 999999) / 100;

        return true;
    }

    public function setSellPrice($price)
    {
        $this->sellPrice = $price;
    }

    public function setBuyPrice($buyPrice)
    {
        $this->buyPrice = $buyPrice;
    }

    /************************************
     * Can be called multiple times before putting the product
     * Uses the current quantity to track adjustments to make sure it did not go below 0.
     *
     * @param int $adjustment
     * @return boolean - if the adjustment was valid based on known information
     ************************************/
    public function updateQuantity(integer $adjustment)
    {
        if ($this->currentQuantity + $adjustment >= 0) {
            $this->quantityAdjustment += $adjustment;
            $this->currentQuantity += $adjustment;
            return true;
        }
        return false;
    }

    /**
     * Transmit the current product back to crystal commerce
     *
     * @return bool
     */
    public function putProduct()
    {
        $url = $this->config['baseUrl'] . '/managed_inventories/' . $this->managedInventoryId;
        $data = [
            'inventory' => [
                'user_collection_id' => $this->userCollectionId,
                'quantity'           => $this->quantityAdjustment,
                'product_id'         => $this->productId,
                'pricing' => [
                    'sell_price_cents'     => intval($this->sellPrice * 100),
                    'buy_price_cents'      => intval($this->buyPrice * 100),
                    'sell_price_cents_was' => intval($this->sellPriceWas * 100),
                ]
            ]
        ];
        $result = $this->crystalTransmit($url, $data,'PUT');
        if ($result) {
            // reset data in case this product is updated and put again.
            $this->sellPriceWas = $this->sellPrice;
            $this->quantityAdjustment = 0;
            return true;
        }
        return false;
    }

    private function loadManagedInventoryId()
    {
        $url = $this->config['baseUrl'] . '/managed_inventories';
        $result = $this->crystalTransmit($url, null, 'GET');
    }



    //==================================================================


    public function getInventories()
    {
        $postVariables = [
            "in_stock_only" => "true",
            "name" => "CT13-EN003",
            "collection_name" => "nearmintgames",
        ];

        $url = $this->config['baseUrl'] . '/inventories';
        $result = $this->crystalTransmit($url, $postVariables, 'GET');

        return $result;
    }

    public function getProductById($productId)
    {
        $url = $this->config['baseUrl'] . '/products/' . $productId . '.json';
        $result = $this->crystalTransmit($url, [], 'GET');

        return $result;
    }


    public function getMyManagedInventoryId()
    {


        return $result;
    }
}