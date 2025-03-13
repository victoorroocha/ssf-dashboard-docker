<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Repository\MenuRepository;
use Laminas\Session\Container;
use Laminas\Permissions\Acl\Acl;

class MenuController extends BaseController
{
    private $pgAdapter;
    private $menuRepository;

    public function __construct(Adapter $pgAdapter, MenuRepository $menuRepository = null, Acl $acl)
    {
        parent::__construct($acl); // Chama o construtor da classe base
        $this->pgAdapter = $pgAdapter;
        $this->menuRepository = $menuRepository;
    }

    /**
     * Action para listar os menus.
     */
    public function listAction()
    {
        $session = new Container('auth');

        // Verifica se o usuário está autenticado
        if (!isset($session->user)) {
            return $this->redirect()->toRoute('login');
        }

        // Obtém o ID do usuário logado
        $userId = $session->user['id'];

        // Verifica se o usuário é administrador
        $isAdmin = $session->user['role'] === 'Administrador';

        // Obtém os menus permitidos para o usuário
        $menus = $this->menuRepository->fetchAllowedMenus($userId, $isAdmin);

        // Passa os menus para a view
        return new ViewModel([
            'menus' => $menus,
        ]);
    }
}