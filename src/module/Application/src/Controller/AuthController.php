<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class AuthController extends AbstractController
{   
    public function loginAction()
    {
        //TODO :: This should be doable by just invoking the plugin but I'm having issues with that
        if ($this->getServiceManager()->get(\Zend\Authentication\AuthenticationService::class)->hasIdentity()) {
            return $this->redirect()->toRoute('dashboard');
        }

        $configuration = $this->getServiceManager()->get('Configuration');
        
        //Redirect to ERS with a return path to where the user was attempting to nagivate to
        if ($configuration['allow_login'] && array_key_exists('redirect', $_REQUEST)) {
            $authService = $this->getServiceManager()->get(\Zend\Authentication\AuthenticationService::class);
            $authService->authenticate();

            $container = new Container('auth');
            $returnUrl = $container->returnTo;
            if (!$returnUrl) {
                $returnUrl = $this->url()->fromRoute('dashboard', [], ['force_canonical' => true]);
            }
            
            unset($container->returnTo);
            $returnUrl = urlencode($returnUrl);
            
            return $this->redirect()->toUrl("{$configuration['ers']['basePath']}{$configuration['ers']['loginUri']}?RET_ADDR={$returnUrl}");
        }

        $viewModel = new ViewModel();
        $viewModel->setVariable('allowLogin', $configuration['allow_login']);
        return $viewModel;
    }
    
    public function logoutAction()
    {
        $authService = $this->getServiceManager()->get(\Zend\Authentication\AuthenticationService::class);
        $authService->clearIdentity();
        
        $session = $this->getServiceManager()->get(\Zend\Session\SessionManager::class);
        $session->forgetMe();

        
        
        //TODO :: Invalidate the SSO object
        
        $configuration = $this->getServiceManager()->get('Configuration');
        
        $viewModel = new ViewModel();
        $viewModel->setVariable('allowLogin', $configuration['allow_login']);
        return $viewModel;
    }
    
    public function profileAction()
    {
        //TODO :: This should be doable by just invoking the plugin but I'm having issues with that
        if (!$this->getServiceManager()->get(\Zend\Authentication\AuthenticationService::class)->hasIdentity()) {
            return $this->redirect()->toRoute('error/404');
        }
        
        $configuration = $this->getServiceManager()->get('Configuration');
        return $this->redirect()->toUrl("{$configuration['ers']['basePath']}{$configuration['ers']['profileUri']}");
    }
}
