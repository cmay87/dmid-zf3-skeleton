<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Console\Request as ConsoleRequest;
use Zend\EventManager\EventInterface;
use Zend\EventManager\LazyListenerAggregate;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\Mvc\MvcEvent as MvcEvent;
use Zend\Session\SessionManager;
use Zend\Session\Config\SessionConfig;
use Zend\Session\Container;
use Zend\Session\Validator;

class Module implements BootstrapListenerInterface
{
    const VERSION = '3.0.3-dev';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    public function bootstrapSession(EventInterface $e)
    {
        $session = $e->getApplication()
            ->getServiceManager()
            ->get(SessionManager::class);
        $session->start();

        $container = new Container('initialized');

        if (isset($container->init)) {
            $container->lastAccess = time();
            return;
        }

        $serviceManager = $e->getApplication()->getServiceManager();
        $request        = $serviceManager->get('Request');

        $session->regenerateId(true);
        $container->init          = 1;
        $container->remoteAddr    = $request->getServer()->get('REMOTE_ADDR');
        $container->httpUserAgent = $request->getServer()->get('HTTP_USER_AGENT');

        $config = $serviceManager->get('Config');
        if (! isset($config['session'])) {
            return;
        }

        $sessionConfig = $config['session'];

        if (! isset($sessionConfig['validators'])) {
            return;
        }

        $chain   = $session->getValidatorChain();

        foreach ($sessionConfig['validators'] as $validator) {
            switch ($validator) {
            case Validator\HttpUserAgent::class:
                    $validator = new $validator($container->httpUserAgent);
                    break;
                    case Validator\RemoteAddr::class:
                    $validator  = new $validator($container->remoteAddr);
                    break;
                default:
                    $validator = new $validator();
            }

            $chain->attach('session.validate', array($validator, 'isValid'));
        }
    }
    
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Logger' => function($container)
                {
                    $config = $container->get('config');
                    
                    $logger = new \Zend\Log\Logger();
                    
                    $logPath = null;
                    if (php_sapi_name() === 'cli') {
                        $logPath = $config['log']['consoleLogPath'];
                    } else {
                        $logPath = $config['log']['logPath'];
                    }
                    
                    $writer = new \Zend\Log\Writer\Stream($logPath);
                    $writer->addFilter(new \Zend\Log\Filter\Priority(\Zend\Log\Logger::INFO));
                    $logger->addWriter($writer);
                    
                    $debugWriter = new \Zend\Log\Writer\Stream(str_replace('.log', '-debug.log', $logPath));
                    $debugWriter->addFilter(new \Zend\Log\Filter\Priority(\Zend\Log\Logger::DEBUG, '>='));
                    $logger->addWriter($debugWriter);
                    
                    \Zend\Log\Logger::registerErrorHandler($logger);
                    
                    return $logger;
                },
                SessionManager::class => function ($container) {
                    $config = $container->get('config');
                    if (! isset($config['session'])) {
                        $sessionManager = new SessionManager();
                        Container::setDefaultManager($sessionManager);
                        return $sessionManager;
                    }

                    $session = $config['session'];

                    $sessionConfig = null;
                    if (isset($session['config'])) {
                        $class = isset($session['config']['class'])
                            ?  $session['config']['class']
                            : SessionConfig::class;

                        $options = isset($session['config']['options'])
                            ?  $session['config']['options']
                            : [];

                        $sessionConfig = new $class();
                        $sessionConfig->setOptions($options);
                    }

                    $sessionStorage = null;
                    if (isset($session['storage'])) {
                        $class = $session['storage'];
                        $sessionStorage = new $class();
                    }

                    $sessionSaveHandler = null;
                    if (isset($session['save_handler'])) {
                        // class should be fetched from service manager
                        // since it will require constructor arguments
                        $sessionSaveHandler = $container->get($session['save_handler']);
                    }

                    $sessionManager = new SessionManager(
                        $sessionConfig,
                        $sessionStorage,
                        $sessionSaveHandler
                    );

                    Container::setDefaultManager($sessionManager);
                    return $sessionManager;
                },
            ],
        ];
    }    
    
    public function onBootstrap(EventInterface $e)
    {
        $application = $e->getApplication();
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();
        $appConfig = $application->getServiceManager()->get('ApplicationConfig');
        $config = $application->getServiceManager()->get('config');

        $request = $e->getRequest();
        
        //Set the event manager loading technique
        if (array_key_exists('event_manager', $config)
            && is_array($config['event_manager'])
            && array_key_exists('lazy_listeners', $config['event_manager'])
        ) {
            $aggregate = new LazyListenerAggregate(
                $config['event_manager']['lazy_listeners'],
                $serviceManager
            );
            $aggregate->attach($eventManager);
        }
        
        //Set the base title
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $headTitleHelper = $viewHelperManager->get('headTitle');
        $headTitleHelper->setSeparator(' - ');
        $headTitleHelper->append($appConfig['site']['title']);
        
        if (APPLICATION_ENV !== 'production') {
            $headTitleHelper->append(ucfirst(APPLICATION_ENV));
        }
        
        //Configure anything view related
        $viewModel = $e->getViewModel();
        $viewRenderer = $serviceManager->get('ViewRenderer');
        $viewModel->sessionTimer = $viewRenderer->partial('session_clock', array('expirationSeconds' => $config['session']['config']['options']['remember_me_seconds']));
        
        //Configure and start the session
        $this->bootstrapSession($e);
        
        //Attach an event dispatch methods if it's not a console request
        if (!($request instanceof ConsoleRequest)) {
            //Enforce HTTPS
            $eventManager->attach(MvcEvent::EVENT_DISPATCH,
                function (MvcEvent $e) {
                    if ($_SERVER['SERVER_PORT'] !== '443') {
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

                        return $response;
                    }
                }, -10);
            
            //Check SSO
            //TODO :: Make sure this isn't going to logout
            if (true) {
                $eventManager->attach(MvcEvent::EVENT_DISPATCH,
                    function (MvcEvent $e) use ($serviceManager) {
                        $authService = $serviceManager->get(\Zend\Authentication\AuthenticationService::class);
                        $logger = $serviceManager->get('Logger');
                        $sso = $serviceManager->get('ErsSSO');
                        
                        $errorUrl = null;
                        
                        try {
                            if ($authService->hasIdentity() && $sso->isCookiePresent()) {
                                if ($sso->validateCookie()) {
                                    $user = $authService->getIdentity();

                                    //We have a user, let's check the last modified date to see if we need to reload the user data
                                    if (!$user->isUserDataCurrent($sso->getLastProfileUpdateTime())) {
                                        //The profile has been updated, so authenticate the cookie again so we can grab the profile info
                                        $sso->authenticateCookie();
                                        $userInfo = $sso->getUser();

                                        //Username/password/contact Id won't change so we don't need to update that
                                        $user->setRoles($userInfo->roles);
                                        $user->setUserModifiedDate($sso->getLastProfileUpdateTime());
                                    }
                                } else {
                                    $logger->debug('Invalid SSO: ' . implode(',', $sso->getErrors()));

                                    //The cookie wasn't authentic so get rid of the user session and kick the user out
                                    $errorUrl = $e->getRequest()->getBaseUrl() . '/logout?sso-invalid';
                                }
                            } else {
                                //Make sure we have a cookie
                                if ($sso->isCookiePresent()) {
                                    //Can we authenticate with the cookie?
                                    if ($sso->authenticateCookie() === true) {
                                        $adapter = new Authentication\Adapter($sso);
                                        $adapter->setServiceManager($serviceManager);
                                        
                                        $authService->authenticate($adapter);
                                    } else {
                                        throw new \Exception('Authentication Failed');
                                    }
                                }
                            }
                        } catch (\Exception $ex) {
                            $logger->debug($ex->getMessage());
                            if ($sso !== null) {
                                $logger->debug('SSO Errors: ' . implode(',', $sso->getErrors(true)));
                                $sso->invalidateCookie();
                            }
                            
                            $errorUrl = $e->getRequest()->getBaseUrl() . '/sso-error';
                        }
                        
                        if ($errorUrl !== null) {
                            $response = $e->getResponse();
                            $response->setStatusCode(302);
                            $response->getHeaders()->addHeaderLine('Location', $errorUrl);

                            return $response;
                        }
                    }, 99);
            }
            
            //Enforce ACL
            $eventManager->attach(MvcEvent::EVENT_DISPATCH,
                function (MvcEvent $e) use ($serviceManager) {
                    $match       = $e->getRouteMatch();
                    $authService = $serviceManager->get(\Zend\Authentication\AuthenticationService::class);
                    $routeName   = $match->getMatchedRouteName();

                    /* Get Controller and Action */
                    $matchedController = $match->getParam('controller');
                    $matchedAction     = $match->getParam('action');

                    /* Default Role */
                    $role = 'guest';

                    /* Check if user exists, if it has authenticated and set role */
                    if ($authService->hasIdentity()) {
                        $role = $authService->getIdentity()->getRoleKey();
                    }

                    /* Valid ACL */
                    $acl = $serviceManager->get(Authentication\Acl::class);
                    if (!$acl->isAllowed($role, $matchedController, $matchedAction)) {
                        $request = $e->getRequest();
                        $response = $e->getResponse();

                        if ($role == 'guest' && $routeName != 'login') {
                            //If this is an AJAX request - just return a 403
                            if (!$request->isXmlHttpRequest()) {
                                //Keep track of where the user was coming from
                                $container = new Container('auth');
                                $container->returnTo = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

                                $match->setParam('controller', Controller\AuthController::class)
                                      ->setParam('action', 'login');
                            } else {
                                $response->setStatusCode(403);
                                return $response;
                            }
                        } else {
                            $response->setStatusCode(403);

                            //If this is an AJAX request - just return a 403
                            if (!$request->isXmlHttpRequest()) {
                                $response->getHeaders()->addHeaderLine('Location', $e->getRequest()->getBaseUrl() . '/404');
                            } else {
                                return $response;
                            }
                        }
                    }
                }, 100);
        }
    }
    
    public function onRenderError($e)
    {
        //Make sure there is actually an error loaded
        if (!$e->isError()) {
            return;
        }
        
        //If it's a console request or a page request, no modifications needed
        $request = $e->getRequest();
        if ($request instanceof ConsoleRequest || !$request->isXmlHttpRequest()) {
            return;
        }
        
        //This is an AJAX Request - Don't include the layout
        $viewModel = $e->getResult();
        $viewModel->setTerminal(true);
        
        $e->setResult($viewModel);
        $e->setViewModel($viewModel);
    }
}
