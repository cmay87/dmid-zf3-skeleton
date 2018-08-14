<?php

namespace Application\Authentication;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class EncryptionFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Configuration');

        $encryption = new Encryption();
        
        //If the config has encryption configurations, add them
        if (isset($config['encryption'])) {
            if (isset($config['encryption']['keys'])) {
                if (!isset($config['encryption']['keys']['default'])) {
                    throw new Exception\EncryptionException("Configuration option 'encryption.keys.default' is not set and is required");
                }
                
                foreach ($config['encryption']['keys'] as $id => $encryptionKey) {
                    $encryption->addEncrpytionKey($id, $encryptionKey);
                }
            } else {
                throw new Exception\EncryptionException("Configuration option 'encryption.keys' is not set and is required");
            }

            if (isset($config['encryption']['salts'])) {
                if (!isset($config['encryption']['salts']['default'])) {
                    throw new Exception\EncryptionException("Configuration option 'encryption.salts.default' is not set and is required");
                }
                
                foreach ($config['encryption']['salts'] as $id => $salt) {
                    $encryption->addSaltValue($id, $salt);
                }
            } else {
                throw new Exception\EncryptionException("Configuration option 'encryption.salts' is not set and is required");
            }
        } else {
            throw new Exception\EncryptionException("Configuration option 'encryption' is not set and is required");
        }
        
        return $encryption;
    }
}
