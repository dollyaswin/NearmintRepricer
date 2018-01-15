<?php

namespace Application\Databases;

use Application\Databases;
use Zend\Log\Logger;

class CrystalCommerceRepository extends Databases
{
    protected $tableName = 'crystal_commerce';

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/crystal-commerce-repository.config.php');
        return array_merge($parent, $child);
    }

}