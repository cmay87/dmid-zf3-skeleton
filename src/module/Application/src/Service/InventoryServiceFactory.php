<?php

namespace Application\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class InventoryServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Configuration');
        
        return new ApplicationInventoryClient($config['m2m']['username'], $config['m2m']['password'], $config['m2m']['endpoint']);
    }
}