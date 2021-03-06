<?php

namespace Application\Authentication;

use Interop\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Configuration');
        
        $authService = new AuthenticationService();
        $authService->setStorage(new \Zend\Authentication\Storage\Session($config['auth_storage_namespace']));        
        
        return $authService;
    }
}
