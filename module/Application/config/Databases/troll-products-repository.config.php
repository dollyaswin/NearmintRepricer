<?php

namespace Application\Databases;

return [
    'table name' => 'troll_products',
    'primary key' => 'record_id',
    'unique keys' => [
        'product_detail' => 'product_detail_id'
    ],
    'indexes' => [
        'asdx' => 'asin',
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'newsku' => [
            'definition' => 'varchar(32) DEFAULT NULL',
            'mapping' => 'newsku',
        ],
        'product_detail_id' => [
            'definition' => 'int(11) NOT NULL',
            'mapping' => '_productdetailid',
        ],
        'troll_set' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => '_category',
        ],
        'troll_product_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => '_name',
        ],
        'asin' => [
            'definition' => 'varchar(25) DEFAULT NULL',
            'mapping' => '_asin',
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