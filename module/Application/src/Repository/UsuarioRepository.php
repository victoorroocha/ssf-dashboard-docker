<?php
namespace Application\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Crypt\Password\Bcrypt;

class UsuarioRepository
{
    private $adapter;
    private $bcrypt;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->bcrypt = new Bcrypt(); // Instância do Bcrypt para criptografar a senha
    }

    public function listarUsuarios($skip, $take, $sort = null)
    {
        // Query base
        $sql = 'SELECT id, nome, email, role, ativo FROM usuario';

        // Adiciona ordenação, se fornecida
        if ($sort) {
            $sort = json_decode($sort, true);
            $orderBy = array_map(function ($item) {
                return $item['selector'] . ' ' . $item['desc'] ? 'DESC' : 'ASC';
            }, $sort);
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }

        // Adiciona paginação
        $sql .= ' LIMIT :take OFFSET :skip';

        // Executa a query
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([
            ':take' => $take,
            ':skip' => $skip,
        ]);

        // Obtém os dados
        $data = [];
        foreach ($result as $row) {
            $data[] = $row;
        }

        // Conta o total de registros
        $totalCount = $this->adapter->query('SELECT COUNT(*) FROM usuario')->execute()->current()['count'];

        return [
            'data' => $data,
            'totalCount' => $totalCount,
        ];
    }
    public function inserirUsuario(array $data)
    {
        // Valida os dados obrigatórios
        if (empty($data['nome']) || empty($data['email']) || empty($data['role'])) {
            throw new \Exception('Nome, email e função são obrigatórios.');
        }

        // Criptografa a senha, se fornecida
        if (!empty($data['senha'])) {
            $data['senha'] = $this->bcrypt->create($data['senha']);
        }

        // Query de inserção
        $sql = 'INSERT INTO usuario (nome, email, role, ativo, senha) VALUES (:nome, :email, :role, :ativo, :senha)';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':role' => $data['role'],
            ':ativo' => $data['ativo'] ?? false,
            ':senha' => $data['senha'] ?? null,
        ]);
    }
    public function atualizarUsuario(array $data)
    {

        // Verifica se a senha foi enviada e a criptografa
        if (!empty($data['senha'])) {
            $data['senha'] = $this->bcrypt->create($data['senha']);

            // Query de atualização
            $sql = 'UPDATE usuario SET nome = :nome, role = :role, ativo = :ativo, senha = :senha WHERE id = :id';
            $statement = $this->adapter->createStatement($sql);
            $statement->execute([
                ':nome' => $data['nome'],
                ':role' => $data['role'],
                ':ativo' => $data['ativo'] ?? false,
                ':senha' => $data['senha'], 
                ':id' => $data['id'],
            ]);
        } else {
            // Query de atualização
            $sql = 'UPDATE usuario SET nome = :nome, role = :role, ativo = :ativo WHERE id = :id';
            $statement = $this->adapter->createStatement($sql);
            $statement->execute([
                ':nome' => $data['nome'],
                ':role' => $data['role'],
                ':ativo' => $data['ativo'] ?? false,
                ':id' => $data['id'],
            ]);
        }


    }
    public function excluirUsuario($id)
    {
        // Verifica se o ID foi fornecido
        if (empty($id)) {
            throw new \Exception('ID do usuário não fornecido.');
        }

        // Query de exclusão
        $sql = 'DELETE FROM usuario WHERE id = :id';
        $statement = $this->adapter->createStatement($sql);
        $statement->execute([
            ':id' => $id,
        ]);
    }
}