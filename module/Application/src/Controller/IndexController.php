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
       
        $session = new Container('auth');


        if (isset($session->user)) {
            // Usuário autenticado, renderiza a página inicial
            return new ViewModel([
                'nomeUsuario' => $session->user['nome'] , 
            ]);
        } else {
            // Usuário não autenticado, redireciona para a página de login
            return $this->redirect()->toRoute('login');
        }
    }
}