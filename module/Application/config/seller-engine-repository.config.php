<?php

/**************************************
 * This configuration file does a lot of work.  It contains the mappings
 * between third party website's CSV download column names,
 * and the local database column names.
 *
 * This also contains all non password MySQL login information.
 ****************************************/

namespace Application\Databases;

return [
    'table name' => 'sellery',
    'primary key' => 'record_id',
    'unique keys' => [
        'amazon_id' => 'asin'
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'asin' => [
            'definition' => 'varchar(25) DEFAULT NULL',
            'mapping' => 'ASIN on amazon.com',
        ],
        'sellery_sell_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Live price on Near Mint Games',
        ],
        'amazon_title' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Title on amazon.com',
        ],
        'amazon_avg_new_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Average new price on amazon.com',
        ],
        'amazon_lowest_new_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Lowest new price on amazon.com',
        ],
        'amazon_avg_used_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Average used price on amazon.com',
        ],
        'amazon_avg_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Average price on amazon.com',
        ],
        'amazon_buy_box_percentage' => [
            'definition' => 'decimal(9,8) DEFAULT NULL',
            'mapping' => 'New Buy Box 24h Ownership % on amazon.com',
        ],
        'amazon_buy_box_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'New Buy Box price plus shipping on amazon.com',
        ],
        'amazon_num_new_offers' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Number of new Offers on amazon.com',
        ],
        'amazon_sales_rank' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Sales rank on amazon.com',
        ],
        'sellery_estimated_profit' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Estimated profit on Near Mint Games',
        ],
        'amazon_fees' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Final price commission on Near Mint Games',
        ],
        'amazon_competition_price_plus_ship' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Chosen competition price plus shipping on Near Mint Games',
        ],
        'amazon_we_own_buy_box' => [
            'definition' => 'tinyint(1) DEFAULT NULL',
            'mapping' => 'I have the new Buy Box on Near Mint Games',
        ],
        'sellery_last_reprice_date' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' => 'Last repriced on Near Mint Games',
        ],
        'sellery_minimum_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Minimum price on Near Mint Games',
        ],
        'sellery_minimum_price_plus_ship' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Minimum price plus shipping on Near Mint Games',
        ],
        'sellery_minimum_ship' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Minimum price shipping on Near Mint Games',
        ],
        'sellery_pricing_rule' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Pricing rule on Near Mint Games',
        ],
        'sellery_pricing_strategy' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Pricing strategy on Near Mint Games',
        ],
        'amazon_shipping_carrier' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Shipping carrier on Near Mint Games',
        ],
        'amazon_shipping_credit' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Shipping credit on Near Mint Games',
        ],
        'shipping_cost' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Shipping cost on Near Mint Games',
        ],
        'sellery_smartlist_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Smartlist name on Near Mint Games',
        ],
        'sales_per_day' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Sales per day',
        ],
        'amazon_sold_in_7' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 7 days on Near Mint Games',
        ],
        'amazon_sold_in_15' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 15 days on Near Mint Games',
        ],
        'amazon_sold_in_30' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 30 days on Near Mint Games',
        ],
        'amazon_sold_in_60' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 60 days on Near Mint Games',
        ],
        'amazon_sold_in_90' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 90 days on Near Mint Games',
        ],
        'amazon_sold_in_180' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Units sold in the last 180 days on Near Mint Games',
        ],
        'sellery_cost' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Cost',
        ],
        'sellery_cost_source' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'Cost Source',
        ],
        'sellery_days_of_stock' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Days of Stock',
        ],
        'amazon_condition' => [
            'definition' => 'varchar(100) DEFAULT NULL',
            'mapping' => 'Condition',
        ],
        'amazon_last_restock_date' => [
            'definition' => "timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'mapping' => 'Last restock date',
        ],
        'amazon_buy_box_seller' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'New Buy Box Seller on amazon.com',
        ],
        'amazon_num_offers' => [
            'definition' => 'int(11) DEFAULT NULL',
            'mapping' => 'Number of Offers on amazon.com',
        ],
        'amazon_minimum_advertised_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'minimum advertised price',
        ],
        'is_fba' => [
            'definition' => 'varchar(25) DEFAULT NULL',
            'mapping' => 'is FBA',
        ],
        'amazon_competition_price' => [
            'definition' => 'decimal(9,2) DEFAULT NULL',
            'mapping' => 'Chosen competition price on Near Mint Games',
        ],
        'sellery_sku' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' => 'SKU',
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
