<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://sellery.sellerengine.com/login',
    'baseUrl' => 'https://sellery.sellerengine.com/',
    'username' => 'andrewstokinger@gmail.com',
    'password' => getenv('SELLER_ENGINE_PASS'),
    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'userIdForExport' => '5593',
    'localFileLocation' => __DIR__ . '/../../../data/mostRecentDownloadSellerEngine.csv',
    'referer' => '',

];