<?php

namespace User;

return [
    'controllers' => [
        'factories' => [
            Controller\UserController::class => Controller\UserControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'zfcuser' => [
                'child_routes' => [
                    'request_reset_password' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/request-reset-password',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action'     => 'requestresetpassword',
                            ],
                        ],
                    ]
                ]
            ]
         ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'zfcuser' => __DIR__ . '/../view',
        ],
    ],
];
