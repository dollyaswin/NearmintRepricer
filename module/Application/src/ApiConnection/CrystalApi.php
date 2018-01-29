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

        $this->logger->info(print_r($this->authorizePostVariables, true));
        $this->logger->info($this->config['authorizeUrl']);

        $body = $this->authorize();
        $this->logger->info($body . " Header : " . $this->headers);
        exit();

    }
/*
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
        exit();
        return false;
    }
*/

    protected function crystalTransmit($url, $data = [], $method = 'PUT')
    {
        if ($this->token) {
            $data["access_token"] = $this->token;
        }
        $options = [
            'http' => [
                'header'  => [
                    'Referer: http://localhost:4200',
                    'User-Agent: php script'
                ],
                'method'  => $method,
                'content' => http_build_query($data),
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    public function getManagedInventories()
    {
        $url = 'https://crystal-api.crystalcommerce.com/api/managed_inventories';
        $result = $this->transmit($url, [], 'GET');
        return $result;
    }




}