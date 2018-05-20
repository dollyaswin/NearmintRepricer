<?php

namespace Application\Databases;

use Application\Databases;

class TrollEvoInventoryRepository extends Databases
{

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/troll-evo-inventory-repository.config.php');
        return array_merge($parent, $child);
    }

}