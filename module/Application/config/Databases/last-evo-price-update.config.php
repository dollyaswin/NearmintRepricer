<?php

namespace Application\Databases;

return [
    'table name' => 'last_evo_price_update',
    'primary key' => 'record_id',
    'unique keys' => [
        'product_detail' => 'product_detail_id',
    ],
    'indexes' => [
        'prdeidlaupdx' => 'product_detail_id, last_updated',
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'product_detail_id' => [
            'definition' => 'int(11) NOT NULL',
            'mapping' => 'product_detail_id',
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
        'quantity_old' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'quantity_old',
        ],
        'quantity_new' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'quantity_new',
        ],
        'hold_qty_old' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'hold_qty_old',
        ],
        'hold_qty_new' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'hold_qty_new',
        ],
        'last_updated' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
];