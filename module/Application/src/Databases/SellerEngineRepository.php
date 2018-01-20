<?php

namespace Application\Databases;

use Application\Databases;
use Zend\Log\Logger;

class SellerEngineRepository extends Databases
{

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/seller-engine-repository.config.php');
        return array_merge($parent, $child);
    }

}