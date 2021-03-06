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

class CrystalCommerce extends ApiConnection
{
    protected $authorizePostVariables;

    /**
     * @var Logger
     */
    protected $logger;

    protected $debug;

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

    public function __construct($logger, $debug)
    {
        $this->logger = $logger;
        $this->debug = $debug;

        $this->setConfig();
        if ($this->setAuthorizeVariables()) {
            $apiResult = $this->authorize();
        }


    }

    protected function setAuthorizeVariables()
    {
        $url = 'https://accounts.crystalcommerce.com/users/sign_in';
        $result = $this->transmit($url);
        // Look for strings in the sign in page which indicate that you are still signed in from last time.
        if (strpos($result, 'You are already signed in.') !== false ||
            strpos($result, 'Signed in!') !== false) {
            //already signed in
            $this->logger->debug("already signed in");
            return false;
        }
        if (strpos($result, 'AdminPanel: Dashboard') !== false) {
            //already signed in
            $this->logger->debug("already on dashboard");
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
     * This saves the page returned from the upload to a file.
     *
     * @param string $mode must be either 'update_only', 'only_create', or empty for both update and create
     *
     * @return bool success or failure to upload the file.
     ********************************************************/
    public function uploadFileToImportForm($mode = 'only_update')
    {
        // This string replace handles testing on windows but running on linux
        $filePath = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $this->config['fileToUploadPath']);
        $filePath = realpath($filePath);

        if (!file_exists($filePath)) {
            $this->logger->err("File to Upload doesn't exist" );
            return false;
        }


        //$url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/mass_imports';

        $url = "localhost:8080/upload";

        $curlFile = curl_file_create($filePath, 'text/csv', basename($filePath));


        $postVariables = [
            'commit'              => 'Mass Import',
            'multiple_categories' => 1,
            'import'              =>  $curlFile,  // This is where the file goes.
            'match_products_by'   =>  'name',
            'mode'                =>  $mode,
            'category_id'         =>  '',
            'utf8'                => '✓',
        ];

        $headers = [
            "accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "accept-encoding: deflate, br",
            "accept-language:en-US,en;q=0.9",
            "content-type: multipart/form-data",
            "origin: https://nearmintgames-admin.crystalcommerce.com",
            "referer: https://nearmintgames-admin.crystalcommerce.com/mass_imports",
            "upgrade-insecure-requests: 1",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36",
        ];

        $result = $this->transmit($url, $postVariables, $headers, $url);

        if ($result) {
            $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->config['fileUploadOutputLoggingPath']);
            file_put_contents($filePath, $result);
            if(strpos($result,'Your import has been enqueued.') !== false) {
                return true;
            }
            $this->logger->err("Something went wrong with the import. Please check " . $this->config['fileUploadOutputLoggingPath'] . " for more information" );
        }

        return $result;
    }

