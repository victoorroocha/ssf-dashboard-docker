<?php
namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Application\Service\OracleService;

class GenericControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Obtém a configuração dos bancos de dados
        $config = $container->get('config')['db'];

        // Instancia o adaptador do PostgreSQL
        $pgAdapter = new Adapter($config);  // Conexão com PostgreSQL

        // Instancia o serviço Oracle, se necessário
        $oracleService = null;
        if (isset($container->get('config')['oracle'])) {
            $oracleService = $container->get('Application\Service\OracleService');
        }

        // Descobre dinamicamente o repositório com base no nome da controller
        $repositoryClass = $this->getRepositoryForController($requestedName);

        // Obtém o repositório ou define como null
        $repository = $repositoryClass && $container->has($repositoryClass) ? $container->get($repositoryClass) : null;

        // Obtém o ACL
        $acl = $container->get('Application\Acl\AccessControl')->getAcl();

        // Retorna a controller com os parâmetros injetados
        return new $requestedName($pgAdapter, $oracleService, $repository, $acl);
    }

    // Método para determinar qual repositório injetar com base na controller
    private function getRepositoryForController($controllerName)
    {
        // Deriva o nome do repositório automaticamente, com base no nome da controller
        // Exemplo: CreditoECobrancaController -> CreditoECobrancaRepository
        $repositoryClass = str_replace('Controller', 'Repository', $controllerName);
        
        // Verifica se a classe do repositório existe
        return class_exists($repositoryClass) ? $repositoryClass : null;
    }
}