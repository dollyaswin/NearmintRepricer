<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'baseUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'useremail' => 'derp@nearmintgames.com',
    'password' => getenv('CRYSTAL_COMMERCE_PASS'),
    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'referer' => 'https://nearmintgames-admin.crystalcommerce.com',
    // adminDomain is everything before .crystalcommerce.com on the admin panel URL, instead of www
    'adminDomain' => 'nearmintgames-admin',
    'sleepBetweenFileReportChecks' => 30,
    'attmptsToGetFileReportBeforeError' => 40,
    'tempFileName'     => __DIR__ . '/../../../data/mostRecentDownloadCrystalCommerce.csv',
    'fileToUploadPath' => __DIR__ . '/../../../data/fileToUploadToCrystalCommerce.csv',
    'fileUploadOutputLoggingPath' => __DIR__ . '/../../../data/uploadOutput.html',

];