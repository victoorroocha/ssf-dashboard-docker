<?php
namespace Application;

use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

class Module
{
    public function getConfig(): array
    {
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $viewModel = $e->getViewModel();  // Acesse o ViewModel da requisição atual

        // Recupera a sessão e define a variável globalmente
        $session = new Container('auth');
        $nomeUsuario = isset($session->user) ? $session->user['nome'] : 'Usuário';

        // Define a variável global para todas as views
        $viewModel->setVariable('nomeUsuario', $nomeUsuario);


        // Mandar o menu para todas views.
        $serviceManager = $application->getServiceManager();
        // Obtém o MenuRepository
        $menuRepository = $serviceManager->get('Application\Repository\MenuRepository');
        $userId = isset($session->user) ? $session->user['id'] : null;
        $isAdmin = isset($session->user) ? $session->user['role'] === 'Administrador' : null;
        if (isset($session->user)) {
            $menus = $menuRepository->fetchAllowedMenus($userId, $isAdmin);
            // Compartilha os menus com todas as views
            $viewModel = $application->getMvcEvent()->getViewModel();
            $viewModel->setVariable('menus', $menus);
        }

    }

    public function checkAuthentication(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();  // Obtém as informações de rota
        $session = new Container('auth');  // Obtém a sessão de autenticação

        // Verifica se a ação requer autenticação
        $controllerName = $routeMatch->getParam('controller'); // Nome do controlador

        // Verifica se o controlador está dentro do namespace protegido
        if (strpos($controllerName, 'Application\Controller') === 0) {
            if (!isset($session->user)) {
                // Realiza o redirecionamento diretamente com a aplicação
                $application = $e->getApplication();
                $url = $application->getServiceManager()->get('ViewHelperManager')->get('url');
                $url = $url('login');  // Gera a URL para o login

                // Redireciona para a página de login
                $response = $e->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);  // Status para redirecionamento
                return $response;
            }
        }
    }
}
