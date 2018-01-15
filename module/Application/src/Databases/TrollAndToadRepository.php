<?php

namespace Application\Databases;

use Application\Databases;

class TrollAndToadRepository extends Databases
{

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/troll-and-toad-repository.config.php');
        return array_merge($parent, $child);
    }

}