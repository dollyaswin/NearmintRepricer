<?php

namespace Application\Controller;

use Application\Databases\RunTimeRepository;
use Application\Factory\LoggerFactory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ReportingController extends AbstractActionController
{
    protected $logger;
    protected $debug = true;

    public function __construct()
    {
        ini_set('memory_limit','2048M');
    }

    public function lastRunAction()
    {
        $this->setLogger('lastRun.txt');

        $scriptId = $this->params()->fromRoute('reportId', 1);
        $limit = $this->params()->fromRoute('limit', 20);

        $scriptRunRepo = new RunTimeRepository($this->logger, $this->debug);
        $recentRunData = $scriptRunRepo->getRecentRunTimeInformation($scriptId, $limit);

        $variables = [
            'recentScriptRunData' => $recentRunData,
        ];

        // This just shows the user the default Zend Skeleton home page if they load http://localhost/
        return new ViewModel($variables);
    }

    private function setLogger($fileName)
    {
        $this->debug = $this->params()->fromQuery('debug', false);
        $inBrowser = $this->params()->fromQuery('inBrowser', false);
        $this->logger = LoggerFactory::createLogger($fileName, $inBrowser, $this->debug);
    }

}