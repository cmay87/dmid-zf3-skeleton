<?php

namespace Application\Authentication;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ErsSSOFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Configuration');
        $encryptionClass = $container->get('Encryption');

        return new ErsSSO($config['ers']['client_secret'], APPLICATION_ENV, $registrationSystemId = 'EE', $encryptionClass);
    }
}
