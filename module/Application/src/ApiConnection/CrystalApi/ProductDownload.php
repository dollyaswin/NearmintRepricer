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

    private function flattenProductArray($productArray)
    {
        $flatArray = [];

        $desiredParameters = [
            'quantity',
            'product_id',
            'id',   // managed product Id
            'sell_price',
            'buy_price',
            'condition',
            'language',
            'product_name',
            'product_details' => [
                'product_type',
                'default_weight',
                'category',
                'category_id' => [
                    '$oid',   // This is the category ID that is needed
                ],
            ],
        ];

        return $flatArray;
    }

}