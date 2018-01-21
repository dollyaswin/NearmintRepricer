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
    }

    public function __construct($logger, $debug)
    {
        $this->logger = $logger;
        $this->debug = $debug;

        $this->setConfig();
        if ($this->setAuthorizeVariables()) {
            $apiResult = $this->authorize();
            $result = json_decode($apiResult,true);
            if (isset($result['token']['token'])) {
                $this->token = $result['token']['token'];
            } else {
                throw new \Exception("Unable to connect to Crystal API. Token not set in response." . $apiResult);
            }
        }
    }

    protected function setAuthorizeVariables()
    {
        $this->authorizePostVariables = [
            'uid' => $this->config['uid'],
        ];
        return true;
    }


}