<?php

namespace Application\Databases;

use Application\Databases;

class TrollProductRepository extends Databases
{

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/troll-products-repository.config.php');
        return array_merge($parent, $child);
    }


}