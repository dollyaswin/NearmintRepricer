<?php

namespace Application\Databases;

use Application\ScriptNames;

return [
    'table name' => 'SCRIPT_RUN_LOG',
    'primary key' => 'record_id',
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'script_id' => [
            'definition' => 'int(11) NOT NULL DEFAULT 1',
            'mapping' =>'',
        ],
        'script_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'script_result' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'script_error_message' => [
            'definition' => 'varchar(1600) DEFAULT NULL',
            'mapping' =>'',
        ],
        'start_time' => [
            'definition' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
        'completion_time' => [
            'definition' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
    'scriptList' => [
        ScriptNames::SCRIPT_PRICES_TO_CC_INSTOCK => 2,
        ScriptNames::SCRIPT_GET_CC_PRICES => 3,
        ScriptNames::SCRIPT_GET_TROLL_BUY => 4,
        ScriptNames::SCRIPT_GET_SELLERY_PRICES => 5,
        ScriptNames::SCRIPT_GET_CC_PRICES_API => 6,
        ScriptNames::SCRIPT_LOAD_TROLL_TRODUCTS=> 7,
        ScriptNames::SCRIPT_TEST => 8,
        ScriptNames::SCRIPT_PRICES_TO_CC_BUY => 9,
        ScriptNames::SCRIPT_GET_TROLL_EVO_INVENTORY => 10,
        ScriptNames::SCRIPT_UPDATE_TROLL_EVO_INVENTORY => 11,
    ],

];