    /**********************
     * Creates a CSV file from an array
     * using the keys as headers.
     *
     * @param array $dataArray array of records, which are arrays with keys that match Crystal Commerce Column Names.
     * @param string $filePath optional file name to avoid using the default.
     **********************************/
    public function createFileForImport($dataArray, $filePath = '')
    {
        if (empty($filePath)) {
            $filePath = $this->config['fileToUploadPath'];
        }

        // This string replace handles testing on windows but running on linux
        $filePath = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $filePath);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $fp = fopen($filePath, 'w');
        fputcsv($fp, array_keys(array_values($dataArray)[0])); // write headers
        foreach ($dataArray as $record) {
            fputcsv($fp, $record);
        }
        fclose($fp);
    }

    public function updateProductPrices($productArray, $retry = true)
    {
        $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/inventory/update_multiple';

        $postVariables =[];

        foreach ($productArray as $product) {
            $postVariables["products[{$product['productId']}][buy_price]"] = $product['buy_price_new'];
            $postVariables["products[{$product['productId']}][sell_price]"] = $product['sell_price_new'];
        }

        $postVariables['save_products'] = 'Save';

        $headers = [
            "accept: text/javascript, text/html, application/xml, text/xml, */*",
            "accept-encoding: deflate, br",
            "accept-language: en-US,en;q=0.9",
            "content-type: application/x-www-form-urlencoded; charset=UTF-8",
            "origin: https://nearmintgames-admin.crystalcommerce.com",
            "referer: https://nearmintgames-admin.crystalcommerce.com/inventory/update",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36",
            "X-Prototype-Version:1.7_rc2",
            "X-Requested-With:XMLHttpRequest",
        ];

        $result = $this->transmit($url, $postVariables, $headers);

        //Result should contain "Changes to 1 product have been made successfully." in a successful update.
        if(strpos($result, 'have been made successfully') !== false) {
            // success
            return true;
        }

        // after first failure, sleep and try again once, and only once.
        if ($retry) {
            $this->logger->info("Failed on First Attempt. Sleeping and trying again.");
            sleep(10);
            return $this->updateProductPrices($productArray, false);
        }
        $this->logger->warn("The update was not successful " . $result);
        //failure
        return false;

    }



    public function downloadCsv($includeOutOfStock = false)
    {

        $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/inventory/update';

        $postVariables = [
            'commit' => 'Export to CSV',
            'form_action' => '',
            'metrics_search[avg_price_perc]' => '',
            'metrics_search[avg_price_perc_operator]' => 'within',
            'metrics_search[perc_of]' => 'avg',
            'page' => '1',
            'per_page_number' => '20',
            'search[any_product_type]' => '0',
            'search[any_product_type]' => '1',  // I know this value does nothing, but the website sets it twice.
            'search[buy_price_gte]' => '',
            'search[buy_price_lte]' => '',
            'search[buy_price_sell_price_operator]' => '',
            'search[buy_price_sell_price_perc]' => '',
            'search[category_ids_with_descendants][]' => '',
            'search[manufacturer_sku_eq]' => '',
            'search[msrp_gte]' => '',
            'search[msrp_lte]' => '',
            'search[name_like]' => '',
            'search[order_qty_is][action]' => 'bought',
            'search[order_qty_is][days]' => '',
            'search[order_qty_is][operator]' => '>',
            'search[order_qty_is][qty]' => '',
            'search[pos_barcode_eq]' => '',
            'search[product_type_id_eq]' => '',
            'search[qty_and_opt_qty_operator]' => '',
            'search[sell_price_gte]' => '',
            'search[sell_price_lte]' => '',
            'search[tags_name_eq]' => '',
            'search[total_qty_gte]' => '',
            'search[total_qty_lte]' => '',
            'search[variants_has_reserved_qty]' => '',
            'search[variants_locked_by_reserved_qty]' => '0',
            'search[variants_on_buylist]' => '0',
            'search[variants_opt_qty_gte]' => '',
            'search[variants_opt_qty_lte]' => '',
            'search[variants_qty_gte]' => '',
            'search[variants_qty_lte]' => '',
            'search[variants_use_defaults_eq]' => '',
            'search[wishes_count_gte]' => '',
            'search[wishes_count_lte]' => '',
            //'utf8' => "✓",   // '&#x2713;'
        ];

        if (!$includeOutOfStock) {
            // Search quantity gte = Greater than or Equals, lte = Less than or equals
            $postVariables['search[total_qty_gte]'] = '1';
            $postVariables['search[total_qty_lte]'] = '';
        }

        $headers = [
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "accept-encoding: gzip, deflate, br",
            "accept-language: en-US,en;q=0.9",
            "cache-control: no-cache",
            "content-type: multipart/form-data",
            "origin: https://nearmintgames-admin.crystalcommerce.com",
            "referer: https://nearmintgames-admin.crystalcommerce.com/inventory?overview=1",
            "upgrade-insecure-requests: 1",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36"
        ];

        $result = $this->transmit($url, $postVariables, $headers);
        // TODO add error handling here

        $pageHtml = $this->getFileReportPageHtml();

        $downloadId = $this->parsePageForLatestDownloadId($pageHtml);
        if (!$downloadId) {
            $this->logger->err("Something went wrong. There are no new downloads available. $pageHtml ");
            return false;
        }
        sleep(10);
        $isReady = $this->checkForLinkForDownloadId($downloadId);

        if ($isReady) {
            // Once the link appears it will be in the following format :
            $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/file_reports/' . $downloadId . '/download';
            $this->downloadToFile($url, $this->config['tempFileName']);
            return file_get_contents($this->config['tempFileName']);
        } else {
            $this->logger->err("After the maximum amount of wait time there was still no file available.");
            return false;
        }



    }

    public function getMostRecentCsvAsArray()
    {
        $fp = fopen($this->config['tempFileName'], 'r');
        $headerRow = fgetcsv($fp);
        $csvArray = [];
        while ($line = fgetcsv($fp)) {
            $csvArray[] = array_combine($headerRow, $line);
        }
        return $csvArray;
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
            $this->logger->err("File not ready.  Sleeping for {$this->config['sleepBetweenFileReportChecks']} Seconds.");
            sleep($this->config['sleepBetweenFileReportChecks']);
        }
        return false;
    }





}