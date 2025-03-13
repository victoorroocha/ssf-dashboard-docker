<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Service\OracleService;
use Application\Repository\CreditoECobrancaRepository; // A importação do repositório continua aqui
use Laminas\View\Model\JsonModel;
use Laminas\Db\Sql\Sql;

class CreditoECobrancaController extends AbstractActionController
{
    private $pgAdapter;
    private $oracleService;
    private $creditoECobrancaRepository;

    // Injeção de dependência (adaptador PostgreSQL, OracleService, e repositório)
    public function __construct(Adapter $pgAdapter, OracleService $oracleService = null, CreditoECobrancaRepository $creditoECobrancaRepository = null)
    {
        $this->pgAdapter = $pgAdapter;
        $this->oracleService = $oracleService;
        $this->creditoECobrancaRepository = $creditoECobrancaRepository;
    }

    public function controleRecebimentoAction()
    {
        return new ViewModel();
    }
    public function getLookupPedidosAction()
    {
        // Verifica se o serviço Oracle está disponível
        if (!$this->oracleService) {
            return new JsonModel([
                'success' => false,
                'message' => 'Serviço Oracle não disponível'
            ]);
        }
    
        try {

            // Recupera o valor do filtro (pesquisa) ou do código do pedido
            $filtro = $this->getRequest()->getQuery('filtro'); 
            $codigo = $this->getRequest()->getQuery('codigo'); 
            
            if (!empty($filtro) || !empty($codigo)) {
                // Consulta dados na Softsul
                $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getPedidosLookupSoftsulQuery($filtro, $codigo) : '';
                $result = [];
                if ($sql) {
                    // Executa a consulta Oracle, caso tenha uma consulta
                    $result = $this->oracleService->executeQuery($sql);
                }
            }


            // Retorna os dados como JSON
            return new JsonModel([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function listControleRecebimentoAction()
    {
        // Verifica se o serviço Oracle está disponível
        if (!$this->oracleService) {
            return new JsonModel([
                'success' => false,
                'message' => 'Serviço Oracle não disponível'
            ]);
        }
    
        try {
                // Consulta no ControleRecebimento postgres
                $sqlControleRecebimento = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getDadosControlRecebimentoQuery() : '';
                $statementPg = $this->pgAdapter->query($sqlControleRecebimento);
                $resultControleRecebimento = $statementPg->execute();

                // Inicializa um array para armazenar os dados
                $data = [];

                // Itera sobre cada linha do resultado e adiciona ao array
                foreach ($resultControleRecebimento as $key => $row) {
                    $row['custom_desconto'] = floatval($row['custom_desconto']);
                    $row['custom_juros'] = floatval($row['custom_juros']);
                    $row['custom_valor_devolvido'] = floatval($row['custom_valor_devolvido']);
                    $row['custom_valor_recebido'] = floatval($row['custom_valor_recebido']);
                    $data[$key] = $row;

                    // Consulta dados na Softsul
                    $pedido = $data[$key]['custom_codigo'];
                    $vencimentoParcela = $data[$key]['vencimento_parcela_format'];
                    
                    $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getInfoPedidosQuery($pedido, $vencimentoParcela) : '';
                    $result = [];
                    if ($sql) {
                        // Executa a consulta Oracle, caso tenha uma consulta
                        $result = $this->oracleService->executeQuery($sql);
                        $data[$key]['id'] = $result[0]['id'];
                        $data[$key]['nome_cliente'] = $result[0]['nome_cliente'];
                        $data[$key]['valor_parcela'] = floatval(str_replace(',', '.', $result[0]['valor_parcela']));
                        $data[$key]['recebido_total_parcela'] = floatval(str_replace(',', '.', $result[0]['recebido_total_parcela']));
                        $data[$key]['tipo_desmembramento'] = intval($result[0]['tipo_desmembramento']);
                    }
                }

            // Retorna os dados como JSON
            return new JsonModel([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getInfoPedidoAction()
    {
        // Verifica se o serviço Oracle está disponível
        if (!$this->oracleService) {
            return new JsonModel([
                'success' => false,
                'message' => 'Serviço Oracle não disponível'
            ]);
        }
        $pedido = $this->getRequest()->getQuery('custom_codigo'); 
        $vencimentoParcela = $this->getRequest()->getQuery('custom_vencimento_parcela'); 

        try {
            // Consulta dados na Softsul
            $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getInfoPedidosQuery($pedido, $vencimentoParcela) : '';
            
            $result = [];
            if ($sql) {
                // Executa a consulta Oracle, caso tenha uma consulta
                $result = $this->oracleService->executeQuery($sql);
            }

            // Retorna os dados como JSON
            return new JsonModel([
                'success' => count($result) > 0 ? true : false,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function saveControleRecebimentoAction()
    {
        // Obtém os dados em formato JSON no corpo da requisição
        $data = json_decode($this->getRequest()->getContent(), true);

        // não vai inserir os dados.
        unset($data['vencimento_parcela_format']);
        unset($data['id']);
        unset($data['nome_cliente']);
        unset($data['valor_parcela']);
        unset($data['recebido_total_parcela']);

        // Verifica se os dados foram recebidos corretamente
        if (empty($data)) {
            return new JsonModel([
                'success' => false,
                'message' => 'Nenhum dado recebido'
            ]);
        }

        $sql = new Sql($this->pgAdapter);
        $table = 'controle_recebimento';  // Nome da tabela no banco

        // Se 'id_controle_recebimento' está presente, realiza um UPDATE
        if (isset($data['id_controle_recebimento']) && !empty($data['id_controle_recebimento'])) {
            try {
                // Verifica se a linha existe com base no 'id_controle_recebimento'
                $select = $sql->select();
                $select->from($table)
                    ->where(['id_controle_recebimento' => $data['id_controle_recebimento']]);

                $statement = $sql->prepareStatementForSqlObject($select);
                $result = $statement->execute();

                // Se a linha existir, realiza o UPDATE
                if ($result->count() > 0) {
                    $update = $sql->update($table);

                    // Formata os valores numéricos conforme necessário
                    foreach ($data as $key => $value) {
                        if (is_string($value) && preg_match('/^\d+,\d+$/', $value)) {
                            $data[$key] = number_format(floatval(str_replace(',', '.', $value)), 2, '.', '');
                        }
                    }

                    $update->set($data);
                    $update->where(['id_controle_recebimento' => $data['id_controle_recebimento']]);

                    $updateStatement = $sql->prepareStatementForSqlObject($update);
                    $updateStatement->execute();

                    return new JsonModel([
                        'success' => true,
                        'message' => 'Dados atualizados com sucesso!'
                    ]);
                } else {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Registro não encontrado para atualização.'
                    ]);
                }
            } catch (\Exception $e) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Erro ao executar consulta: ' . $e->getMessage()
                ]);
            }
        }

        // Se 'id_controle_recebimento' não está presente, realiza um INSERT
        try {
            // Formata os valores numéricos conforme necessário
            foreach ($data as $key => $value) {
                if (is_string($value) && preg_match('/^\d+,\d+$/', $value)) {
                    $data[$key] = number_format(floatval(str_replace(',', '.', $value)), 2, '.', '');
                }
            }
            // Remove 'id_controle_recebimento' dos dados para que o banco o atribua automaticamente
            unset($data['id_controle_recebimento']);

            $insert = $sql->insert($table);
            $insert->values($data);

            $insertStatement = $sql->prepareStatementForSqlObject($insert);
            $insertStatement->execute();

            return new JsonModel([
                'success' => true,
                'message' => 'Dados inseridos com sucesso!'
            ]);
        } catch (\Exception $e) {
            echo '<pre>';
            print_r($e->getMessage());exit;

            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao executar consulta: ' . $e->getMessage()
            ]);
        }
    }
    public function deleteControleRecebimentoAction()
    {
        // Obtém os dados em formato JSON no corpo da requisição
        $data = json_decode($this->getRequest()->getContent(), true);
        $id = $data['id_controle_recebimento'];

        if ($id === 0) {
            return new JsonModel([
                'success' => false,
                'message' => 'Não encontrei esse ID para remover.',
            ]);
        }

        $sql = new Sql($this->pgAdapter);
        $table = 'controle_recebimento';
    
        try {
             // Cria a instrução DELETE
             $delete = $sql->delete($table);
             $delete->where(['id_controle_recebimento' => $id]);
 
             // Executa a instrução DELETE
             $deleteStatement = $sql->prepareStatementForSqlObject($delete);
             $result = $deleteStatement->execute();

            if ($result->getAffectedRows() > 0) {
                return new JsonModel([
                    'success' => true,
                    'message' => 'Registro excluído com sucesso.',
                ]);
            } else {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Nenhum registro encontrado para o ID fornecido.',
                ]);
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao excluir o registro: ' . $e->getMessage(),
            ]);
        }
    }

}
