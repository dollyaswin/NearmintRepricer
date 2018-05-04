<?php

namespace Application\Databases;

return [
    'table name' => 'price_updates',
    'primary key' => 'record_id',
    'indexes' => [
        'amazon_id' => 'asin',
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