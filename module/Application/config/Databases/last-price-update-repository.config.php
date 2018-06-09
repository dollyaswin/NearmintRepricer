<?php

namespace Application\Databases;

return [
    'table name' => 'last_price_update',
    'primary key' => 'record_id',
    'unique keys' => [
        'amazon_id' => 'asin',
    ],
    'indexes' => [
        'aslaupdx' => 'asin, last_updated',
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'asin' => [
            'definition' => 'varchar(25) NOT NULL',
            'mapping' => 'asin',
        ],
        'reprice_rule_id' => [
            'definition' => 'int(11) NOT NULL',
            'mapping' => 'reprice_rule',
        ],
        'sell_price_old' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'sell_price_old',
        ],
        'sell_price_new' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'sell_price_new',
        ],
        'buy_price_old' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'buy_price_old',
        ],
        'buy_price_new' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'buy_price_new',
        ],
        'last_updated' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
];