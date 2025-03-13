<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Service\OracleService;
use Application\Repository\CreditoECobrancaRepository;
use Laminas\View\Model\JsonModel;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use Laminas\Permissions\Acl\Acl;

class CreditoECobrancaController extends BaseController
{
    private $pgAdapter;
    private $oracleService;
    private $creditoECobrancaRepository;

    public function __construct(Adapter $pgAdapter, OracleService $oracleService = null, CreditoECobrancaRepository $creditoECobrancaRepository = null, Acl $acl)
    {
        parent::__construct($acl); // Chama o construtor da classe base
        $this->pgAdapter = $pgAdapter;
        $this->oracleService = $oracleService;
        $this->creditoECobrancaRepository = $creditoECobrancaRepository;
    }

    public function controleRecebimentoAction()
    {
        $session = new Container('auth');

        if (!isset($session->user)) {
            // Redireciona o usuário para o login caso não esteja autenticado
            return $this->redirect()->toRoute('login');
        }

        return new ViewModel();
    }

    
    public function getLookupSafraAction()
    {
        // Verifica se o serviço Oracle está disponível
        if (!$this->oracleService) {
            return new JsonModel([
                'success' => false,
                'message' => 'Serviço Oracle não disponível'
            ]);
        }
    
        try {
            // Consulta dados na Softsul
            $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getLookupSafraQuery() : '';
            $result = [];
            if ($sql) {
                // Executa a consulta Oracle, caso tenha uma consulta
                $result = $this->oracleService->executeQuery($sql);

                foreach ($result as $key => $row) {
                    $result[$key]['dsc'] = mb_convert_encoding($row['dsc'], 'UTF-8', 'Windows-1252');
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
    
        // Captura os parâmetros da requisição GET
        $codigoSafra = $this->params()->fromQuery('codigosafra', null);
        $emissao_inicio = $this->params()->fromQuery('emissao_inicio', null);
        $emissao_fim = $this->params()->fromQuery('emissao_fim', null);
        $skip = $this->params()->fromQuery('skip', null);
        $take = $this->params()->fromQuery('take', null);
         
        try {
            // Consulta no Softsul todos pedidos
            $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getDadosSoftsulQuery($codigoSafra, $emissao_inicio, $emissao_fim) : '';
   

            $params = [];
            if ($codigoSafra) {
                $params['codigoSafra'] = $codigoSafra;
            }
            if ($emissao_inicio && $emissao_fim) {
                $params['emissao_inicio'] = $emissao_inicio;
                $params['emissao_fim'] = $emissao_fim;
            }
            $result = [];
            if ($sql) {
                // Executa a consulta Oracle, caso tenha uma consulta
                $result = $this->oracleService->executeQuery($sql, $params);

                // Consulta no PostgreSQL para obter os registros da tabela controle_recebimento
                $pgSql = new Sql($this->pgAdapter);
                $selectPg = $pgSql->select('controle_recebimento');
                // Adiciona filtro de codigoSafra, caso o parâmetro tenha sido passado
                if (!empty($codigoSafra)) {
                    $selectPg->where(['codigosafra' => $codigoSafra]);
                }
                $statementPg = $pgSql->prepareStatementForSqlObject($selectPg);
                $pgResult = $statementPg->execute();
    
                // Mapeia os resultados do PostgreSQL em um array associativo
                $pgData = [];
                foreach ($pgResult as $pgRow) {
                    // Conversão de valores numéricos
                    $pgRow['valor_parcela'] = floatval(str_replace(',', '.', $pgRow['valor_parcela']));
                    $pgRow['valor_recebido'] = floatval(str_replace(',', '.', $pgRow['valor_recebido']));
                    $pgRow['valor_recebido_juros'] = floatval(str_replace(',', '.', $pgRow['valor_recebido_juros']));
                    $pgRow['valor_desconto'] = floatval(str_replace(',', '.', $pgRow['valor_desconto']));
                    $pgRow['valor_liquido'] = floatval(str_replace(',', '.', $pgRow['valor_liquido']));
                    $pgRow['saldo_parcela'] = floatval(str_replace(',', '.', $pgRow['saldo_parcela']));
                    $pgRow['total_germoplasma'] = floatval(str_replace(',', '.', $pgRow['total_germoplasma']));
                    $pgRow['recebido_germoplasma'] = floatval(str_replace(',', '.', $pgRow['recebido_germoplasma']));
                    $pgRow['total_royalties'] = floatval(str_replace(',', '.', $pgRow['total_royalties']));
                    $pgRow['recebido_royalties'] = floatval(str_replace(',', '.', $pgRow['recebido_royalties']));
                    $pgRow['total_tsi'] = floatval(str_replace(',', '.', $pgRow['total_tsi']));
                    $pgRow['recebido_tsi'] = floatval(str_replace(',', '.', $pgRow['recebido_tsi']));
                    $pgRow['total_frete'] = floatval(str_replace(',', '.', $pgRow['total_frete']));
                    $pgRow['recebido_frete'] = floatval(str_replace(',', '.', $pgRow['recebido_frete']));


                    // Cria uma chave única com base nas colunas relevantes
                    $chave = $pgRow['codigo'] 
                    . '-' . $pgRow['id'] 
                    . '-' . $pgRow['vencimento_parcela'] 
                    . '-' . (!empty($pgRow['id_recebimento']) ? $pgRow['id_recebimento'] : 'x')
                    . '-' . (!empty($pgRow['total_germoplasma']) && $pgRow['total_germoplasma'] !== '0.00' ? $pgRow['total_germoplasma'] : 'x')
                    . '-' . (!empty($pgRow['total_tsi']) && $pgRow['total_tsi'] !== '0.00'  ? $pgRow['total_tsi'] : 'x')
                    . '-' . (!empty($pgRow['total_frete']) && $pgRow['total_frete'] !== '0.00'  ? $pgRow['total_frete'] : 'x')
                    . '-' . (!empty($pgRow['total_royalties']) && $pgRow['total_royalties'] !== '0.00'  ? $pgRow['total_royalties'] : 'x');
                    
                    $pgData[$chave] = $pgRow;
                }

                // Processa os dados do Oracle
                foreach ($result as $key => $row) {
                    // Convertendo a codificação para UTF-8
                    $result[$key]['status'] = mb_convert_encoding($row['status'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_cliente'] = mb_convert_encoding($row['nome_cliente'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_vendedor'] = mb_convert_encoding($row['nome_vendedor'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_agente'] = mb_convert_encoding($row['nome_agente'], 'UTF-8', 'Windows-1252');
                    $result[$key]['tipo_venda'] = mb_convert_encoding($row['tipo_venda'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_grupo_compra'] = mb_convert_encoding($row['nome_grupo_compra'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_tipo_desmembramento'] = mb_convert_encoding($row['nome_tipo_desmembramento'], 'UTF-8', 'Windows-1252');
                    $result[$key]['tipo_parcela'] = mb_convert_encoding($row['tipo_parcela'], 'UTF-8', 'Windows-1252');
    
                    // Conversão de valores numéricos
                    $result[$key]['valor_parcela'] = floatval(str_replace(',', '.', $result[$key]['valor_parcela']));
                    $result[$key]['valor_recebido'] = floatval(str_replace(',', '.', $result[$key]['valor_recebido']));
                    $result[$key]['valor_recebido_juros'] = floatval(str_replace(',', '.', $result[$key]['valor_recebido_juros']));
                    $result[$key]['valor_desconto'] = floatval(str_replace(',', '.', $result[$key]['valor_desconto']));
                    $result[$key]['valor_liquido'] = floatval(str_replace(',', '.', $result[$key]['valor_liquido']));
                    $result[$key]['saldo_parcela'] = floatval(str_replace(',', '.', $result[$key]['saldo_parcela']));
                    $result[$key]['total_germoplasma'] = floatval(str_replace(',', '.', $result[$key]['total_germoplasma']));
                    $result[$key]['recebido_germoplasma'] = floatval(str_replace(',', '.', $result[$key]['recebido_germoplasma']));
                    $result[$key]['total_royalties'] = floatval(str_replace(',', '.', $result[$key]['total_royalties']));
                    $result[$key]['recebido_royalties'] = floatval(str_replace(',', '.', $result[$key]['recebido_royalties']));
                    $result[$key]['total_tsi'] = floatval(str_replace(',', '.', $result[$key]['total_tsi']));
                    $result[$key]['recebido_tsi'] = floatval(str_replace(',', '.', $result[$key]['recebido_tsi']));
                    $result[$key]['total_frete'] = floatval(str_replace(',', '.', $result[$key]['total_frete']));
                    $result[$key]['recebido_frete'] = floatval(str_replace(',', '.', $result[$key]['recebido_frete']));
    
                    // Cria a chave única para buscar no array associativo do PostgreSQL
                    $chave = $result[$key]['codigo'] 
                             . '-' . $result[$key]['id'] 
                             . '-' . $result[$key]['vencimento_parcela']
                             . '-' . (!empty($result[$key]['id_recebimento']) ? $result[$key]['id_recebimento'] : 'x')

                             . '-' . (!empty($result[$key]['total_germoplasma']) ? $result[$key]['total_germoplasma'] : 'x')
                             . '-' . (!empty($result[$key]['total_tsi']) ? $result[$key]['total_tsi'] : 'x')
                             . '-' . (!empty($result[$key]['total_frete']) ? $result[$key]['total_frete'] : 'x')
                             . '-' . (!empty($result[$key]['total_royalties']) ? $result[$key]['total_royalties'] : 'x');
    
                    // Verifica se há correspondência no PostgreSQL
                    if (isset($pgData[$chave])) {
                        // Adiciona os campos do PostgreSQL ao resultado
                        $result[$key]['id_controle_recebimento'] = $pgData[$chave]['id_controle_recebimento'];
                        $result[$key]['custom_forma_pgto'] = $pgData[$chave]['custom_forma_pgto'];
                        $result[$key]['custom_valor_devolvido'] = $pgData[$chave]['custom_valor_devolvido'];
                        $result[$key]['custom_vencimento_boleto'] = $pgData[$chave]['custom_vencimento_boleto'];
                        $result[$key]['custom_observacao'] = $pgData[$chave]['custom_observacao'];

                        // Remove a chave do PostgreSQL para que não seja inserida novamente mais tarde
                        unset($pgData[$chave]);
                    }
                }

                // Agora adiciona os registros que estão apenas no PostgreSQL pros casos que são devolvidos por completo e deletados do softsul também aparecer.
                foreach ($pgData as $chave => $pgRow) {
                    // Adiciona no resultado apenas se a chave não existir no Oracle
                    $result[] = $pgRow;
                }
            }


            $totalCount = count($result); // Contagem total de registros
            $pagedData = array_slice($result, $skip, 9999); // Aplica paginação

            // Retorna os dados como JSON
            return new JsonModel([
                'success' => true,
                'data' => $pagedData,
                'totalCount' => $totalCount
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
            echo '<pre>insert';
            print_r( $e->getMessage());exit;
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao executar consulta: ' . $e->getMessage()
            ]);
        }
    }


    public function controleRecebimentoViewFinanceiroAction()
    {
        $session = new Container('auth');

        if (!isset($session->user)) {
            // Redireciona o usuário para o login caso não esteja autenticado
            return $this->redirect()->toRoute('login');
        }
        
        return new ViewModel();
    }
    public function listControleRecebimentoEnvioFinanceiroAction()
    {
        // Verifica se o serviço Oracle está disponível
        if (!$this->oracleService) {
            return new JsonModel([
                'success' => false,
                'message' => 'Serviço Oracle não disponível'
            ]);
        }
    
        // Captura os parâmetros da requisição GET
        $codigoSafra = $this->params()->fromQuery('codigosafra', null);
        $pagamento_inicio = $this->params()->fromQuery('pagamento_inicio', null);
        $pagamento_fim = $this->params()->fromQuery('pagamento_fim', null);
         
        try {
            // Consulta no Softsul todos pedidos
            $sql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getDadosSoftsulDataPagamentoQuery($codigoSafra, $pagamento_inicio, $pagamento_fim) : '';

            $params = [];
            if ($codigoSafra) {
                $params['codigoSafra'] = $codigoSafra;
            }
            if ($pagamento_inicio && $pagamento_fim) {
                $params['pagamento_inicio'] = $pagamento_inicio;
                $params['pagamento_fim'] = $pagamento_fim;
            }

            $result = [];
            if ($sql) {
                // Executa a consulta Oracle, caso tenha uma consulta
                $result = $this->oracleService->executeQuery($sql, $params);

                // Consulta no PostgreSQL para obter os registros da tabela controle_recebimento
                $pgSql = $this->creditoECobrancaRepository ? $this->creditoECobrancaRepository->getDadosControlRecebimentoDataPagamentoQuery($codigoSafra, $pagamento_inicio, $pagamento_fim) : '';
                $statementPg = $this->pgAdapter->query($pgSql);
                $pgResult = $statementPg->execute();
                
    
                // Mapeia os resultados do PostgreSQL em um array associativo
                $pgData = [];
                foreach ($pgResult as $pgRow) {
                    // Conversão de valores numéricos
                    $pgRow['valor_parcela'] = floatval(str_replace(',', '.', $pgRow['valor_parcela']));
                    $pgRow['valor_recebido'] = floatval(str_replace(',', '.', $pgRow['valor_recebido']));
                    $pgRow['valor_recebido_juros'] = floatval(str_replace(',', '.', $pgRow['valor_recebido_juros']));
                    $pgRow['valor_desconto'] = floatval(str_replace(',', '.', $pgRow['valor_desconto']));
                    $pgRow['valor_liquido'] = floatval(str_replace(',', '.', $pgRow['valor_liquido']));
                    $pgRow['saldo_parcela'] = floatval(str_replace(',', '.', $pgRow['saldo_parcela']));
                    $pgRow['total_germoplasma'] = floatval(str_replace(',', '.', $pgRow['total_germoplasma']));
                    $pgRow['recebido_germoplasma'] = floatval(str_replace(',', '.', $pgRow['recebido_germoplasma']));
                    $pgRow['total_royalties'] = floatval(str_replace(',', '.', $pgRow['total_royalties']));
                    $pgRow['recebido_royalties'] = floatval(str_replace(',', '.', $pgRow['recebido_royalties']));
                    $pgRow['total_tsi'] = floatval(str_replace(',', '.', $pgRow['total_tsi']));
                    $pgRow['recebido_tsi'] = floatval(str_replace(',', '.', $pgRow['recebido_tsi']));
                    $pgRow['total_frete'] = floatval(str_replace(',', '.', $pgRow['total_frete']));
                    $pgRow['recebido_frete'] = floatval(str_replace(',', '.', $pgRow['recebido_frete']));


                    // Cria uma chave única com base nas colunas relevantes
                    $chave = $pgRow['codigo'] 
                    . '-' . $pgRow['id'] 
                    . '-' . $pgRow['vencimento_parcela'] 
                    . '-' . (!empty($pgRow['id_recebimento']) ? $pgRow['id_recebimento'] : 'x')
                    . '-' . (!empty($pgRow['total_germoplasma']) && $pgRow['total_germoplasma'] !== '0.00' ? $pgRow['total_germoplasma'] : 'x')
                    . '-' . (!empty($pgRow['total_tsi']) && $pgRow['total_tsi'] !== '0.00'  ? $pgRow['total_tsi'] : 'x')
                    . '-' . (!empty($pgRow['total_frete']) && $pgRow['total_frete'] !== '0.00'  ? $pgRow['total_frete'] : 'x')
                    . '-' . (!empty($pgRow['total_royalties']) && $pgRow['total_royalties'] !== '0.00'  ? $pgRow['total_royalties'] : 'x');
                    
                    $pgData[$chave] = $pgRow;
                }

                // Processa os dados do Oracle
                foreach ($result as $key => $row) {
                    // Convertendo a codificação para UTF-8
                    $result[$key]['status'] = mb_convert_encoding($row['status'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_cliente'] = mb_convert_encoding($row['nome_cliente'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_vendedor'] = mb_convert_encoding($row['nome_vendedor'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_agente'] = mb_convert_encoding($row['nome_agente'], 'UTF-8', 'Windows-1252');
                    $result[$key]['tipo_venda'] = mb_convert_encoding($row['tipo_venda'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_grupo_compra'] = mb_convert_encoding($row['nome_grupo_compra'], 'UTF-8', 'Windows-1252');
                    $result[$key]['nome_tipo_desmembramento'] = mb_convert_encoding($row['nome_tipo_desmembramento'], 'UTF-8', 'Windows-1252');
                    $result[$key]['tipo_parcela'] = mb_convert_encoding($row['tipo_parcela'], 'UTF-8', 'Windows-1252');
    
                    // Conversão de valores numéricos
                    $result[$key]['valor_parcela'] = floatval(str_replace(',', '.', $result[$key]['valor_parcela']));
                    $result[$key]['valor_recebido'] = floatval(str_replace(',', '.', $result[$key]['valor_recebido']));
                    $result[$key]['valor_recebido_juros'] = floatval(str_replace(',', '.', $result[$key]['valor_recebido_juros']));
                    $result[$key]['valor_desconto'] = floatval(str_replace(',', '.', $result[$key]['valor_desconto']));
                    $result[$key]['valor_liquido'] = floatval(str_replace(',', '.', $result[$key]['valor_liquido']));
                    $result[$key]['saldo_parcela'] = floatval(str_replace(',', '.', $result[$key]['saldo_parcela']));
                    $result[$key]['total_germoplasma'] = floatval(str_replace(',', '.', $result[$key]['total_germoplasma']));
                    $result[$key]['recebido_germoplasma'] = floatval(str_replace(',', '.', $result[$key]['recebido_germoplasma']));
                    $result[$key]['total_royalties'] = floatval(str_replace(',', '.', $result[$key]['total_royalties']));
                    $result[$key]['recebido_royalties'] = floatval(str_replace(',', '.', $result[$key]['recebido_royalties']));
                    $result[$key]['total_tsi'] = floatval(str_replace(',', '.', $result[$key]['total_tsi']));
                    $result[$key]['recebido_tsi'] = floatval(str_replace(',', '.', $result[$key]['recebido_tsi']));
                    $result[$key]['total_frete'] = floatval(str_replace(',', '.', $result[$key]['total_frete']));
                    $result[$key]['recebido_frete'] = floatval(str_replace(',', '.', $result[$key]['recebido_frete']));
    
                    // Cria a chave única para buscar no array associativo do PostgreSQL
                    $chave = $result[$key]['codigo'] 
                             . '-' . $result[$key]['id'] 
                             . '-' . $result[$key]['vencimento_parcela']
                             . '-' . (!empty($result[$key]['id_recebimento']) ? $result[$key]['id_recebimento'] : 'x')

                             . '-' . (!empty($result[$key]['total_germoplasma']) ? $result[$key]['total_germoplasma'] : 'x')
                             . '-' . (!empty($result[$key]['total_tsi']) ? $result[$key]['total_tsi'] : 'x')
                             . '-' . (!empty($result[$key]['total_frete']) ? $result[$key]['total_frete'] : 'x')
                             . '-' . (!empty($result[$key]['total_royalties']) ? $result[$key]['total_royalties'] : 'x');
    
                    // Verifica se há correspondência no PostgreSQL
                    if (isset($pgData[$chave])) {
                        // Adiciona os campos do PostgreSQL ao resultado
                        $result[$key]['id_controle_recebimento'] = $pgData[$chave]['id_controle_recebimento'];
                        $result[$key]['custom_forma_pgto'] = $pgData[$chave]['custom_forma_pgto'];
                        $result[$key]['custom_valor_devolvido'] = $pgData[$chave]['custom_valor_devolvido'];
                        $result[$key]['custom_vencimento_boleto'] = $pgData[$chave]['custom_vencimento_boleto'];
                        $result[$key]['custom_observacao'] = $pgData[$chave]['custom_observacao'];

                        // Remove a chave do PostgreSQL para que não seja inserida novamente mais tarde
                        unset($pgData[$chave]);
                    }
                }

                // Agora adiciona os registros que estão apenas no PostgreSQL pros casos que são devolvidos por completo e deletados do softsul também aparecer.
                foreach ($pgData as $chave => $pgRow) {
                    // Adiciona no resultado apenas se a chave não existir no Oracle
                    $result[] = $pgRow;
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

}