<?php
namespace Application\Service;

class OracleService
{
    private $username;
    private $password;
    private $connection_string;
    private $connection;

    public function __construct($username, $password, $connection_string)
    {
        $this->username = $username;
        $this->password = $password;
        $this->connection_string = $connection_string;
        $this->connect();
    }

    private function connect()
    {
        $this->connection = oci_connect($this->username, $this->password, $this->connection_string);

        if (!$this->connection) {
            throw new \Exception("Erro de conexão Oracle: " . oci_error()['message']);
        }
    }

    /**
     * Executa uma consulta no banco Oracle e retorna os resultados como um array.
     * @param string $sql A consulta SQL a ser executada.
     * @return array Retorna um array de resultados.
     */
    public function executeQuery($sql)
    {
        // Prepara a consulta
        $stid = oci_parse($this->connection, $sql);

        // Executa a consulta
        if (!oci_execute($stid)) {
            $error = oci_error($stid);
            throw new \Exception("Erro ao executar a consulta: " . $error['message']);
        }

        // Armazena os resultados
        $results = [];
        while ($row = oci_fetch_assoc($stid)) {
            $results[] = $row;
        }

        // Libera a memória da consulta
        oci_free_statement($stid);

        // Retorna os resultados como um array
        return $results;
    }

    public function __destruct()
    {
        // Fecha a conexão quando o objeto for destruído
        oci_close($this->connection);
    }
}
