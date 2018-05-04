<?php

namespace Application\Databases;

use Application\Databases;

class PriceUpdatesRepository extends Databases
{
    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/price-updates-repository.config.php');
        return array_merge($parent, $child);
    }

}