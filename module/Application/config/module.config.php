<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Application\Repository\CreditoECobrancaRepository;  // Importar repositório

return [
    'db' => [
        'driver'   => 'Pdo_Pgsql',
        'hostname' => 'laminas_postgres', 
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'port'     => '5432',
    ],
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => Controller\LoginController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => Controller\LoginController::class,
                        'action'     => 'logout',
                    ],
                ],
            ],
            'error' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/error[/:action]',
                    'defaults' => [
                        'controller' => Controller\ErrorController::class,
                        'action'     => 'index', 
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            // Teste Banco de Dados
            'db-test' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/db-test',
                    'defaults' => [
                        'controller' => Controller\DbController::class, 
                        'action'     => 'test',
                    ],
                ],
            ],
            'usuario' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/usuario[/:action][/:id]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',  
                        'id'     => '[0-9]+',  
                    ],
                    'defaults' => [
                        'controller' => Controller\UsuarioController::class,
                        'action'     => 'index',  
                    ],
                ],
            ],
            'menu' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/menu[/:action][/:id]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\MenuController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            // Credito e Cobrança
            'credito-e-cobranca' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/credito-e-cobranca[/:action][/:id]',  
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',  
                        'id'     => '[0-9]+',  
                    ],
                    'defaults' => [
                        'controller' => Controller\CreditoECobrancaController::class,
                        'action'     => 'index',  // Action padrão caso nenhuma seja informada
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\MenuController::class => function($container) {
                return new Controller\MenuController(
                    $container->get('Laminas\Db\Adapter\Adapter'), // Adaptador do banco de dados
                    $container->get('Application\Repository\MenuRepository'), // Repositório de menus
                    $container->get('Application\Acl\AccessControl')->getAcl() // ACL
                );
            },
            Controller\DbController::class => Factory\GenericControllerFactory::class,  
            Controller\LoginController::class => Factory\LoginControllerFactory::class,
            Controller\ErrorController::class => Factory\GenericControllerFactory::class,
            Controller\CreditoECobrancaController::class => Factory\GenericControllerFactory::class,
            Controller\UsuarioController::class => Factory\GenericControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Laminas\Db\Adapter\Adapter' => function ($container) {
                $config = $container->get('config')['db'];
                error_log('Configuração do banco de dados no module.config.php: ' . print_r($config, true)); // Log da configuração
                return new \Laminas\Db\Adapter\Adapter($config);
            },
            Laminas\Session\SessionManager::class => Application\Factory\SessionManagerFactory::class,
            'Application\Acl\AccessControl' => function($container) {
                return new \Application\Acl\AccessControl();
            },
            'Application\Service\AuthService' => function($container) {
                // Obtém a instância do adaptador do DB registrada como 'Laminas\Db\Adapter\Adapter'
                return new \Application\Service\AuthService(
                    $container->get('Laminas\Db\Adapter\Adapter')
                );
            },
            'Application\Service\OracleService' => function($container) {
                $config = $container->get('config')['oracle'];  // Supondo que a configuração do Oracle esteja em 'config'
                return new \Application\Service\OracleService(
                    $config['username'],
                    $config['password'],
                    $config['connection_string'],
                    'WE8MSWIN1252'
                );
            },
            CreditoECobrancaRepository::class => InvokableFactory::class, 
            'Application\Repository\UsuarioRepository' => function ($container) {
                $adapter = $container->get('Laminas\Db\Adapter\Adapter');
                return new \Application\Repository\UsuarioRepository($adapter);
            },
            'Application\Controller\UsuarioController' => function ($container) {
                $usuarioRepository = $container->get('Application\Repository\UsuarioRepository');
                return new \Application\Controller\UsuarioController($usuarioRepository);
            }, 
            'Application\Controller\MenuController' => function ($container) {
                $menuRepository = $container->get('Application\Repository\MenuRepository');
                return new \Application\Controller\MenuController($menuRepository);
            }, 
            'Application\Repository\MenuRepository' => function ($container) {
                $adapter = $container->get('Laminas\Db\Adapter\Adapter'); // Certifique-se de que o adapter está sendo injetado
                return new \Application\Repository\MenuRepository($adapter);
            },
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'application' => __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'exception_strategy' => 'error',
    ],
];
