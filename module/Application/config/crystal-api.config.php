<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://crystal-api.crystalcommerce.com/api/tokens',
    'baseUrl' => 'https://crystal-api.crystalcommerce.com/api',
    'uid' => getenv('CRYSTAL_COMMERCE_UID'),

    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'referer' => 'nearmintgames.com',

    'tempFileName'     => __DIR__ . '/../../../data/mostRecentDownloadCrystalCommerce.csv',
    'fileToUploadPath' => __DIR__ . '/../../../data/fileToUploadToCrystalCommerce.csv',
    'fileUploadOutputLoggingPath' => __DIR__ . '/../../../data/uploadOutput.html',

];