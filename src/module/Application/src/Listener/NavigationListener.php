<?php

namespace Application\Listener;

use Zend\Mvc\MvcEvent;

class NavigationListener
{
    /**
     * @param MvcEvent $event
     */
    public function addAcl(MvcEvent $event)
    {
        // Get service manager
        $serviceManager = $event->getApplication()->getServiceManager();

        // Get view helper plugin manager
        /** @var \Zend\View\HelperPluginManager $helperPluginManager */
        $helperPluginManager = $serviceManager->get('ViewHelperManager');

        // Get navigation plugin
        /** @var \Zend\View\Helper\Navigation $plugin */
        $plugin = $helperPluginManager->get('navigation');

        $authService = $serviceManager->get(\Zend\Authentication\AuthenticationService::class);
        $role = 'guest';

        /* Check if user exists, if it has authenticated and set role */
        if ($authService->hasIdentity()) {
            $role = $authService->getIdentity()->getRoleKey();
        }
        
        $plugin->setAcl($serviceManager->get(\Application\Authentication\Acl::class));
        $plugin->setRole($role);
    }
}
