<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {

        error_log('Caiu no indexxxxxxxxxxxxxxxxxxxxx'); // Log simples para verificar execução
        // Inicializa o container de sessão 'auth'
        $session = new Container('auth');

        // Verifica se a chave 'user' existe e contém dados
        if (isset($session->user) && !empty($session->user)) {
            // Usuário autenticado, renderiza a página inicial com dados do usuário
            return new ViewModel([
                'nomeUsuario' => $session->user['nome'], 
            ]);
        } else {
            // Usuário não autenticado, redireciona para a página de login
            return $this->redirect()->toRoute('login');
        }
    }
}
