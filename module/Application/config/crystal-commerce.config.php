<?php

namespace Application\ApiConnection;

return [
    'authorizeUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'baseUrl' => 'https://accounts.crystalcommerce.com/users/sign_in',
    'useremail' => 'andrewstokinger@gmail.com',
    'password' => getenv('CRYSTAL_COMMERCE_PASS'),
    'cookieFile' => __DIR__ . '/../../../data/cookiefile',
    'referer' => 'https://nearmintgames-admin.crystalcommerce.com',

];