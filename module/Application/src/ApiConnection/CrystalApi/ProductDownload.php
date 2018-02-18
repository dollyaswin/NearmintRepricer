<?php

namespace Application\ApiConnection\CrystalApi;


use Application\ApiConnection\CrystalApi;

class ProductDownload extends CrystalApi
{
    public function downloadProducts($pageNumber = 1, $priceMin = '')
    {
        $url = $this->config['baseUrl'] . '/inventories';
        $data = [
            'in_stock_only' => "true",
            'collection_name' => "nearmintgames",
            'page' => "$pageNumber",
            'per_page' => "25",
        ];

        $result = $this->crystalTransmit($url, $data,'GET');
        if (empty($result)) {
            return false;
        }
        $this->logger->info($result);
        $resultArray = json_decode($result, true);

        if (isset($resultArray['inventories'])) {
            return $this->flattenProductArray($resultArray['inventories']);
        }
        return false;
    }

    protected $mapping = [
        'Product Id' => 'product_id',
        'Inventory Id' => 'id',
        'Product Name' => 'product_name',
        'Total Qty' => 'quantity',
        'Buy Price' => 'buy_price',
        'Sell Price' => 'sell_price',
        'condition' => 'condition',
        'language' =>  'language',
    ];


    private function flattenProductArray($products)
    {
        $flatArray = [];

        foreach ($products as $product) {
            $currentProduct = [];
            foreach ($this->mapping as $databaseKey => $apiKey ) {
                $currentProduct[$databaseKey] = $product[$apiKey] ?? '';
            }
            $currentProduct['product_type'] = $product['product_details']['product_type'];
            $currentProduct['Category Id'] = $product['product_details']['category_id']['$oid'];
            $currentProduct['Category'] = $product['product_details']['category'];

            $flatArray[] = $currentProduct;
        }

        return $flatArray;
    }






}