<?php

namespace Application\Databases;

use Application\Databases;

class TrollAndToadRepository extends Databases
{

    public function getConfig()
    {
        $parent = parent::getConfig();
        $child = include(__DIR__ . '/../../config/Databases/troll-and-toad-repository.config.php');
        return array_merge($parent, $child);
    }


    /**
     *  This solves the problem of Buy prices which have fallen off the Troll
     *  Buy List.   The pricing information can remain, but the quantity must be wiped out.
     */
    public function wipeOutCurrentBuyQuantity()
    {
        $query = "Update troll_and_toad SET troll_buy_quantity=0;";
        $statement = $this->conn->prepare($query);
        $statement->execute();
    }

}