<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'baseUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'useremail' => 'andrewstokinger@gmail.com',
    'password' => getenv('CRYSTAL_COMMERCE_PASS'),
    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'referer' => 'https://nearmintgames-admin.crystalcommerce.com',
    // Everything before .crystalcommerce.com on the admin panel URL, instead of www is the adminDomain
    'adminDomain' => 'nearmintgames-admin',
    'sleepBetweenFileReportChecks' => 30,
    'attmptsToGetFileReportBeforeError' => 30,
    'tempFileName' => __DIR__ . '/../../../data/mostRecentDownloadCrystalCommerce.csv',
    'fileToUploadPath' => '/../../../data/fileToUploadToCrystalCommerce.csv'

];