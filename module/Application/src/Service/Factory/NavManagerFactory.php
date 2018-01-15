<?php

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Application\Service\NavManager;


class NavManagerFactory {
    /**
     * This method creates the NavManager service and returns its instance.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $authService = $container->get(\Zend\Authentication\AuthenticationService::class);
        $viewHelperManager = $container->get('ViewHelperManager');
        $urlHelper = $viewHelperManager->get('url');

        return new NavManager($authService, $urlHelper);
    }
}