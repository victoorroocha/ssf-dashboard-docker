<?php

namespace Application\Service;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Crypt\Password\Bcrypt;

class AuthService
{
    private $dbAdapter;
    
    public function __construct(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    
        error_log('Configuração do adaptador no AuthService: ' . print_r($dbAdapter->getDriver()->getConnection()->getConnectionParameters(), true));
    }

    public function authenticate($email, $senha)
    {
        $sql = new Sql($this->dbAdapter);
        $select = $sql->select('usuario');
        $select->where(['email' => $email]);

        error_log('Antes de preparar a declaração SQL');
        try {
            $statement = $sql->prepareStatementForSqlObject($select);
            error_log('Depois de preparar a declaração SQL');
            $result = $statement->execute();
        } catch (\Exception $e) {
            error_log('Erro ao autenticar: ' . $e->getMessage());
            exit;
            return null;
        }

        if ($result->count() == 1) {
            $user = $result->current();
    
            // Verifica se o usuário está ativo
            if (!$user['ativo']) {
                return null; // Não permite a autenticação se o usuário não estiver ativo
            }
    
            $bcrypt = new Bcrypt();
    
            if ($bcrypt->verify($senha, $user['senha'])) {
                return $user;
            }
        }
        
        return null; // Se não encontrar o usuário ou a senha não corresponder, retorna null
    }
}