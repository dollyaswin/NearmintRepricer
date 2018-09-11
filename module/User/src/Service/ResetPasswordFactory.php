<?php
namespace User\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * ResetPassword Factory
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class ResetPasswordFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $edName, array $options = null)
    {
        $resetPasswordRequestMapper = $container->get(\User\Mapper\ResetPasswordRequest::class);
        $resetPasswordEntity = new \User\Entity\ResetPasswordRequest;
        $service = new ResetPassword($resetPasswordRequestMapper, $resetPasswordEntity);
        return $service;
    }
}
