<?php

namespace Application\Authentication\Identity;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserIdentityFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $userIdentity = new UserIdentity();
        $userIdentity->setEncryptionClass($container->get('Encryption'));
        $userIdentity->setLogger($container->get('Logger'));
        
        return $userIdentity;
    }
}
