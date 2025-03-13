<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Permissions\Acl\Acl;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;

abstract class BaseController extends AbstractActionController
{
    protected $acl;
    protected $session;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
        $this->session = new Container('auth');
    }

    public function onDispatch(\Laminas\Mvc\MvcEvent $e)
    {
        // Verifica se o usuário está autenticado
        if (!isset($this->session->user)) {
            return $this->redirect()->toRoute('login');
        }

        // Obtém a role do usuário da sessão
        $role = $this->session->user['role'];

        // Obtém o nome da controller e da action atual
        $controller = $e->getRouteMatch()->getParam('controller');
        $action = $e->getRouteMatch()->getParam('action');

        // Remove o namespace da controller para obter o nome simples
        $controller = substr($controller, strrpos($controller, '\\') + 1);

        // Converte o nome da action de kebab-case para camelCase
        $action = $this->kebabToCamelCase($action);

        // Remove o sufixo "Action" do nome da action (caso exista)
        $action = str_replace('Action', '', $action);

        // Verifica se o usuário tem permissão para acessar a action atual
        if (!$this->acl->isAllowed($role, $controller, $action)) {
            // Verifica se a action retorna JSON
            if ($this->isJsonAction()) {
                // Retorna uma resposta JSON com erro de permissão
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Você não tem permissão para acessar este recurso.'
                ], 403); // Código HTTP 403: Forbidden
            }

            // Caso contrário, redireciona para a página de erro
            return $this->redirect()->toRoute('error', ['action' => 'unauthorized']);
        }

        // Continua a execução normal da action
        return parent::onDispatch($e);
    }

    /**
     * Verifica se a action retorna JSON.
     */
    protected function isJsonAction()
    {
        // Verifica o cabeçalho 'Accept' da requisição
        $acceptHeader = $this->getRequest()->getHeader('Accept');
        if ($acceptHeader && strpos($acceptHeader->getFieldValue(), 'application/json') !== false) {
            return true;
        }

        // Verifica se a action retorna um JsonModel
        $actionResponse = parent::onDispatch($this->getEvent());
        return $actionResponse instanceof JsonModel;
    }

    /**
     * Retorna uma resposta JSON.
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        return $response;
    }

    /**
     * Converte kebab-case para camelCase.
     */
    protected function kebabToCamelCase($string)
    {
        // Remove hífens e converte a primeira letra após cada hífen para maiúscula
        $string = str_replace('-', '', ucwords($string, '-'));
        // Converte a primeira letra para minúscula (camelCase)
        $string = lcfirst($string);
        return $string;
    }
}