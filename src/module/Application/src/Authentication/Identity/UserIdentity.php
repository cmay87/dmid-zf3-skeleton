<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application\Authentication\Identity;

class UserIdentity {
    protected $logger;
    protected $encryption;
    
    private $_authService;
    private $_authType;
    private $_contactId;
    private $_displayUsername;
    private $_isSSOAuth = false;
    private $_password;
    private $_username;
    private $_appRole = 'guest';
    private $_userModifiedDate;
    
    public function setEncryptionClass(Application\Authentication\Encryption $encryption)
    {
        $this->encryption = $encryption;
    }
    
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }        
    
    public function getContactId()
    {
        return $this->_contactId;    
    }
    
    public function setContactId($contactId)
    {
        $this->_contactId = $contactId;
    }
    
    public function getPassword()
    {
        return $this->encryption->decrypt($this->_password);
    }
    
    public function setPassword($password)
    {
        $this->_password = $this->encryption->encrypt($password);
    }
    
    public function getUsername()
    {
        return $this->_username;
    }
    
    public function setUsername($username)
    {
        $this->_username = $username;
    }
    
    public function getRoleKey()
    {
        return $this->_appRole;
    }
    
    public function isAdmin()
    {
        return $this->_appRole === 'admin' || $this->isDeveloper();
    }
    
    public function isDeveloper()
    {
        return $this->_appRole === 'developer';
    }
    
    public function setRoles($roles)
    {
        if (in_array('Developer', $roles)) {
            $this->_appRole = 'developer';
        } elseif (in_array('DDS_ADMIN', $roles)) {
            $this->_appRole = 'admin';
        } else {
            $this->_appRole = 'user';
        }
    }
    
    public function isUserDataCurrent($lastModified)
    {
        if ($this->_userModifiedDate) {
            return (strtotime($lastModified) <= $this->_userModifiedDate);
        } else {
            return false;
        }
    }

    public function getUserModifiedDate()
    {
        return $this->_userModifiedDate;
    }

    public function setUserModifiedDate($modifiedDate)
    {
        $this->_userModifiedDate = strtotime($modifiedDate);
    }    
    
    public function getAuthService()
    {
        return $this->_authService;
    }
    
    public function setAuthService($authService)
    {
        $this->_authService = $authService;
    }
    
    public function getAuthType()
    {
        return $this->_authType;
    }
    
    public function setAuthType($authType)
    {
        $this->_authType = $authType;
    }
    
    public function getDisplayUsername()
    {
        return $this->_displayUsername;
    }
    
    public function setDisplayUsername($username)
    {
        $this->_displayUsername = $username;
    }
    
    public function markSSOAuth()
    {
        $this->_isSSOAuth = true;
    }
    
    public function isSSOAuth()
    {
        return $this->_isSSOAuth;
    }
}
