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
    'defaultDb' => 'nearmintgames',
    'host' => 'localhost',
    'username' => 'derp',
    // Name on Crystal Commerce Download => database Column Name
    'crystalCommerceMapping' => [
        'Product Name'     => 'product_name',
        'Category'         => 'category_name',
        'Total Qty'        => 'total_qty',
        'Wishlists'        => 'wishlists',
        'Buy Price'        => 'buy_price',
        //'Sell Price'       => 'sell_price',
        'URL'              => 'product_url',
        'Barcode'          => 'barcode',
        'Manufacturer SKU' => 'manufacturer_sku',
        'ASIN'             => 'asin',
        'MSRP'             => 'msrp',
        'Brand'            => 'brand',
        'Weight'           => 'weight',
        'Description'      => 'description',
        'Max Qty'          => 'max_quantity',
        'Domestic Only'    => 'domestic_only',
        'Tax Exempt'       => 'tax_exempt',
    ],
    // Name on Sellery Download => database Column Name
    'selleryMapping'  => [
        'ASIN on amazon.com'                                           => 'asin',
        'Live price on Near Mint Games'                                => 'sell_price',
        'Title on amazon.com'                                          => 'amazon_title',
        'Average new price on amazon.com'                              => 'amazon_avg_new_price',
        'Average price on amazon.com'                                  => 'amazon_lowest_new_price',
        'Average used price on amazon.com'                             => 'amazon_avg_used_price',
        'Lowest new price on amazon.com'                               => 'amazon_avg_price',
        'New Buy Box 24h Ownership % on amazon.com'                    => 'amazon_buy_box_percentage',
        'New Buy Box price plus shipping on amazon.com'                => 'amazon_buy_box_price',
        'Number of new Offers on amazon.com'                           => 'amazon_num_offers',
        'Sales rank on amazon.com'                                     => 'amazon_sales_rank',
        'Estimated profit on Near Mint Games'                          => 'sellery_estimated_profit',
        'Final price commission on Near Mint Games'                    => 'amazon_fees',
        'Chosen competition price plus shipping on Near Mint Games'    => 'amazon_competition_price',
        'I have the new Buy Box on Near Mint Games'                    => 'amazon_we_own_buy_box',
        'Last repriced on Near Mint Games'                             => 'sellery_last_reprice_date',
        'Minimum price on Near Mint Games'                             => 'sellery_minimum_price',
        'Minimum price plus shipping on Near Mint Games'               => 'sellery_minimum_ship',
        'Minimum price shipping on Near Mint Games'                    => 'sellery_minimum_price_plus_ship',
        'Pricing rule on Near Mint Games'                              => 'sellery_pricing_rule',
        'Pricing strategy on Near Mint Games'                          => 'sellery_pricing_strategy',
        'Shipping carrier on Near Mint Games'                          => 'sellery_shipping_carrier',
        'Shipping cost on Near Mint Games'                             => 'sellery_shipping_credit',
        'Shipping credit on Near Mint Games'                           => 'sellery_smartlist_name',
        'Smartlist name on Near Mint Games'                            => 'amazon_sales_per_day',
        'Sales per day'                                                => 'amazon_sold_in_7',
        'Units sold in the last 7 days on Near Mint Games'             => 'amazon_sold_in_15',
        'Units sold in the last 15 days on Near Mint Games'            => 'amazon_sold_in_30',
        'Units sold in the last 30 days on Near Mint Games'            => 'amazon_sold_in_60',
        'Units sold in the last 60 days on Near Mint Games'            => 'amazon_sold_in_90',
        'Units sold in the last 90 days on Near Mint Games'            => 'amazon_sold_in_120',
        'Units sold in the last 180 days on Near Mint Games'           => 'amazon_sold_in_180',
        'Cost'                                                         => 'sellery_cost',
        'Cost Source'                                                  => 'sellery_cost_source',
        'Days of Stock'                                                => 'sellery_days_of_stock',
        'Condition'                                                    => 'amazon_condition',
        'Last restock date'                                            => 'amazon_last_restock_date',
        'New Buy Box Seller on amazon.com'                             => 'amazon_buy_box_seller',
        'Number of Offers on amazon.com'                               => 'amazon_num_offers',
    ],

];
