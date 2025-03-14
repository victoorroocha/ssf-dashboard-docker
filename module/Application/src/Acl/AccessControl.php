<?php

namespace Application\Acl;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;

class AccessControl
{
    private $acl;

    public function __construct()
    {
        $this->acl = new Acl();

        // 1️⃣ Definir as Roles
        $this->defineRoles();

        // 2️⃣ Definir as Controllers como Resources
        $this->defineResources();

        // 3️⃣ Definir as Permissões das Roles para cada Controller
        $this->definePermissions();
    }

    private function defineRoles()
    {
        $this->acl->addRole(new Role('Convidado'))
                  ->addRole(new Role('Auxiliar'))
                  ->addRole(new Role('Assistente'))
                  ->addRole(new Role('Analista'))
                  ->addRole(new Role('Coordenador'))
                  ->addRole(new Role('Gerente'))
                  ->addRole(new Role('Diretor'))
                  ->addRole(new Role('Administrador')); 
    }

    private function defineResources()
    {
        $controllers = [
            'BaseController',
            'DbController',
            'IndexController',
            'LoginController',
            'CreditoECobrancaController',
            'UsuarioController',
            'MenuController',
        ];

        foreach ($controllers as $controller) {
            $this->acl->addResource(new Resource($controller));
        }
    }

    private function definePermissions()
    {
        // Administrador
        $this->acl->allow('Administrador');

        // Diretor 
        $this->acl->allow('Diretor', 'CreditoECobrancaController', ['controleRecebimento','getLookupSafra','listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']);
        $this->acl->allow('Diretor', 'UsuarioController', ['perfilUsuario']);

        // Gerente 
        $this->acl->allow('Gerente', 'CreditoECobrancaController', ['controleRecebimento','getLookupSafra','listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']);
        $this->acl->allow('Gerente', 'UsuarioController', ['perfilUsuario']);

        // Coordenador 
        $this->acl->allow('Coordenador', 'CreditoECobrancaController', ['controleRecebimento','getLookupSafra','listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']);
        $this->acl->allow('Coordenador', 'UsuarioController', ['perfilUsuario']);

        // Analista
        $this->acl->allow('Analista', 'CreditoECobrancaController', ['controleRecebimento', 'getLookupSafra', 'listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']);
        $this->acl->allow('Analista', 'UsuarioController', ['perfilUsuario']);

        // Assistente 
        $this->acl->allow('Assistente', 'CreditoECobrancaController', ['controleRecebimento', 'getLookupSafra', 'listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']); 
        $this->acl->allow('Assistente', 'UsuarioController', ['perfilUsuario']);

        // Auxiliar 
        $this->acl->allow('Auxiliar', 'CreditoECobrancaController', ['controleRecebimento', 'getLookupSafra', 'listControleRecebimento','saveControleRecebimento','controleRecebimentoViewFinanceiro','listControleRecebimentoEnvioFinanceiro']); 
        $this->acl->allow('Auxiliar', 'UsuarioController', ['perfilUsuario']);

        // Convidado
        $this->acl->allow('Convidado', 'CreditoECobrancaController', ['controleRecebimento']);
    }

    public function getAcl()
    {
        return $this->acl;
    }
}
