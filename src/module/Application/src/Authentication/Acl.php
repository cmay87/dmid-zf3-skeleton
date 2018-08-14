<?php

namespace Application\Authentication;

use Zend\Log\LoggerInterface;
use Zend\Log\LoggerAwareInterface;
use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Permissions\Acl\Role\GenericRole as Role;

class Acl extends ZendAcl implements LoggerAwareInterface {
    const DEFAULT_ROLE = 'guest';

    protected $logger;
    protected $serviceManager;
    
    public function loadConfig($config)
    {
        //First add all of the available roles
        $roles = array();
        if (isset($config['roles'])) {
            foreach ($config['roles'] as $role => $parents) {
                if (!isset($roles[$role])) {
                    $roles[$role] = new Role($role);
                }
                
                if (is_array($parents)) {
                    foreach ($parents as $parentRole) {
                        if (!isset($roles[$parentRole])) {
                            $roles[$parentRole] = new Role($parentRole);
                        } 

                        $this->addRole($roles[$role], $roles[$parentRole]);
                    }
                } elseif (is_string($parents)) {
                    if (!isset($roles[$parents])) {
                        $roles[$parents] = new Role($parents);
                    } 
                    
                    $this->addRole($roles[$role], $roles[$parents]);
                } else {
                    $this->addRole($roles[$role]);
                }
                
                 
            }
            
            //Now add resources and allowances
            foreach ($config['resources'] as $resource => $actions) {
                $aclResource = new Resource($resource);
                $this->addResource($aclResource);
                
                foreach ($actions as $action => $accessRole) {
                    if ($action === 'all') {
                        $this->allow($accessRole, $aclResource);
                    } else {
                        $this->allow($accessRole, $aclResource, $action);
                    }    
                }
            }
            
            $this->allow($roles['admin']);
        } else {
            throw new \Exception('Empty ACL role configuration found');
        }
        
        return $this;
    }
    
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }    
    
    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return $this
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}
