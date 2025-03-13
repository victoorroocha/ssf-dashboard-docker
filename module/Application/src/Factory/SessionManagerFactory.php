<?php

namespace Application\Factory;

use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\ValidatorChain;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SessionManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        
        $config = $container->get('config')['session_config'] ?? [];  

        $sessionConfig = new SessionConfig();
        if (isset($config['config']['options'])) {
            $sessionConfig->setOptions($config['config']['options']);
        }

        $sessionStorage = new SessionArrayStorage();

        $sessionManager = new SessionManager($sessionConfig, $sessionStorage);

        if (isset($config['validators'])) {
            $validatorChain = $sessionManager->getValidatorChain();
            foreach ($config['validators'] as $validator) {
                $validatorInstance = new $validator();
                $validatorChain->attach('session.validate', [$validatorInstance, 'isValid']);
            }
        }

        return $sessionManager;
    }
}
