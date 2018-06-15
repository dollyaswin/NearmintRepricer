<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;

class Module
{
    const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();
        $em = $mvcEvent->getApplication()->getEventManager();
        $authService = $sm->get('zfcuser_auth_service');

        $em->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($authService) {
            // get the current controller name
            $controller = $e->getRouteMatch()->getParam('controller');
            $indexController  = explode('\\', $controller);
            $namespace  = $indexController[0];
            if ($namespace === __NAMESPACE__ && ! $authService->hasIdentity()) {
                $router = $e->getRouter();
                $url = $router->assemble([], [
                    'name' => 'zfcuser/login'
                ]);

                $response = $e->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);
                return $response;
            }
//             $name = (substr($controller, strrpos($controller, '\\') + 0));
//             echo $name;
//             $class  = new \ReflectionClass($controller);
//             $class->getNamespaceName();
//             echo $class->getNamespaceName();
                // get the current route
            $route = $e->getRouteMatch()->getMatchedRouteName();
        }, -100);
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
