<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\Controller\LoginController;

class LoginControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Recupera o AuthService já configurado no service_manager
        $authService = $container->get('Application\Service\AuthService');
        return new LoginController($authService);
    }
}
