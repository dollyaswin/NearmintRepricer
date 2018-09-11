<?php
namespace User\Controller;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
//         $config = $container->get("Config");
        $resetPasswordRequestMapper = $container->get(\User\Mapper\ResetPasswordRequest::class);
        $resetPasswordService = $container->get(\User\Service\ResetPassword::class);
        $resetPasswordRequest = $container->get(\User\Form\ResetPasswordRequest::class);
        $resetPassword = $container->get(\User\Form\ResetPassword::class);
        $userController = new UserController($resetPasswordRequestMapper);
        $userController->setResetPasswordForm($resetPassword);
        $userController->setRequestResetPasswordForm($resetPasswordRequest);
        $userController->setResetPasswordService($resetPasswordService);
        return $userController;
    }
}
