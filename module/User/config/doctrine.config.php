<?php
return [
    'doctrine' => [
        'driver' => [
            'user_entity' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/orm']
            ],
            'orm_default' => [
                'drivers' => [
                    'User\Entity' => 'user_entity',
                ]
            ]
        ],
    ],
];
