<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Application\Repository\CreditoECobrancaRepository;  // Importar repositório

return [
    'router' => [
        'routes' => [
            'menu' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/menu[/:action]',
                    'defaults' => [
                        'controller' => Controller\MenuController::class,
                        'action'     => 'list',
                    ],
                ],
            ],
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
                        'controller' => Controller\DbController::class, // Certifique-se de que o controlador está correto aqui
                        'action'     => 'test',
                    ],
                ],
            ],
            // Credito e Cobrança
            'credito-e-cobranca' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/credito-e-cobranca[/:action][/:id]',  // Aceita qualquer ação e um ID opcional
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',  // Restringe action a caracteres alfanuméricos e underscores
                        'id'     => '[0-9]+',  // ID opcional deve ser numérico
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
            Controller\CreditoECobrancaController::class => Factory\GenericControllerFactory::class,
            Controller\LoginController::class => Factory\LoginControllerFactory::class,
            Controller\ErrorController::class => Factory\GenericControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
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
            'Application\Repository\MenuRepository' => function($container) {
                return new \Application\Repository\MenuRepository(
                    $container->get('Laminas\Db\Adapter\Adapter')
                );
            },
            CreditoECobrancaRepository::class => InvokableFactory::class, 
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
