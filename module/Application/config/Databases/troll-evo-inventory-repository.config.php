<?php

namespace Application\Databases;

return [
    'table name' => 'troll_evo_inventory',
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
        'newsku' => [
            'definition' => 'varchar(20) DEFAULT NULL',
            'mapping' => 'Product Sku',
        ],
        'troll_set' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Product Category',
        ],
        'troll_product_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Product Name',
        ],
        'troll_sell_price' => [
            'definition' => 'decimal(11,4) DEFAULT NULL',
            'mapping' => 'Troll Price',
        ],
        'lowest_evo_competitor_sell_price' => [
            'definition' => 'decimal(11,4) DEFAULT NULL',
            'mapping' => 'Lowest Price',
        ],
        'evo_sell_price' => [
            'definition' => 'decimal(11,4) DEFAULT NULL',
            'mapping' => '*My Price',
        ],
        'evo_quantity' => [
            'definition' => 'int(11) DEFAULT 0',
            'mapping' => 'Available',
        ],
        'evo_hold_quantity' => [
            'definition' => 'int(11) DEFAULT 0',
            'mapping' => '*On Hold',
        ],
        'evo_cost' => [
            'definition' => 'decimal(11,4) DEFAULT NULL',
            'mapping' => '*My Cost',
        ],
        'evo_sales_in_last_7' => [
            'definition' => 'int(11) DEFAULT 0',
            'mapping' => 'Sales (Past Week)',
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