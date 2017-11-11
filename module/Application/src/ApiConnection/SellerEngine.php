<?php

namespace Application\ApiConnection;

use Application\ApiConnection;
use Application\Databases\PricesRepository;

class SellerEngine extends ApiConnection
{
    protected $baseUrl = 'https://sellery.sellerengine.com/';
    protected $authorizeUrl = 'https://sellery.sellerengine.com/login';




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
    }

    protected function setAuthorizeVariables()
    {
        $this->authorizePostVariables = [
            'userName' => $this->config['username'],
            'user_name' => $this->config['username'],
            'password' => $this->config['password'],
            //'forward_url' => 'https://sellery.sellerengine.com/se2/',
            'login' => 'Login',
        ];
    }

    public function download()
    {
        $remoteUrl = $this->findNextFile();
        $this->downloadToFile($remoteUrl, $this->config['localFileLocation']);
        //$newFileName = dirname($this->config['localFileLocation']) . '/download' . date('Y-m-d.H.i.s') . '.csv';
        //copy ($this->config['localFileLocation'],  $newFileName);
        return($this->config['localFileLocation']);

    }

    private function findNextFile()
    {
        $repo = new PricesRepository();
        $downloadNumber = $repo->getMostRecentSelleryDownloadNumber();
        if (!$downloadNumber) {
            $downloadNumber = 10;
        }
        // move ahead a few numbers then work backwards
        $downloadNumber += 5;
        $highestDownload = false;

        do {
            $nextFileUrl = 'https://sellery.sellerengine.com/export/getContents?userId=' . $this->config['userIdForExport'] . '&exportId=' . $downloadNumber;
            if ($this->transmit($nextFileUrl)) {
                $highestDownload = $downloadNumber;
            } else {
                //keep sellery from banning us too quickly
                sleep(5);
                $downloadNumber--;
            }
        } while (!$highestDownload);

        $repo->setMostRecentSelleryDownloadNumber($highestDownload);
        return $nextFileUrl;
    }

    public function createArrayfromFile($fileName)
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

