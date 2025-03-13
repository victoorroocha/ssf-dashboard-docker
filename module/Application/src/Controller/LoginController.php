<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Application\Service\AuthService;
use Laminas\Session\Container;

class LoginController extends AbstractActionController
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function loginAction()
    {
        $request = $this->getRequest();
        $session = new Container('auth');
    
        if ($request->isPost()) {
            $data = json_decode($request->getContent(), true) ?? $request->getPost()->toArray();
            $email = $data['email'] ?? null;
            $senha = $data['senha'] ?? null;
    
            if (!$email || !$senha) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Email e senha são obrigatórios.'
                ]);
            }
    
            $user = $this->authService->authenticate($email, $senha);
    
            if ($user) {
                $session->user = $user;
    
                if ($request->isXmlHttpRequest()) {
                    return new JsonModel([
                        'success' => true,
                        'user'    => $user
                    ]);
                } else {
                    // Redireciona para a página inicial se não for uma requisição AJAX
                    return $this->redirect()->toRoute('home');
                }
            } else {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Usuário ou senha inválidos.'
                ]);
            }
        }
    
        $this->layout()->setTemplate('layout/login');
        return new ViewModel();
    }


    public function logoutAction()
    {
        // Obtém a sessão de autenticação
        $session = new Container('auth');

        // Destrói a sessão
        $session->getManager()->destroy();

        // Redireciona para a tela de login após o logout
        return $this->redirect()->toRoute('login');
    }
}
