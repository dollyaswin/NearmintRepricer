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
        // Look for strings in the sign in page which indicate that you are still signed in from last time.
        if (strpos($result, 'You are already signed in.') !== false ||
            strpos($result, 'Signed in!') !== false) {
            //already signed in
            //print "already signed in";
            return false;
        }
        if (strpos($result, 'AdminPanel: Dashboard') !== false) {
            //already signed in
            //print "already on dashboard";
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
        //$result = $this->loadTestPage();

        //print $result;

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
            'search[any_product_type]' => '1',
            'search[buy_price_gte]' => '',
            'search[buy_price_lte]' => '',
            'search[buy_price_sell_price_operator]' => '',
            'search[buy_price_sell_price_perc]' => '',
            'search[category_ids_with_descendants][]' => '',
            'search[manufacturer_sku_eq]' => '',
            'search[msrp_gte]' => '',
            'search[msrp_lte]' => '',
            'search[name_like]' => 'dog',
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
            'search[total_qty_gte]' => '0',
            'search[total_qty_lte]' => '',
            'search[variants_has_reserved_qty]' => '0',
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

        if ($inStockOnly) {
            // Search quantity gte = Greater than or Equals, lte = Less than or equals
            $postVariables['search[total_qty_gte]'] = 1;
            $postVariables['search[total_qty_lte]'] = '';
        }

        $headers = [
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "accept-encoding: gzip, deflate, br",
            "accept-language: en-US,en;q=0.9",
            "cache-control: no-cache",
            "content-type: multipart/form-data",
            //"cookie: liveagent_oref=https://accounts.crystalcommerce.com/users/sign_in; liveagent_ptid=4f90aec1-ce86-4d23-b382-f24512cd4f87; liveagent_sid=4c4bff96-fe60-4704-a2d9-34a7d7dedf27; liveagent_vc=43; __utmt=1; __utma=250373076.1858490364.1508204602.1508976759.1509666949.6; __utmb=250373076.13.10.1509666949; __utmc=250373076; __utmz=250373076.1508785118.3.2.utmcsr=accounts.crystalcommerce.com|utmccn=(referral)|utmcmd=referral|utmcct=/users/sign_in; intercom-session-iq6g9kms=M0R4QkdsUjFqdzk5dUVOalpHa1pyNGd2ZlZQU2hlUmtHQ1BGS3RjSVNUMW9JalhlV2UxVkt6U0k3QXpkQTlBdi0tUmlnTURlRWJRRE90bFZsTisrRUl4QT09--6e36da0ae9ce351a217a306e75b076ecb1a5bb06; _admin_session=40239ff170a5f3ecb2e8533df97b221e",
            "origin: https://nearmintgames-admin.crystalcommerce.com",
            //"postman-token: 73f0bc93-e4be-97f3-d667-799fe002af0f",
            "referer: https://nearmintgames-admin.crystalcommerce.com/inventory?overview=1",
            "upgrade-insecure-requests: 1",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36"
        ];

        $result = $this->transmit($url, $postVariables, $headers);
        // TODO add error handling here

        $pageHtml = $this->getFileReportPageHtml();

        $downloadId = $this->parsePageForLatestDownloadId($pageHtml);
        if (!$downloadId) {
            throw new \Exception("Something went wrong. There are no new downloads available. $pageHtml ");
        }
        sleep(10);
        $isReady = $this->checkForLinkForDownloadId($downloadId);

        $result = false;
        if ($isReady) {
            // Once the link appears it will be in the following format :
            $url = 'https://' . $this->config['adminDomain'] . '.crystalcommerce.com/file_reports/' . $downloadId . '/download';
            $result = $this->transmit($url);
        }

        file_put_contents($this->config['tempFileName'],$result);
        return $result;

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
            print ("File not ready.  Sleeping for {$this->config['sleepBetweenFileReportChecks']} Seconds." . PHP_EOL);
            sleep($this->config['sleepBetweenFileReportChecks']);
        }
        return false;
    }



}