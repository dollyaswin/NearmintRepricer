<?php

namespace User;

return [
    'controllers' => [
        'factories' => [
            Controller\UserController::class => Controller\UserControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Mapper\ResetPasswordRequest::class => Mapper\ResetPasswordRequestFactory::class
        ]
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
                    ],
                    'reset_password' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/reset-password[/:uuid]',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action'     => 'resetpassword',
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
