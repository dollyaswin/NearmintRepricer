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
        $config  = $container->get('Config');
        // $logger  = $container->get("logger_default");
        $mailNotificationConfig = $config['mail']['transport']['notification'];
        $viewRenderer  = $container->get('ViewRenderer');
        $viewHelper    = $container->get('ViewHelperManager');
        $resetPasswordRequestMapper = $container->get(\User\Mapper\ResetPasswordRequest::class);
        $resetPasswordEntity = new \User\Entity\ResetPasswordRequest;
        $service = new ResetPassword($resetPasswordRequestMapper, $resetPasswordEntity);
        // $service->setLogger($logger);
        $service->setConfig($mailNotificationConfig);
        $service->setViewRenderer($viewRenderer);
        $service->setViewHelper($viewHelper);
        return $service;
    }
}
