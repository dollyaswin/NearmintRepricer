<?php

/************************************************
 * This class is handles all functions related to connecting to https://www.crystalcommerce.com/
 *  Sometimes referred to in the code as 'CC'
 *
 *  Crystal Commerce is a shopping cart, but it is mostly used for it's inventory controls and native
 *  integration with Ebay, Amazon Marketplace and TCGPlayer (another price comparison engine).
 *************************************************/

namespace Application\ApiConnection;

use Application\ApiConnection;
use Zend\Log\Logger;

class CrystalApi extends ApiConnection
{
    protected $logger;
    protected $debug;

    protected $token;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/crystal-api.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
        $this->config['uid'] = getenv('CRYSTAL_COMMERCE_UID');
    }

    public function __construct(Logger $logger, $debug)
    {
        $this->logger = $logger;
        $this->debug = $debug;

        $this->setConfig();

        $this->authorizePostVariables = [ 'uid' => $this->config['uid'] ];
        if($this->authorize()){
            $this->logger->debug("Authorized CC API with token : " . $this->token);
        } else {
            throw new \Exception("Unable to authorize CC API");
        }

    }

    protected function authorize()
    {
        $data["uid"] = $this->config['uid'];
        $apiResult = $this->crystalTransmit($this->config['authorizeUrl'], $data, 'POST');
        $result = json_decode($apiResult,true);
        if (isset($result['token']['token'])) {
            $this->token = $result['token']['token'];
            return true;
        }
        $this->logger->err($apiResult);
        return false;
    }


    protected function crystalTransmit($url, $data = [], $method = 'PUT')
    {
        if ($this->token) {
            $data["access_token"] = $this->token;
        }

        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n".
                             "Referer: nearmintgames.com\r\nUser-Agent: php script",
                'method'  => $method,
                'content' => http_build_query($data),
            ],
        ];

        $context  = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    public function getInventories()
    {
        $postVariables = [
            "in_stock_only" => "true",
            "name" => "CT13-EN003",
            "page" => "1",
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
        $url = $this->config['baseUrl'] . '/managed_inventories';
        $result = $this->crystalTransmit($url, null, 'GET');

        return $result;
    }



}