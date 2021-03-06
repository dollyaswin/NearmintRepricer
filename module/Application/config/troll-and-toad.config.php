<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://www.trollandtoad.com/ajax_scripts/logon.php',
    'baseUrl' => 'https://www.trollandtoad.com/',
    'username' => 'derp@nearmintgames.com',
    'password' => getenv('TROLL_AND_TOAD_PASS'),
    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'localFileLocation' => __DIR__ . '/../../../data/mostRecentBuylistDownloadTrollAndToad.csv',
    'localEvoFileLocation' => __DIR__ . '/../../../data/mostRecentEvoDownloadTrollAndToad.csv',
    'referer' => '',
    'downloadType' => [
        'buyListPokemonSingles' => [
            'categoryName' => 'Pokemon Singles',
            'url' => 'https://www.trollandtoad.com/buylist/ajax_scripts/csv-download.php?deptCode=4',
        ],
        'buyListMagicSingles' => [
            'categoryName' => 'Magic Singles',
            'url' => 'https://www.trollandtoad.com/buylist/ajax_scripts/csv-download.php?deptCode=M',
        ],
        'buyListYugiohSingles' => [
            'categoryName' => 'Yugioh Singles',
            'url' => 'https://www.trollandtoad.com/buylist/ajax_scripts/csv-download.php?deptCode=Y',
        ],

    ],
    'merchantInventoryDownloadUrl' => 'https://www.trollandtoad.com/merchant/InventoryManager.php?',
    'merchantInventoryUploadUrl' => 'https://www.trollandtoad.com/ajax_scripts/inventory_manager_handle.php',
];