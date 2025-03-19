<?php
namespace Application\Repository;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

class MenuRepository
{
    private $tableGateway;
    private $userMenuTableGateway;
    private $adapter; // Declare a propriedade $adapter

    public function __construct(Adapter $adapter)
    {
        $this->tableGateway = new TableGateway('menu', $adapter);
        $this->userMenuTableGateway = new TableGateway('usuario_menu', $adapter);
        $this->adapter = $adapter; // Atribua o valor à propriedade declarada
    }

    /**
     * Busca todos os menus (sem filtro de permissões).
     */
    public function fetchAll()
    {
        // Busca todos os menus do banco de dados
        $select = $this->tableGateway->getSql()->select();
        $select->order('order ASC'); 
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

    private function organizeMenus(array $menus)
    {
        $menuGroups = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === null) {
                // Menu de nível superior
                $menuGroups[$menu['id']] = [
                    'label' => $menu['label'],
                    'icon' => $menu['icon'],
                    'order' => $menu['order'], // Inclui o campo order
                    'subitems' => $this->getSubmenus($menus, $menu['id']), // Busca submenus
                ];
            }
        }
    
        // Ordena os menus principais pelo campo 'order'
        usort($menuGroups, fn($a, $b) => $a['order'] <=> $b['order']);
    
        return array_values($menuGroups);
    }
    
    private function getSubmenus(array $menus, $parentId)
    {
        $submenus = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === $parentId) {
                // Submenu
                $submenu = [
                    'label' => $menu['label'],
                    'icon' => $menu['icon'],
                    'order' => $menu['order'], // Inclui o campo order
                    'subsubitems' => $this->getSubsubmenus($menus, $menu['id']), // Busca subsubmenus
                ];
                $submenus[] = $submenu;
            }
        }
    
        // Ordena os submenus pelo campo 'order'
        usort($submenus, fn($a, $b) => $a['order'] <=> $b['order']);
    
        return $submenus;
    }
    
    private function getSubsubmenus(array $menus, $parentId)
    {
        $subsubmenus = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === $parentId) {
                // Subsubmenu
                $subsubmenus[] = [
                    'label' => $menu['label'],
                    'link' => $menu['link'],
                    'order' => $menu['order'], // Inclui o campo order
                ];
            }
        }
    
        // Ordena os subsubmenus pelo campo 'order'
        usort($subsubmenus, fn($a, $b) => $a['order'] <=> $b['order']);
    
        return $subsubmenus;
    }

    #region Gestão Menus
    /**
     * Lista todos os menus, organizados hierarquicamente.
     */
    public function listarMenus()
    {
        $sql = 'SELECT id, parent_id, label, link, icon, "order" FROM menu ORDER BY "order"';
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();

        $menus = [];
        foreach ($result as $row) {
            $menus[] = $row;
        }

        return $menus;
    }

    /**
     * Insere um novo menu.
     */
    public function inserirMenu(array $data)
    {
        $sql = 'INSERT INTO menu (parent_id, label, link, icon, "order") 
                VALUES (:parent_id, :label, :link, :icon, :order)';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([
            ':parent_id' => $data['parent_id'] ?? null,
            ':label' => $data['label'],
            ':link' => $data['link'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':order' => $data['order'],
        ]);
    }

    /**
     * Atualiza um menu existente.
     */
    public function atualizarMenu(array $data)
    {
        $sql = 'UPDATE menu 
                SET parent_id = :parent_id, label = :label, link = :link, icon = :icon, "order" = :order 
                WHERE id = :id';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([
            ':id' => $data['id'],
            ':parent_id' => $data['parent_id'] ?? null,
            ':label' => $data['label'],
            ':link' => $data['link'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':order' => $data['order'],
        ]);
    }

    /**
     * Exclui um menu e seus filhos (cascata).
     */
    public function excluirMenu($id)
    {
        $sql = 'DELETE FROM menu WHERE id = :id';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([
            ':id' => $id,
        ]);
    }
    #endRegion

    #region Gestão Usuario Menu
    /**
     * Lista os menus associados a um usuário.
     */
    public function listarMenusPorUsuario($usuarioId)
    {
        $sql = 'SELECT menu_id FROM usuario_menu WHERE usuario_id = :usuario_id';
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([':usuario_id' => $usuarioId]);

        $menus = [];
        foreach ($result as $row) {
            $menus[] = $row['menu_id'];
        }

        return $menus;
    }

    /**
     * Associa menus a um usuário.
     */
    public function associarMenusAoUsuario($usuarioId, array $menuIds)
    {
        // Remove associações existentes
        $this->removerMenusDoUsuario($usuarioId);

        // Insere as novas associações
        foreach ($menuIds as $menuId) {
            $sql = 'INSERT INTO usuario_menu (usuario_id, menu_id) VALUES (:usuario_id, :menu_id)';
            $statement = $this->adapter->createStatement($sql);
            $statement->execute([
                ':usuario_id' => $usuarioId,
                ':menu_id' => $menuId,
            ]);
        }
    }

    /**
     * Remove todas as associações de menus de um usuário.
     */
    public function removerMenusDoUsuario($usuarioId)
    {
        $sql = 'DELETE FROM usuario_menu WHERE usuario_id = :usuario_id';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([':usuario_id' => $usuarioId]);
    }
    #endRegion
}