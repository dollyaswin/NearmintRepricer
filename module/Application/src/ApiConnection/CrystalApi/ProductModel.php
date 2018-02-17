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


    /**
     *  Use the Crystal API to get the information about this product for this store
     *  and load it into this object
     *
     * @param string $managedInventoryId - The id for the product which is specific to this store.
     * @return bool success or failure to load this inventory id
     */
    public function loadProductByInventoryId($managedInventoryId)
    {
        $url = $this->config['baseUrl'] . '/managed_inventories/' . $managedInventoryId  . '.json';
        $data = [];
        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);

        if (isset($resultArray['inventory'])) {
            return $this->loadThisObjectFromArray($resultArray['inventory']);
        }
        return false;
    }


    /**
     * Use the Crystal API to get the information about this product for this store
     *  and load it into this object
     *
     * @param string $productId - The product's Id, not specific to this store
     * @return bool success or failure to load this product Id
     */
    public function loadProductByProductId($productId)
    {
        $url = $this->config['baseUrl'] . '/products/' . $productId  . '.json';
        $data = [
            'self_collection_only' => true,
            'id'                   => $productId,
        ];
        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);
        if (isset($resultArray['product']['inventories'][0])) {
            return $this->loadThisObjectFromArray($resultArray['product']['inventories'][0]);
        }
        return false;
    }

    private function loadThisObjectFromArray($productArray)
    {
        $this->managedInventoryId = $productArray['id'];
        $this->productId = $productArray['product_id'];
        $this->quantityAdjustment = 0;

        $this->currentQuantity =  $productArray['quantity'] ?? 0;
        $this->buyPrice        = $productArray['buy_price'] ?? 0;
        $this->sellPrice       = $productArray['sell_price'] ?? 0;
        $this->sellPriceWas    = $this->sellPrice;
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

}