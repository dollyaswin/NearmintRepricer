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
        $userController = new UserController($resetPasswordRequestMapper);
        return $userController;
    }
}
