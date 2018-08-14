<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;

return [
    'acl' => [
        'resources' => [
            Controller\AuthController::class => [
                'all' => 'guest'
            ],            
            Controller\HighvolumeController::class => [
                'all' => 'user'
            ],
            Controller\IndexController::class => [
                'all' => 'user'
            ],
            Controller\QueueController::class => [
                'all' => 'user'
            ],
            Controller\SettingsController::class => [
                'all' => 'user'
            ]
        ]
    ],
    'router' => [
        'routes' => [
            'dashboard' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/dashboard',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index'
                    ],
                    'may_terminate' => true,
                    'child_routes' => [
                        'action' => [
                            'type' => Segment::class,
                            'options' => [
                                'route'    => '/:action[/]',
                                'defaults' => [
                                    'controller' => Controller\IndexController::class,
                                    'action'     => 'index'
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'highvolume' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/highvolume',
                    'defaults' => [
                        'controller' => Controller\HighvolumeController::class,
                        'action'     => 'index'
                    ],
                    'may_terminate' => true,
                    'child_routes' => [
                        'action' => [
                            'type' => Segment::class,
                            'options' => [
                                'route'    => '/:action[/]',
                                'defaults' => [
                                    'controller' => Controller\HighvolumeController::class,
                                    'action'     => 'index'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'home' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '[/]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index'
                    ],
                ],
            ],
            'login' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/login[/]',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'login'
                    ],
                ],
            ],      
            'logout' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/logout[/]',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'logout'
                    ],
                ],
            ], 
            'profile' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/profile[/]',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'profile'
                    ],
                ],
            ],             
            'queue' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/queue[/:action[/]]',
                    'defaults' => [
                        'controller' => Controller\QueueController::class,
                        'action'     => 'index'
                    ],
                ]
            ],
            'session-update' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/session-update[/]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'sessionupdate'
                    ],
                ],
            ],
            'settings' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/settings',
                    'defaults' => [
                        'controller' => Controller\SettingsController::class,
                        'action'     => 'index'
                    ],
                    'may_terminate' => true,
                    'child_routes' => [
                        'action' => [
                            'type' => Segment::class,
                            'options' => [
                                'route'    => '/:action[/]',
                                'defaults' => [
                                    'controller' => Controller\SettingsController::class,
                                    'action'     => 'index'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\AuthController::class => Controller\ControllerFactory::class,
            Controller\HighvolumeController::class => Controller\ControllerFactory::class,
            Controller\IndexController::class => Controller\ControllerFactory::class,
            Controller\QueueController::class => Controller\ControllerFactory::class,
            Controller\SettingsController::class => Controller\ControllerFactory::class,
        ],
    ],
    'event_manager'   => [
        'lazy_listeners' => [
            [
                'listener' => Listener\NavigationListener::class,
                'method'   => 'addAcl',
                'event'    => \Zend\Mvc\MvcEvent::EVENT_RENDER,
                'priority' => -100,
            ],
        ],
    ],
    'navigation' => [
        'default' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'resource' => Controller\IndexController::class,
            ],
            [
                'label' => 'Pending Downloads (0)',
                'route' => 'queue',
                'action' => 'pending',
                'resource' => Controller\QueueController::class,
            ],
            [
                'label' => 'Download Orders',
                'route' => 'queue',
                'action' => 'orders' ,
                'resource' => Controller\QueueController::class,
            ],
            [
                'label' => 'High Volume',
                'route' => 'highvolume',
                'resource' => Controller\HighVolumeController::class,
            ],
            [
                'label' => 'History',
                'route' => 'queue',
                'action' => 'history' ,
                'resource' => Controller\QueueController::class,
            ],
            [
                'label' => 'Admin',
                'route' => 'admin_default',
                'resource' => \Admin\Controller\IndexController::class,
            ],
        ]
    ],
    'service_manager' => [
        'factories' => [
            \Zend\Authentication\AuthenticationService::class => Authentication\ServiceFactory::class,
            Authentication\Acl::class => Authentication\AclFactory::class,
            Listener\NavigationListener::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
            'InventoryService' => Service\InventoryServiceFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'profile_overlay'         => __DIR__ . '/../view/partials/profile_overlay.phtml',
            'session_clock'           => __DIR__ . '/../view/layout/session_clock.phtml'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
