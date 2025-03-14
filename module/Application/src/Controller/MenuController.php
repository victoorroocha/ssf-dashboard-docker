<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Repository\MenuRepository;
use Laminas\Session\Container;
use Laminas\Permissions\Acl\Acl;
use Laminas\View\Model\JsonModel;


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


    #region Gestão Menus
        public function gestaoMenuAction()
        {
            $session = new Container('auth');
        
            if (!isset($session->user)) {
                return $this->redirect()->toRoute('login');
            }

            return new ViewModel();
        }
        /**
         * Lista todos os menus.
         */
        public function listarMenusAction()
        {
            try {
                $menus = $this->menuRepository->listarMenus();

                return new JsonModel([
                    'success' => true,
                    'data' => $menus,
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao listar menus: ' . $e->getMessage(),
                ]);
            }
        }
        /**
         * Insere um novo menu.
         */
        public function inserirMenuAction()
        {
            if (!$this->getRequest()->isPost()) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ]);
            }

            $data = json_decode($this->getRequest()->getContent(), true);

            $data['parent_id'] = $data['parent_id'] == 0 ? null : $data['parent_id'];

            try {
                $this->menuRepository->inserirMenu($data);
                return new JsonModel([
                    'success' => true,
                    'message' => 'Menu inserido com sucesso!',
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao inserir menu: ' . $e->getMessage(),
                ]);
            }
        }
        /**
         * Atualiza um menu existente.
         */
        public function atualizarMenuAction()
        {
            if (!$this->getRequest()->isPut()) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ]);
            }

            $data = json_decode($this->getRequest()->getContent(), true);

            try {
                $this->menuRepository->atualizarMenu($data);
                return new JsonModel([
                    'success' => true,
                    'message' => 'Menu atualizado com sucesso!',
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao atualizar menu: ' . $e->getMessage(),
                ]);
            }
        }
        /**
         * Exclui um menu.
         */
        public function excluirMenuAction()
        {
            if (!$this->getRequest()->isDelete()) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ]);
            }

            $data = json_decode($this->getRequest()->getContent(), true);

            try {
                $this->menuRepository->excluirMenu($data['id']);
                return new JsonModel([
                    'success' => true,
                    'message' => 'Menu excluído com sucesso!',
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao excluir menu: ' . $e->getMessage(),
                ]);
            }
        }
    #endRegion


    #region Gestão Usuario Menu
        public function gestaoUsuarioMenuAction()
        {
            $session = new Container('auth');
        
            if (!isset($session->user)) {
                return $this->redirect()->toRoute('login');
            }

            return new ViewModel();
        }
        /**
         * Lista os menus associados a um usuário.
         */
        public function listarMenusPorUsuarioAction()
        {
            $usuarioId = $this->params()->fromQuery('usuario_id');

            try {
                $menus = $this->menuRepository->listarMenusPorUsuario($usuarioId);
                return new JsonModel([
                    'success' => true,
                    'data' => $menus,
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao listar menus do usuário: ' . $e->getMessage(),
                ]);
            }
        }
        /**
         * Associa menus a um usuário.
         */
        public function associarMenusAoUsuarioAction()
        {
            if (!$this->getRequest()->isPost()) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ]);
            }

            $data = json_decode($this->getRequest()->getContent(), true);
            $usuarioId = $data['usuario_id'];
            $menuIds = $data['menu_ids'];

            try {
                $this->menuRepository->associarMenusAoUsuario($usuarioId, $menuIds);
                return new JsonModel([
                    'success' => true,
                    'message' => 'Menus associados com sucesso!',
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao associar menus: ' . $e->getMessage(),
                ]);
            }
        }
        /**
         * Remove todas as associações de menus de um usuário.
         */
        public function removerMenusDoUsuarioAction()
        {
            if (!$this->getRequest()->isDelete()) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Método não permitido.',
                ]);
            }

            $data = json_decode($this->getRequest()->getContent(), true);
            $usuarioId = $data['usuario_id'];

            try {
                $this->menuRepository->removerMenusDoUsuario($usuarioId);
                return new JsonModel([
                    'success' => true,
                    'message' => 'Menus removidos com sucesso!',
                ]);
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao remover menus: ' . $e->getMessage(),
                ]);
            }
        }
    #endRegion
}