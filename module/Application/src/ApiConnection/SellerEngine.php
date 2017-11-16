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
use Application\Databases\PricesRepository;

class SellerEngine extends ApiConnection
{

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/seller-engine.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct()
    {
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

    protected function download()
    {
        $remoteUrl = $this->findNextFile();
        $this->downloadToFile($remoteUrl, $this->config['localFileLocation']);
        // Todo Need to decide if we should keep historic files or not.
        //$newFileName = dirname($this->config['localFileLocation']) . '/download' . date('Y-m-d.H.i.s') . '.csv';
        //copy ($this->config['localFileLocation'],  $newFileName);
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
    private function findNextFile()
    {
        $repo = new PricesRepository();
        $downloadNumber = $repo->getMostRecentSelleryDownloadNumber();
        if (!$downloadNumber) {
            $downloadNumber = 50;
        }
        // move ahead a few numbers then work backwards
        $downloadNumber += 10;
        $highestDownload = false;

        do {
            $nextFileUrl = 'https://sellery.sellerengine.com/export/getContents?userId=' . $this->config['userIdForExport'] . '&exportId=' . $downloadNumber;
            // Downloads which do not exists yet will return a 500 internal server error,
            // which causes trasmit to return false.
            if ($this->transmit($nextFileUrl)) {
                $highestDownload = $downloadNumber;
            } else {
                //keep sellery from noticing we are scraping, and banning us.
                sleep(5);
                $downloadNumber--;
            }
        } while (!$highestDownload);

        $repo->setMostRecentSelleryDownloadNumber($highestDownload);
        return $nextFileUrl;
    }

    /*********************************
     * Read the CSV file downloaded from Sellery and turn it into a PHP Array
     * This is not scalable to over 1 million rows, but works for any normal number of products.
     *
     * @param string $fileName
     * @return array of arrays with keys of the header row
     ********************************/
    protected function createArrayfromFile($fileName)
    {
        $headerArray = [];
        $resultArray = [];
        $fp = fopen($fileName, 'r');
        while ($line = fgetcsv($fp)) {
            if (count($headerArray) == 0) {
                $headerArray = $line;
            } else {
                $resultArray[] = array_combine($headerArray, $line);
            }
        }
        fclose($fp);
        return $resultArray;

    }

    /**************************************************
     * Wrapper function for both downloading the most recent price file
     * and loading it into an array
     *
     * @return array of arrays with keys of the header row
     ***************************************************/
    public function downloadReportAndReturnArray()
    {
        $priceArray = [];
        $fileName = $this->download();
        $fileAsArray = $this->createArrayfromFile($fileName);
        foreach ($fileAsArray as $priceLine) {
            if ($priceLine['Live price on Near Mint Games'] > 0) {
                $priceArray[] = $priceLine;
            }
        }
        return $priceArray;
    }

}

