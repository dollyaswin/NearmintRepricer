<?php

/***************************************
 * This is the Controller, and where most of the logic should live.
 *
 * Public methods in this class which end in Action can be called by loading the URL
 * localhost/application/converted-method-name   for method convertedMethodName()
 *
 * It is expected the user will first
 * getCrystalCommerceDataAction() and getSelleryPricingAction() in any order
 * and then run updateCrystalCommercePricesAction()
 *
 ******************************************/

namespace Application\Controller;

use Application\ApiConnection\CrystalApi;
use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\CrystalApi\ProductModel;
use Application\Databases\LastEvoPriceUpdateRepository;
use Application\Databases\LastPriceUpdatedRepository;
use Application\Databases\PricesRepository;
use Application\Databases\PriceUpdatesRepository;
use Application\Databases\RunTimeRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    protected $logger;
    protected $debug = true;

    public function __construct()
    {
        ini_set('memory_limit','2048M');
        $this->logger = LoggerFactory::createLogger('updateLog.txt', true, $this->debug);
    }

    public function indexAction()
    {
        $scripts = [
            'Get Prices From Crystal Commerce' => '/get-data/get-crystal-commerce-data',
            'Get Prices From Crystal Commerce, Include OOS' => '/get-data/get-crystal-commerce-data?includeOutOfStock=true',
            'Get Prices From Sellery' => '/get-data/get-sellery-pricing',
            'Get Buy Prices From Troll and Toad' => '/get-data/troll-buy-prices',
            'Get Evo Inventory Troll and Toad' => '/get-data/troll-evo-inventory',
            'Upload In Stock Prices to Crystal Commerce' => '/upload?inBrowser=true&updateLimit=20&mode=instock',
            'Upload On Buy List Prices to Crystal Commerce' => '/upload?inBrowser=true&updateLimit=20&mode=onBuyList',
            'Upload Product Updates to EVO ' => '/upload/troll-evo-update?updateLimit=20&inBrowser=true',
        ];

        if (getenv('APPLICATION_ENV') == 'development') {
            //$scripts['Crystal Commerce Data'] = '/get-data/get-crystal-products-using-api?inBrowser=true&debug=true';
            /*
            $scripts['Download Crystal Commerce Prices Skip Import'] =
                '/get-data/get-crystal-commerce-data?skipImport=true&inBrowser=true&debug=true';
            $scripts['Download Crystal Commerce Prices Skip Import Include OOS'] =
                '/get-data/get-crystal-commerce-data?skipImport=true&includeOutOfStock=true&inBrowser=true&debug=true';
            $scripts['Download Sellery Prices Skip Import'] =
                '/get-data/get-sellery-pricing?skipImport=true&inBrowser=true&debug=true';
            */
            $scripts['----- DEBUG Scripts ------'] = '/';
            $scripts['Download Evo Skip Import'] =
                '/get-data/troll-evo-inventory?skipImport=true&inBrowser=true&debug=true';
            $scripts['Load Evo From Local File'] =
                '/get-data/troll-evo-inventory?skipDownload=true&inBrowser=true&debug=true';
            $scripts['Load Crystal Commerce Prices From Local File'] =
                '/get-data/get-crystal-commerce-data?skipDownload=true&inBrowser=true&debug=true';
            $scripts['Load Sellery Prices From Local File'] =
                '/get-data/get-sellery-pricing?skipDownload=true&inBrowser=true&debug=true';
            $scripts['Test Script'] = '/get-data/test-script';
            $scripts['Reprice One EVO Product'] = '/upload/troll-evo-update?updateLimit=1&maxPrice=0.99&inBrowser=true&debug=true';
        }

        $downloads = [
            'Spreadsheet Generator' => '/download',
            'Import Troll Product List' => '/get-data/troll-products',
            'Download Unmatched Troll Products' => '/download/unmatched-troll-products',
            'Download Unmatched ASINs on CC' => '/download/get-unmatched-asins?source=crystal',
            'Download Unmatched ASINs on Sellery' => '/download/get-unmatched-asins?source=sellery',
            'Download Recent Price Changes' => '/download/get-recent-price-changes?days=1',
        ];

        $scriptRunRepo = new RunTimeRepository($this->logger, $this->debug);
        $recentRunData = $scriptRunRepo->getLastRunInformation();

        $variables = [
            'scripts' => $scripts,
            'downloads' => $downloads,
            'recentScriptRunData' => $recentRunData,
        ];

        // This just shows the user the default Zend Skeleton home page if they load http://localhost/
        return new ViewModel($variables);
    }

    public function testAction()
    {
        /*
        $arrayUpdate = [
            [
                'product_name' => 'Test',
                'sell_price_old' => 1.20,
                'sell_price_new' => 1.25,
                'product_detail_id' => 123456789,
                'quantity_old' => 1,
                'quantity_new' => 5,
                'hold_qty_old' => 4,
                'hold_qty_new' => 0,
            ]
        ];
        $repo = new LastEvoPriceUpdateRepository($this->logger, $this->debug);
        $repo->importFromArray($arrayUpdate);
        */

    }


    // This function needs rewritten to use getRecords() differently
    // and to use the CC API
    private function updateCrystalCommercePricesAction()
    {
        set_time_limit(0);

        $this->setLogger('CrystalCommercePricesUpdateLog.txt');

        $pricesRepo = new PricesRepository($this->logger, $this->debug);
        $pricesArray = $pricesRepo->getRecords([],true, false, true);

        if ($pricesArray) {
            $this->logger->info("There are " . count($pricesArray) . " prices to be uploaded");
            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $crystal->createFileForImport($pricesArray);

            if($ouput = $crystal->uploadFileToImportForm()) {
                $this->logger->info("Successfully uploaded CSV File.");
            } else {
                $this->logger->info("Failed to upload CSV File.");
            }
        } else {
            $this->logger->info("There are no prices which need to be updated");
        }

    }


    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }


}
