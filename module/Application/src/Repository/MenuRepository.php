<?php
namespace Application\Repository;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

class MenuRepository
{
    private $tableGateway;
    private $userMenuTableGateway;

    public function __construct(Adapter $adapter)
    {
        $this->tableGateway = new TableGateway('menu', $adapter);
        $this->userMenuTableGateway = new TableGateway('usuario_menu', $adapter);
    }

    /**
     * Busca todos os menus (sem filtro de permissões).
     */
    public function fetchAll()
    {
        // Busca todos os menus do banco de dados
        $select = $this->tableGateway->getSql()->select();
        $menus = $this->tableGateway->selectWith($select)->toArray();

        // Organiza os menus em uma estrutura hierárquica
        return $this->organizeMenus($menus);
    }

    /**
     * Busca os menus permitidos para um usuário específico.
     */
    public function fetchAllowedMenus($userId, $isAdmin = false)
    {
        if ($isAdmin) {
            // Se for administrador, retorna todos os menus
            return $this->fetchAll();
        }

        // Busca os IDs dos menus permitidos para o usuário
        $select = $this->userMenuTableGateway->getSql()->select();
        $select->where(['usuario_id' => $userId]);
        $allowedMenuIds = $this->userMenuTableGateway->selectWith($select)->toArray();
        $allowedMenuIds = array_column($allowedMenuIds, 'menu_id');

        if (empty($allowedMenuIds)) {
            return []; // Nenhum menu permitido
        }

        // Busca os menus permitidos
        $select = $this->tableGateway->getSql()->select();
        $select->where->in('id', $allowedMenuIds);
        $menus = $this->tableGateway->selectWith($select)->toArray();

        // Organiza os menus em uma estrutura hierárquica
        return $this->organizeMenus($menus);
    }

    /**
     * Organiza os menus em uma estrutura hierárquica.
     */
    private function organizeMenus(array $menus)
    {
        $menuGroups = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === null) {
                // Menu de nível superior
                $menuGroups[$menu['id']] = [
                    'label' => $menu['label'],
                    'icon' => $menu['icon'],
                    'subitems' => $this->getSubmenus($menus, $menu['id']), // Busca submenus
                ];
            }
        }

        // Remove as chaves numéricas dos arrays
        return array_values($menuGroups);
    }

    /**
     * Busca submenus e subsubmenus recursivamente.
     */
    private function getSubmenus(array $menus, $parentId)
    {
        $submenus = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === $parentId) {
                // Submenu
                $submenu = [
                    'label' => $menu['label'],
                    'icon' => $menu['icon'],
                    'subsubitems' => $this->getSubsubmenus($menus, $menu['id']), // Busca subsubmenus
                ];
                $submenus[] = $submenu;
            }
        }
        return $submenus;
    }

    /**
     * Busca subsubmenus.
     */
    private function getSubsubmenus(array $menus, $parentId)
    {
        $subsubmenus = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === $parentId) {
                // Subsubmenu
                $subsubmenus[] = [
                    'label' => $menu['label'],
                    'link' => $menu['link'],
                ];
            }
        }
        return $subsubmenus;
    }
}