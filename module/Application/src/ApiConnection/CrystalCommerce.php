<?php
namespace Application\ApiConnection;

use Application\ApiConnection;

class CrystalCommerce extends ApiConnection
{
    protected $authorizePostVariables;

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
        $result = $this->transmit('https://accounts.crystalcommerce.com/users/sign_in');
        if (strpos($result, 'You are already signed in.') !== false) {
            //already signed in
            return false;
        }
        $splitPage = explode('authenticity_token', $result);

        $truncatedPage = substr($splitPage[2],0, 2000);
        preg_match('/value="(.+)"/', $truncatedPage, $matches);
        $authenticityToken = $matches[1];
        if (strlen($authenticityToken) < 10 ) {
            throw new \Exception("Authenticity Token not found. The page may have changed");
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




}