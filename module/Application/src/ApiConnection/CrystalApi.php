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
use Application\ApiConnection\CrystalApi\TokenHolder;
use Zend\Log\Logger;

abstract class CrystalApi extends ApiConnection
{
    protected $logger;
    protected $debug;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/crystal-api.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
        $this->config['uid'] = getenv('CRYSTAL_COMMERCE_UID');
    }

    /**
     * CrystalApi constructor.
     * @param Logger $logger
     * @param $debug
     * @throws \Exception
     */
    public function __construct(Logger $logger, $debug)
    {
        $this->logger = $logger;
        $this->debug = $debug;

        $this->setConfig();

        $this->authorizePostVariables = [ 'uid' => $this->config['uid'] ];
        if($this->authorize()){
            $this->logger->debug("Authorized CC API with token : " . TokenHolder::getToken());
        } else {
            throw new \Exception("Unable to authorize CC API");
        }

    }

    protected function authorize()
    {
        if (TokenHolder::getToken()) {
            return true;
        }

        $data["uid"] = $this->config['uid'];
        $apiResult = $this->crystalTransmit($this->config['authorizeUrl'], $data, 'POST');
        $result = json_decode($apiResult,true);
        if (isset($result['token']['token'])) {
            TokenHolder::setToken($result['token']['token']);
            return true;
        }
        $this->logger->err($apiResult);
        return false;
    }


    protected function crystalTransmit($url, $data = [], $method = 'PUT')
    {
        if (TokenHolder::getToken()) {
            $data["access_token"] = TokenHolder::getToken();
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


}