<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Service\OracleService;

class DbController extends AbstractActionController
{
    private $pgAdapter;
    private $oracleService;

    // Ambos os adaptadores são injetados
    public function __construct(Adapter $pgAdapter, OracleService $oracleService = null)
    {
        $this->pgAdapter = $pgAdapter;
        $this->oracleService = $oracleService;
    }

    public function testAction()
    {
        // Usando PostgreSQL
        $pgTime = "não conectado";
        try {
            $sqlPg = "SELECT TO_CHAR(now(), 'YYYY-MM-DD HH24:MI:SS') as pg_time";
            $statementPg = $this->pgAdapter->query($sqlPg);
            $resultPg = $statementPg->execute()->current();
            $pgTime = isset($resultPg['pg_time']) ? $resultPg['pg_time'] : "não conectado";
        } catch (\Exception $e) {
            $pgTime = "Erro ao conectar no PostgreSQL: " . $e->getMessage();
        }

        // Usando Oracle, se disponível
        $oracleTime = "não conectado";
        if ($this->oracleService) {
            try {
                $oracleTime = $this->oracleService->executeQuery("SELECT TO_CHAR(SYSDATE, 'YYYY-MM-DD HH24:MI:SS') as oracle_time FROM dual");
            } catch (\Exception $e) {
                $oracleTime = "Erro ao conectar no Oracle: " . $e->getMessage();
            }
        }

        return new ViewModel([
            'pg_time'     => $pgTime,
            'oracle_time' => $oracleTime,
        ]);
    }
}
