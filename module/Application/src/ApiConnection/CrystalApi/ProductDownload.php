<?php

namespace Application\ApiConnection\CrystalApi;


use Application\ApiConnection\CrystalApi;

class ProductDownload extends CrystalApi
{

    /**
     * Use the /api/inventories API in order to get a group of products from the
     * Crystal Commerce account and turn it into an array
     *
     * @param int $pageNumber
     * @param string $priceMin
     * @return array|bool
     */
    public function downloadProducts($pageNumber = 1, $priceMin = '')
    {
        $url = $this->config['baseUrl'] . '/inventories';
        $data = [
            'in_stock_only' => "true",
            'collection_name' => "nearmintgames",
            'page' => "$pageNumber",
            'per_page' => "100",
        ];

        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);

        if (isset($resultArray['inventories'])) {
            return $this->mapProductArray($resultArray['inventories']);
        }
        return false;
    }

    protected $inventoryMapping = [
        'Product Id' => 'product_id',
        'Inventory Id' => 'id',
        'Product Name' => 'product_name',
        'Total Qty' => 'quantity',
        'Buy Price' => 'buy_price',
        'Sell Price' => 'sell_price',
        'condition' => 'condition',
        'language' =>  'language',
        'Category Id' => 'category_id',
        'Category' =>  'category_name',
        'product_type' => 'product_type',
    ];


    private function mapProductArray($products)
    {
        $flatArray = [];

        foreach ($products as $product) {
            $currentProduct = [];
            foreach ($this->inventoryMapping as $databaseKey => $apiKey ) {
                $currentProduct[$databaseKey] = $product[$apiKey] ?? '';
            }
            /*
             * API call to get ASIN is currently returning an error on CC side.
            $currentProduct['asin'] = $this->getAsinUsingProductId($product['product_id']);
            $this->logger->debug($currentProduct['asin'] . ' ASIN was found for product id ' . $product['product_id']);
            */

            $flatArray[] = $currentProduct;
        }
        return $flatArray;
    }

    /**
     * Use the products API solely in order to get the ASIN.
     *
     * @param string $productId Crystal Commerce Product Id
     * @return string|bool  false on failure, the ASIN string on success.
     */
    private function getAsinUsingProductId($productId)
    {
        $url = $this->config['baseUrl'] . '/products/' . $productId . '.json';
        $data = [];

        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);

        return $resultArray['product']['identifiers']['asin'] ?? false;

    }







}