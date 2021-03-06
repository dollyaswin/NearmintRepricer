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

class SellerEngine extends ApiConnection
{
    protected $logger;
    protected $debug;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/seller-engine.config.php');
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
        $this->setAuthorizeVariables();
        $apiResult = $this->authorize();

        if (!$apiResult) {
            throw new \Exception("Unable to authorize Seller Engine");
        }
    }

    protected function setAuthorizeVariables()
    {
        $this->authorizePostVariables = [
            'userName' => $this->config['username'],
            'user_name' => $this->config['username'],
            'password' => $this->config['password'],
            'login' => 'Login',
        ];
    }


    /***********************************************
     * Get URL for sellery download, then download to file.  Return file location.
     *
     * @param integer|bool $jumpToExportId
     * @return mixed
     **********************************************/
    protected function download($jumpToExportId)
    {
        $this->logger->debug("Inside " . __METHOD__ );

        $remoteUrl = $this->findNextFile($jumpToExportId);
        $this->downloadToFile($remoteUrl, $this->config['localFileLocation']);
        return($this->config['localFileLocation']);
    }

    /*****************************************************
     * The sellery Repricing engine can be configured to create a new report on a daily schedule.
     * These reports all have the same URL except for their key which is a simple auto incremented number.
     *
     * This code generates and tests the next URL to try
     *
     * @return string
     */
    private function findNextFile($jumpToExportId)
    {
        $this->logger->debug("Inside " . __METHOD__ );

        $settingsRepo = new RepricerSettingsRepository($this->logger, $this->debug);
        if ($jumpToExportId) {
            $downloadNumber = $jumpToExportId;
        } else {
            $downloadNumber = $settingsRepo->getSetting('sellery_download_number');
        }
        if (!$downloadNumber) {
            $downloadNumber = 50;
        }
        // move ahead a few numbers then work backwards
        $downloadNumber += 5;
        $highestDownload = false;

        do {
            $nextFileUrl = 'https://sellery.sellerengine.com/export/getContents?userId=' . $this->config['userIdForExport'] . '&exportId=' . $downloadNumber;
            // Downloads which do not exists yet will return a 500 internal server error,
            // which causes transmit to return false.
            if ($this->transmit($nextFileUrl)) {
                $highestDownload = $downloadNumber;
            } else {
                //keep sellery from noticing we are scraping, and banning us.
                sleep(5);
                $downloadNumber--;
            }
        } while (!$highestDownload && $downloadNumber > 0);

        $settingsRepo->setSetting('sellery_download_number', $highestDownload);
        return $nextFileUrl;
    }

    /**************************************************
     * Wrapper function for both downloading the most recent price file
     * and loading it into an array
     * @param integer|bool $jumpToExportId
     *
     * @return array of arrays with keys of the header row
     ***************************************************/
    public function downloadReportAndReturnArray($jumpToExportId = false)
    {
        $priceArray = [];
        $fileName = $this->download($jumpToExportId);
        $fileAsArray = $this->createArrayFromFile();
        foreach ($fileAsArray as $priceLine) {
            $priceArray[] = $priceLine;
        }
        return $priceArray;
    }

}

