<?php

namespace Application\Authentication;

use Zend\Authentication\Adapter\AdapterInterface;

class Adapter  implements AdapterInterface {    
    //TODO :: Integrate this with 
    public function authenticate($userSSO = null)
    {
        $authResult = \Zend\Authentication\Result::SUCCESS;
        $userIdentity = new UserIdentity();

        return new \Zend\Authentication\Result($authResult, $userIdentity);
    }
}
