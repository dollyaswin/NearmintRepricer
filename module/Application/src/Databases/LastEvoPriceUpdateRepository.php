<?php

namespace Application\Databases;

use Application\Databases;

class LastEvoPriceUpdateRepository extends Databases
{
    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/last-evo-price-update.config.php');
        return array_merge($parent, $child);
    }

}