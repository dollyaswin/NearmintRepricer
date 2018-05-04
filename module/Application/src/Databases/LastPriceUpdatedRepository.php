<?php

namespace Application\Databases;

use Application\Databases;

class LastPriceUpdatedRepository extends Databases
{
    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/last-price-update-repository.config.php');
        return array_merge($parent, $child);
    }

}