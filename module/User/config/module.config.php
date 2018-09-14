<?php

namespace User;

return [
    'mail' => [
        'transport' => [
            'notification' => [
                'reset_password' => [
                    'template' => 'user/user/resetpasswordlink.phtml',
                    'subject'  => 'Reset Your Password',
                    'url' => ':scheme://:host/user/reset-password/:key'
                ],
                'sender' => [
                    'name' => 'Repricer Project Admin',
                    'from' => '',
                ],
                'options' => [
                    'host'   => '',
                    'connection_class'  => 'login',
                    'connection_config' => [
                        'username' => '',
                        'password' => '',
                        'ssl' => 'tls'
                    ],
                    'port' => 587
                ]
            ]
        ]
    ],
    'controllers' => [
        'factories' => [
            Controller\UserController::class => Controller\UserControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Mapper\ResetPasswordRequest::class => Mapper\ResetPasswordRequestFactory::class,
            Form\ResetPasswordRequest::class   => Form\ResetPasswordRequestFactory::class,
            Form\ResetPassword::class          => Form\ResetPasswordFactory::class,
            Service\ResetPassword::class       => Service\ResetPasswordFactory::class
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
                    'request_reset_password_success' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/request-reset-password-success',
                            'defaults' => [
                                'controller' => Controller\UserController::class,
                                'action'     => 'requestresetpasswordsuccess',
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
