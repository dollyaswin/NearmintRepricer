<?php

namespace Application\Controller;


use Application\ApiConnection\CrystalCommerce;
use Application\ApiConnection\TrollandToad;
use Application\Databases\LastEvoPriceUpdateRepository;
use Application\Databases\LastPriceUpdatedRepository;
use Application\Databases\PricesRepository;
use Application\Databases\PriceUpdatesRepository;
use Application\Databases\RunTimeRepository;
use Application\Factory\LoggerFactory;
use Application\Repricer\CrystalFromSellery;
use Application\Repricer\TrollEvo;
use Application\ScriptNames;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UploadController extends AbstractActionController
{
    private $debug;
    private $updateLimit;
    private $logger;

    protected $startTime;
    protected $tempFileName;

    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->startTime = date('Y-m-d H:i:s');
        $this->debug = true;
    }

    public function trollEvoUpdateAction()
    {
        $this->setLogger('uploadEvoLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempEvoPriceUpdateLog.txt';
        $this->addTempLogger($this->tempFileName);

        $updateLimit = intval($this->params()->fromQuery('updateLimit', 20));
        $maxPrice = intval($this->params()->fromQuery('maxPrice', 0));

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getProductsToUpdateOnTrollEvo($updateLimit, $maxPrice);

        if (count($productsToUpdate) > 0 ) {
            $repricer = new TrollEvo($this->logger, $this->debug);
            $productsToUpdate = $repricer->calculatePrices($productsToUpdate);

            $troll = new TrollandToad($this->logger, $this->debug);
            $result = $troll->evoUploadArray($productsToUpdate);

            if ($result) {
                if($repricer->markProductsUpdated($productsToUpdate)) {
                    $message = "Upload Successful to EVO";
                } else {
                    $message = "Upload Successful to EVO, but database update failed.";
                }
            } else {
                $message = "Failed to Upload prices to Troll Evo";
            }

        } else {
            $message = "No Prices to update";
        }

        $scriptName = ScriptNames::SCRIPT_UPDATE_TROLL_EVO_INVENTORY;
        $this->logScript($scriptName,$message);
        return new ViewModel();
    }

    public function indexAction()
    {
        $this->setLogger('uploadCCLog.txt');
        $this->tempFileName = __DIR__ . '/../../../../logs/tempCCPriceUpdateLog.txt';
        $this->addTempLogger($this->tempFileName);

        $this->updateLimit = intval($this->params()->fromQuery('updateLimit', 15));
        $mode = $this->params()->fromQuery('mode', 'instock');

        $prices = new PricesRepository($this->logger);
        $productsToUpdate = $prices->getPricesToUpdate($mode, $this->updateLimit);

        if ($productsToUpdate && count($productsToUpdate) > 0) {
            $repricer = new CrystalFromSellery($this->logger, $this->debug);
            $productsToUpdate = $repricer->calculatePrices($productsToUpdate);

            $crystal = new CrystalCommerce($this->logger, $this->debug);
            $result = $crystal->updateProductPrices($productsToUpdate);

            if ($result) {
                if ($repricer->markProductsUpdated($productsToUpdate)) {
                    $message = "Upload Successful in " . $mode . " mode";
                } else {
                    $message = "Upload Successful in " . $mode . " mode, but database update failed.";
                }
            } else {
                $message = "Failed to Upload prices to CC";
            }

        } else {
            $message = "No Prices to update in " . $mode . " mode";
        }

        if ($mode == 'instock') {
            $scriptName = ScriptNames::SCRIPT_PRICES_TO_CC_INSTOCK;
        } else {
            $scriptName = ScriptNames::SCRIPT_PRICES_TO_CC_BUY;
        }

        $this->logScript($scriptName, $message);
        return new ViewModel();
    }

    protected function logScript($scriptName, $message)
    {
        $runTimes = new RunTimeRepository($this->logger, $this->debug);
        $this->logger->info($message);
        $errorLog = substr(file_get_contents($this->tempFileName),0,1400);
        $runTimes->logScriptRun($scriptName, $message, $errorLog, $this->startTime);
        file_put_contents($this->tempFileName,'');
    }

    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }

    private function addTempLogger($tempFileName)
    {
        $this->logger = LoggerFactory::addWriterToLogger($this->logger, $tempFileName);
    }


}