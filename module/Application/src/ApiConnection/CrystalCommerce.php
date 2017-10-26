<?php
namespace Application\ApiConnection;

use Application\ApiConnection;

class CrystalCommerce extends ApiConnection
{
    protected $authorizePostVariables;


    /**
     * @var array
     */
    protected $config;

    public function getConfig()
    {
        return include(__DIR__ . '/../../config/crystal-commerce.config.php');
    }

    public function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct()
    {
        $this->setConfig();
        if ($this->setAuthorizeVariables()) {
            $apiResult = $this->authorize();
        }

    }

    protected function setAuthorizeVariables()
    {
        $url = 'https://accounts.crystalcommerce.com/users/sign_in';
        $result = $this->transmit($url);
        if (strpos($result, 'You are already signed in.') !== false) {
            //already signed in
            return false;
        }
        if (strpos($result, 'AdminPanel: Dashboard') !== false) {
            //already signed in
            return false;
        }
        $splitPage = explode('authenticity_token', $result);

        $truncatedPage = substr($splitPage[2],0, 2000);
        preg_match('/value="(.+)"/', $truncatedPage, $matches);
        $authenticityToken = $matches[1];
        if (strlen($authenticityToken) < 10 ) {
            $debugInfo = "Page returned: $result";

            throw new \Exception("Authenticity Token not found. The page may have changed. URL: $url \n 
            Headers : {$this->headers} \n
            Output: $debugInfo");
        }
        $this->authorizePostVariables['authenticity_token'] = $authenticityToken;
        $this->authorizePostVariables['user[email]'] = $this->config['useremail'];
        $this->authorizePostVariables['user[password]'] = $this->config['password'];
        $this->authorizePostVariables['commit'] = 'Sign In';
        return true;
    }

    public function loadTestPage()
    {
        $url = 'https://nearmintgames-admin.crystalcommerce.com/inventory?overview=1';
        return $this->transmit($url);

    }

    /*
     * If you are updating products with this import and there are fields you do not wish to update, set the value as $SKIP$ .
     *
     *
     * Product Name*	anything
        Description	anything
        Barcode	anything
        Store Only	Either Yes, No, or leave blank
        Manufacturer SKU	anything
        Weight	numeric
        Sell Price	dollar amount (ie. 3.99)
        Buy Price	dollar amount (ie. 1.49)
        Photo URL	URL of image for photo (only specify one per product name)
        MSRP	dollar amount (ie. 9.99)
        Max Qty	numeric
        Opt Qty	numeric
        ASIN	anything
        Tax Exempt	Either Yes, No, or leave blank
        Domestic Sale Only	Either Yes, No, or leave blank
        Category*	anything
    */


    /*
     * Add Qty	numeric
        Qty	numeric
        Infinite Qty	Either Yes, No, or leave blank
     * */


    /*********************************************************************************
     * Uploads the file into Crystal Commerce.  Assumes file is in the correct format.
     *
     * @param string $filePath - path to file which should be uploaded.
     * @param string $mode must be either 'update_only', 'only_create', or empty for both update and create
     *
     * @return bool success or failure to upload the file.
     ********************************************************/
    public function uploadFileToImportForm($filePath, $mode = '')
    {
        $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/mass_imports';

        $postVariables['multiple_categories'] = 1;
        $postVariables['import'] = $filePath;
        $postVariables['match_by'] = 'manufacturer_sku';
        $postVariables['mode'] = $mode;
        $postVariables['commit'] = 'Mass Import';

        $result = $this->transmit($url, $postVariables);
        // TODO add error handling here

        return true;

    }

    public function downloadCsv($inStockOnly = 'true')
    {
        $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/inventory/update';
        if ($inStockOnly) {
            // Search quantity gte = Greater than or Equals, lte = Less than or equals
            $postVariables['search[total_qty_gte]'] = 1;
            $postVariables['search[total_qty_lte]'] = '';
        }

        $postVariables['search[product_type_id_eq]'] = 1453; //  Just setting this in case the page is erroring without it.

        $postVariables['utf8'] = '&#x2713;';  //"✓";
        $postVariables['search[any_product_type]'] = 1;
        $postVariables['commit'] = 'Export to CSV';

        print("Post Variables: ");
        print_r ($postVariables);
        /*
        $getVars = '';
        $start = true;
        foreach ($postVariables as $label => $value) {
            if ($start) {
                $getVars .= '?';
                $start = false;
            } else {
                $getVars .= '&';
            }
            $getVars .= urlencode($label) . '=' . urlencode($value);
        }
        $url .= $getVars;
        */

        $result = $this->transmit($url);
        // TODO add error handling here

        print($url . PHP_EOL);
        print($result);
        exit();


        $pageHtml = $this->getFileReportPageHtml();

        $downloadId = $this->parsePageForLatestDownloadId($pageHtml);
        if (!$downloadId) {
            throw new \Exception("Something went wrong. There are no new downloads available. $pageHtml ");
        }

        $isReady = $this->checkForLinkForDownloadId($downloadId);

        $result = false;
        if ($isReady) {
            // Once the link appears it will be in the following format :
            $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/file_reports/' . $downloadId . '/download';
            $result = $this->transmit($url);
        }

        return $result;

    }

    protected function getFileReportPageHtml()
    {
        // Fetch this this page to get the Download ID and check if the link to the download exists
        $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/file_reports';
        $pageHtml = $this->transmit($url);
        return $pageHtml;
    }

    protected function parsePageForLatestDownloadId($pageHtml)
    {
        $downloadId = 0;
        $parts = explode('<h1>File Reports</h1>', $pageHtml);
        $tableAreaHTML = explode('<script>', $parts[1]);
        $dom = new \DOMDocument();
        $dom->loadHTML($tableAreaHTML[0]);
        $domNode = $dom->getElementsByTagName('td');
        foreach ($domNode as $node) {
            if (is_numeric($node->nodeValue) && $node->nodeValue > $downloadId) {
                $downloadId = $node->nodeValue;
            }
        }

        if ($downloadId > 0) {
            return $downloadId;
        }
        return false;
    }

    protected function checkForLinkForDownloadId($downloadId)
    {
        $attempts = 0;
        while($attempts < $this->config['attmptsToGetFileReportBeforeError']) {
            $pageHtml = $this->getFileReportPageHtml();
            if (strpos($pageHtml, 'file_report_' . $downloadId . '_download') !== false) {
                return true;
            }
            $attempts++;
            print ("File not ready.  Sleeping for {$this->config['sleepBetweenFileReportChecks']} Seconds." . PHP_EOL);
            sleep($this->config['sleepBetweenFileReportChecks']);
        }
        return false;
    }



}