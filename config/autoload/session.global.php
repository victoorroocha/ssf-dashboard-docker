<?php
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Validator\RemoteAddr;
use Laminas\Session\Validator\HttpUserAgent;

return [
    'session_config' => [  // Alterei para session_config
        'config' => [
            'class'   => SessionConfig::class,
            'options' => [
                'name'           => 'SSF',
                'cookie_lifetime' => 3600,
                'gc_maxlifetime'  => 7200,
            ],
        ],
        'storage'   => SessionArrayStorage::class,
        'validators' => [
            RemoteAddr::class,
            HttpUserAgent::class,
        ],
    ],
];

