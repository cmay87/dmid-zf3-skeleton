<?php

namespace Application\Authentication;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AclFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $service = (null === $options) ? new $requestedName : new $requestedName($options);
        $appConfig = $container->get('ApplicationConfig');
        $config = $container->get('Configuration');
        
        if (!isset($appConfig['acl'])) {
            throw new \Exception("'acl' applicatin configuration not found");
        }
        
        if (!isset($config['acl'])) {
            throw new \Exception("'acl' module configuration not found");
        }
        
        $mergedConfig = array_merge($appConfig['acl'], $config['acl']);
        
        return $service->setServiceManager($container)->loadConfig($mergedConfig);
    }
}
