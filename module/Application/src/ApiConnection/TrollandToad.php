<?php

/************************************************
 * This class is handles all functions related to connecting to https://sellery.sellerengine.com
 *  Sometimes referred to in the code as 'Sellery'
 *
 *  Sellery is an app which will update your products' price on Amazon Marketplace automatically
 *  Based on your preferences set on the site.
 *************************************************/

namespace Application\ApiConnection;

use Application\ApiConnection;
use Application\Databases\RepricerSettingsRepository;
use Zend\Log\Logger;

class TrollandToad extends ApiConnection
{
    protected $logger;
    protected $debug;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/troll-and-toad.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct(Logger $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
        $this->setConfig();
        $this->transmit("https://www.trollandtoad.com/myaccount/logon.php?action=logout");

        $this->setAuthorizeVariables();
        $apiResult = $this->authorize();

        if (!$apiResult || strpos($apiResult, 'invalid') !== false) {
            throw new \Exception("Unable to authorize Troll And Toad ");
        }
    }

    protected function setAuthorizeVariables()
    {
        $this->authorizePostVariables = [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'action' => '',
            'firsttimeflag' => '',
        ];
    }

    /***********************************************
     * Get URL for sellery download, then download to file.  Return file location.
     *
     * @return string the File location string.
     **********************************************/
    protected function download($type)
    {
        $this->logger->debug("Inside " . __METHOD__ );

        $remoteUrl = $this->config['downloadType'][$type]['url'];
        $this->downloadToFile($remoteUrl, $this->config['localFileLocation']);
        return($this->config['localFileLocation']);
    }

     public function getBuyListArray()
     {
         $buyListsArray = [];
         foreach($this->config['downloadType'] as $type => $typeData) {
             $this->download($type);
             $buyList = $this->createArrayFromFile();
             foreach ($buyList as $index => $record) {
                 $buyList[$index]['Category'] = $typeData['categoryName'];
             }
             $buyListsArray = array_merge($buyListsArray, $buyList);
         }
         return $buyListsArray;
     }
     public function getEvoFileName()
     {
         return $this->config['localEvoFileLocation'];
     }

     public function evoDownload()
     {
         $this->logger->debug("Inside " . __METHOD__ );

         $postVariables = [
             'CSVDownload' => 'Download CSV',
         ];

         $fileDownload = $this->transmit($this->config['merchantInventoryUrl'],  $postVariables);
         file_put_contents($this->config['localEvoFileLocation'], $fileDownload);

         return($this->config['localEvoFileLocation']);
     }

}