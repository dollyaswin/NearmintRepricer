<?php

namespace Application\Databases;

return [
    'table name' => 'crystal_commerce',
    'primary key' => 'record_id',
    'unique keys' => [
        'amazon_id' => 'asin',
        'product_name' => '`product_name`,`category_name`',
    ],
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'product_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'Product Name',
        ],
        'category_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'Category',
        ],
        'total_qty' => [
            'definition' => "int(11) NOT NULL DEFAULT '0'",
        'mapping' =>'Total Qty',
         ],
        'wishlists' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' =>'Wishlists',
        ],
        'buy_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' =>'Buy Price',
        ],
        'cc_sell_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' =>'Sell Price',
        ],
        'product_url' => [
            'definition' => 'varchar(1000) DEFAULT NULL',
            'mapping' => 'URL',
        ],
        'barcode' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'Barcode',
        ],
        'manufacturer_sku' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'Manufacturer SKU',
        ],
        'asin' => [
            'definition' => 'varchar(25) DEFAULT NULL',
            'mapping' => 'ASIN',
        ],
        'msrp' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' =>'MSRP',
        ],
        'brand' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'Brand',
        ],
        'weight' => [
            'definition' => 'decimal(8,4) DEFAULT NULL',
            'mapping' =>'Weight',
        ],
        'description' => [
            'definition' => 'varchar(4000) DEFAULT NULL',
            'mapping' =>'Description',
        ],
        'max_quantity' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' =>'Max Qty',
        ],
        'domestic_only' => [
            'definition' => 'tinyint(1) DEFAULT NULL',
            'mapping' =>'Domestic Only',
        ],
        'tax_exempt' => [
            'definition' => 'tinyint(1) DEFAULT NULL',
            'mapping' =>'Tax Exempt',
        ],
        'date_created' => [
            'definition' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
        'last_updated' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ]

];
