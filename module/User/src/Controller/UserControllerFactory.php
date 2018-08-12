<?php
namespace User\Controller;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UserControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
//         $config = $container->get("Config");
        $userController = new UserController();
        return $userController;
    }
}
