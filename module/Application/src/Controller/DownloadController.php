<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * This is the controller class for managing file downloads.
 */
class DownloadController extends AbstractActionController
{


    public function indexAction()
    {
        // Show list of possible downloads and options. Link to the actions below.
    }

    public function pricesToUpdateAction()
    {
        // Get data from mysql

        // Turn data into string

        // send data to browser with a filename.

    }



    /**
     * This is the example from https://olegkrivtsov.github.io/using-zend-framework-3-book/html/en/Model_View_Controller/Disabling_the_View_Rendering.html
     *
     * This is the 'file' action that is invoked when a user wants to download the given file.
     */
    public function fileAction()
    {
        // Get the file name from GET variable
        $fileName = $this->params()->fromQuery('name', '');

        // Take some precautions to make file name secure
        $fileName = str_replace("/", "", $fileName);  // Remove slashes
        $fileName = str_replace("\\", "", $fileName); // Remove back-slashes

        // Try to open file
        $path = './data/download/' . $fileName;
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