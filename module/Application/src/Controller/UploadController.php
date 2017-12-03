<?php

namespace Application\Controller;


use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UploadController extends AbstractActionController
{


    public function indexAction()
    {
        print ("Upload Controller Index in use <pre>");

        print ("Files : ". print_r($_FILES, true));

        print ("POST : "  . print_r($_POST, true));

        print ("</pre>");
        // Show list of possible downloads and options. Link to the actions below.
        return new ViewModel();
    }


}