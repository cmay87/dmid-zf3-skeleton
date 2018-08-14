<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
    'allow_login' => true,
    'auth_storage_namespace' => 'auth_storage_production',
    'ers' => [
        'apiUrl'     => 'api',
        'basePath' => 'https://ers.cr.usgs.gov/',
        'client_secret' => '',
        'loginUri'   => 'login/',
        'profileUri' => 'profile/'
    ],
    'log' => [
        'consoleLogPath' => '/home/apache/logs/dds-' . APPLICATION_ENV . '-console.log',
        'logPath' => '/home/apache/logs/dds-' . APPLICATION_ENV . '-http.log'
    ],
    'm2m' => [
        'endpoint' => 'https://earthexplorer.usgs.gov/inventory/json/v/1.4.1/',
        'username' => '',
        'password' => ''
    ],    
    'session' => [
        'config' => [
            'class' => \Zend\Session\Config\SessionConfig::class,
            'options' => [
                'name' => 'dds_' . APPLICATION_ENV,
                'remember_me_seconds' => 7200,
                'gc_maxlifetime' => 7200,
            ]
        ],
        'storage' => \Zend\Session\Storage\SessionArrayStorage::class,
        'validators' => [
            \Zend\Session\Validator\RemoteAddr::class,
            \Zend\Session\Validator\HttpUserAgent::class,
        ]
    ]
];
