<?php

namespace Application\ApiConnection;

use Application\ApiConnection;

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

    protected function downloadToFile($remoteUrl, $localFileLocaiton)
    {
        $ch = curl_init($remoteUrl);
        $fp = fopen($localFileLocaiton, "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    protected function findNextFile()
    {
        $nextFileUrl = 'https://sellery.sellerengine.com/export/getContents?userId=' . $this->config['userIdForExport'] . '&exportId=9';
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
        $hasPriceArray = [];
        $fileName = $this->download();
        $fileAsArray = $this->createArrayfromFile($fileName);
        $count =0;
        foreach ($fileAsArray as $priceLine) {
            if ($priceLine['Live price on Near Mint Games'] > 0) {
                $hasPriceArray[] = $priceLine;
            }
            $count++;
            if ($count > 30) {
                break;
            }
        }
        return $hasPriceArray;
    }




}
