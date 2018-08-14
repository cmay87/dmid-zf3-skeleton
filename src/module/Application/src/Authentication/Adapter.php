<?php

namespace Application\Authentication;

use Zend\Authentication\Adapter\AdapterInterface;

class Adapter  implements AdapterInterface {  
    protected $serviceManager;
    private $_userSSO = null;
    
    public function __construct($userSSO)
    {
        $this->_userSSO = $userSSO;
    }
    
    public function authenticate()
    {
        if ($this->_userSSO !== null) {
            $authResult = \Zend\Authentication\Result::SUCCESS;
            $userIdentity = $this->serviceManager->get('UserIdentity');

            $userInfo = $this->_userSSO->getUser();

            $userIdentity->markSSOAuth();
            $userIdentity->setAuthService($this->_userSSO->getAuthService());
            $userIdentity->setAuthType($this->_userSSO->getAuthType());
            $userIdentity->setContactId($this->_userSSO->getContactId());
            $userIdentity->setDisplayUsername($userInfo->username);
            $userIdentity->setUsername($this->_userSSO->getUsername());
            $userIdentity->setPassword($this->_userSSO->getPassword());
            $userIdentity->setUserModifiedDate($this->_userSSO->getLastProfileUpdateTime());
            $userIdentity->setRoles($userInfo->roles);

            if ($this->_userSSO->getAuthType() == 'oauth') {
                $userIdentity->setDisplayUsername($userInfo->username . " (via {$this->_userSSO->getAuthService()})");
            }
        } else {
            $authResult = \Zend\Authentication\Result::FAILURE_IDENTITY_NOT_FOUND;
            $userIdentity = null;
        }
        
        return new \Zend\Authentication\Result($authResult, $userIdentity);
    }
    
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }
}
