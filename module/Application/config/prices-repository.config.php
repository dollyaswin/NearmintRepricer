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
        'ASIN on amazon.com'             => 'asin',
        'Live price on Near Mint Games'  => 'sell_price',
    ],

];
