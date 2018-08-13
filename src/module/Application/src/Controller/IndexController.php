<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractController
{   
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function sessionupdateAction()
    {
        $json = array('valid' => true, 'error' => null);
        
        $configuration = $this->getServiceManager()->get('Configuration');
        
        $sessionManager = \Zend\Session\Container::getDefaultManager();
        $sessionManager->rememberMe($configuration['session']['config']['options']['remember_me_seconds']);
        
        $json['ttl'] = $configuration['session']['config']['options']['remember_me_seconds'];
        
        return new JsonModel($json);
    }
}
