<?php
namespace Application\Controller;

use Application\ApiConnection\CrystalCommerce;
use Application\Databases\PricesRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * This is the controller class for managing file downloads.
 */
class DownloadController extends AbstractActionController
{

    protected $config;


    /**
     * @var \Zend\Log\Logger
     */
    protected $logger;

    protected $debug;


    private function getConfig()
    {
        return include __DIR__ . '/../../config/download.config.php';
    }

    private function setConfig()
    {
        $this->config = $this->getConfig();
    }

    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        $this->debug = $this->params()->fromQuery('debug', false);
        $this->setConfig();
        $this->logger = LoggerFactory::createLogger('downloadLog.txt', false, $this->debug);

    }

    protected $dropDowns = [
        'crystal_commerce^category_name' => 'Category Name',
    ];

    protected $checkBoxes = [
        'selleryData' => [
            'label' => 'Require Sellery Data',
            'checked' => true,
        ],
        'quickUploadOnly' => 'Create Fields For Upload Only',
        'changesOnly' => 'Show only prices which are > 2% and > $0.05 different',
        'trollBuyInfo' => 'Show Troll and Toad Buy Price Info if available',
        'trollBuyRestrict' => 'Require Troll and Toad Buy Price Info',
    ];

    public function indexAction()
    {

        $searchBoxes = [
            'daysLimit' => 'Updated in last Number of Days',
        ];

        if (!empty($this->dropDowns)) {
            $dropDowns = $this->buildDropDowns($this->dropDowns);
        }

        if (!empty($this->checkBoxes)) {
            $checkboxes = $this->buildCheckBoxes($this->checkBoxes);
        } else {
            $checkboxes =[];
        }

        $variables = [
            'checkboxes' => $checkboxes,
            'dropDowns' => $dropDowns,
            'searchBoxes' => $searchBoxes,
        ];

        // Show list of possible downloads and options. Link to the actions below.
        return new ViewModel($variables);
    }

    protected function buildCheckBoxes($checkboxes)
    {
        $outputArray = [];
        foreach ($checkboxes as $parameterName => $displayName) {
            $checked = '';
            if (is_array($displayName)) {
                $checked = $displayName['checked'] ? 'checked' : '';
                $displayName = $displayName['label'];
            }
            $outputArray[]  = "<input type='checkbox' name='$parameterName' value='true' $checked> - $displayName ";
        }
        return $outputArray;
    }

    /****************
     * Build the HTML needed for form drop down options.
     *
     * @param array $dropDowns an array of table_name.column_name keys, and Display Name for values.
     * @return array of strings which are Html for select boxes
     */
    protected function buildDropDowns($dropDowns)
    {
        $outputArray = [];
        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        foreach ($dropDowns as $columnName => $displayText) {
            $optionsArray = $pricesRepo->getOptionsForColumn($columnName);

            if ($optionsArray) {
                $selector = "$displayText : <select name='$columnName'> ";
                $selector .= "<option value='' selected >Choose A $displayText</option>";
                foreach($optionsArray as $option) {
                    $optionName = $option['option_name'];
                    $selector .= "<option value='$optionName' >$optionName</option>";
                }
                $selector .= '</select>';
                $outputArray[] = $selector;
            }
        }
        return $outputArray;
    }

    /**
     *  Process the Form POST action to create a CSV file from the database
     *  and return that file to the user.
     *
     * @return bool|\Zend\Stdlib\ResponseInterface|ViewModel
     */
    public function pricesToUpdateAction()
    {
        if(!$this->getRequest()->isPost()) {
            $this->redirect()->toUrl('/download');
        }

        $daysLimit = $this->params()->fromPost('daysLimit', false);

        $checkBoxParameters = [];
        if (!empty($this->checkBoxes)) {
            foreach ($this->checkBoxes as $column => $displayName) {
                $setting = $this->params()->fromPost($column, '');
                if ($setting) {
                    $checkBoxParameters[$column] = $setting;
                }
            }
        }

        $dropDownParameters = [];
        // Filtering here, only drop downs from the list will be processed,
        // no values from the form allowed as column names.
        if (!empty($this->dropDowns)) {
            foreach ($this->dropDowns as $column => $displayName) {
                $setting = $this->params()->fromPost($column, '');
                if ($setting) {
                    $dropDownParameters[$column] = $setting;
                }
            }
        }

        // Get data from mysql
        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        $pricesArray = $pricesRepo->getRecords($dropDownParameters, $checkBoxParameters, $daysLimit);

        $downloadPath = $this->config['tempDownloadName'];
        $downloadPath = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $downloadPath);

        $this->logger->debug("Download Path $downloadPath");
        if (file_exists($downloadPath)) {
            unlink($downloadPath);
        }

        if ($pricesArray) {
            $this->logger->debug( "Prices array exists");
            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $crystal->createFileForImport($pricesArray, $downloadPath);
            // Read data into string
            $csvString = file_get_contents($downloadPath);
            // send data to browser with a filename.
            $timestamp = date('Y-m-d-His');
            return $this->returnFileFromString('pricesToUpdate' . $timestamp . '.csv', $csvString);
        } else {
            print "There are no prices to be updated. ";
            return new ViewModel();
        }
    }


    public function unmatchedTrollProductsAction()
    {
         // Get data from mysql
        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        $records = $pricesRepo->getUnmatchedTrollPrices();

        $downloadPath = $this->config['tempDownloadName'];
        $downloadPath = str_replace(['\\','/'],DIRECTORY_SEPARATOR, $downloadPath);

        $this->logger->debug("Download Path $downloadPath");
        if (file_exists($downloadPath)) {
            unlink($downloadPath);
        }

        if ($records) {
            $this->logger->debug( "Prices array exists");
            $this->createFileForImport($records, $downloadPath);
            // Read data into string
            $csvString = file_get_contents($downloadPath);
            // send data to browser with a filename.
            $timestamp = date('Y-m-d-His');
            return $this->returnFileFromString('unmatchedTrollProducts' . $timestamp . '.csv', $csvString);
        } else {
            print "There are no records with bad ASINs.";
            return new ViewModel();
        }


    }


    /**********************
     * Creates a CSV file from an array
     * using the keys as headers.
     *
     * @param array $dataArray array of records, which are arrays with keys that match Crystal Commerce Column Names.
     * @param string $filePath optional file name to avoid using the default.
     **********************************/
    public function createFileForImport($dataArray, $filePath)
    {
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


    /**
     * This is the example from https://olegkrivtsov.github.io/using-zend-framework-3-book/html/en/Model_View_Controller/Disabling_the_View_Rendering.html
     *
     * This is the 'file' action that is invoked when a user wants to download the given file.
     */
    private function fileAction()
    {
        // Get the file name from GET variable
        $fileName = $this->params()->fromQuery('name', '');

        // Take some precautions to make file name secure
        $fileName = str_replace("/", "", $fileName);  // Remove slashes
        $fileName = str_replace("\\", "", $fileName); // Remove back-slashes


        // Try to open file
        $path = __DIR__ . '/../../../../data/' . $fileName;

        $path = str_replace(['\\','/'], DIRECTORY_SEPARATOR, $path);

        if (!is_readable($path)) {
            // Set 404 Not Found status code
            $this->getResponse()->setStatusCode(404);
            return false;
        }
        return $this->returnFileFromLocalPath($path);

    }

    protected function returnFileFromString($fileName, $string)
    {
        // Get file size in bytes
        $fileSize = strlen($string);

        // Write HTTP headers
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: application/octet-stream");
        $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
        $headers->addHeaderLine("Content-length: $fileSize");
        $headers->addHeaderLine("Cache-control: private");

        // Write file content
        if($fileSize !== 0 ) {
            $response->setContent($string);
        } else {
            // Set 500 Server Error status code
            $this->getResponse()->setStatusCode(500);
            return false;
        }
        // Return Response to avoid default view rendering
        return $this->getResponse();
    }



    protected function returnFileFromLocalPath($path)
    {
        // Get file size in bytes
        $fileSize = filesize($path);

        // Write HTTP headers
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: application/octet-stream");
        $headers->addHeaderLine("Content-Disposition: attachment; filename=\"" .
            basename($path) . "\"");
        $headers->addHeaderLine("Content-length: $fileSize");
        $headers->addHeaderLine("Cache-control: private");

        // Write file content
        $fileContent = file_get_contents($path);
        if($fileContent!=false) {
            $response->setContent($fileContent);
        } else {
            // Set 500 Server Error status code
            $this->getResponse()->setStatusCode(500);
            return false;
        }

        // Return Response to avoid default view rendering
        return $this->getResponse();
    }
}