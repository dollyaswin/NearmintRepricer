<?php

namespace Application\Databases;

return [
    'table name' => 'troll_and_toad',
    'primary key' => 'record_id',
    'unique keys' => [
        'product_detail' => 'product_detail_id'
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'product_detail_id' => [
            'definition' => 'int(11) NOT NULL',
            'mapping' => 'Product Id',
        ],
        'troll_set' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Set',
        ],
        'troll_product_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Edition',
        ],
        'troll_condition' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Condition',
        ],
        'troll_buy_price' => [
            'definition' => 'decimal(11,4) DEFAULT NULL',
            'mapping' => 'Buy Price',
        ],
        'troll_buy_quantity' => [
            'definition' => 'int(11) DEFAULT 0',
            'mapping' => 'Buy Qty',
        ],
        'troll_category' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Category',
        ],
        'date_created' => [
            'definition' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
        'last_updated' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
];