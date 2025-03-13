<?php

namespace Application\Repository;

class CreditoECobrancaRepository
{


    public function getDadosSoftsulQuery($codigoSafra, $emissao_inicio = null, $emissao_fim = null)
    {
        $wheres = "";
        
        if (!empty($codigoSafra)) {
            $wheres .= " AND p.CODIGOSAFRA = {$codigoSafra}";
        }
        if (!empty($emissao_inicio)) {
            $emissao_fim = !empty($emissao_fim) ? $emissao_fim : date('Y-m-d');
            $wheres .= " AND P.CREATED_AT BETWEEN TO_DATE('{$emissao_inicio}', 'YYYY-MM-DD') AND TO_DATE('{$emissao_fim}', 'YYYY-MM-DD')";
        }
       
        
        return "SELECT 
                    dadospedido.id AS \"id\"
                    ,dadospedido.codigo AS \"codigo\"
                    ,dadospedido.CODIGOSAFRA AS \"codigosafra\"
                    ,dadospedido.mae_pedido_id AS \"mae_pedido_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.status) AS \"status\"
                    ,dadospedido.tipo_frete AS \"tipo_frete\"
                    ,dadospedido.cliente_id AS \"cliente_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_cliente) AS \"nome_cliente\"
                    ,dadospedido.vendedor_id AS \"vendedor_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_vendedor) AS \"nome_vendedor\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.tipo_venda) AS \"tipo_venda\"
                    ,dadospedido.agente_id AS \"agente_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_agente) AS \"nome_agente\"
                    ,dadospedido.grupo_compras_id AS \"grupo_compras_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_grupo_compra) AS \"nome_grupo_compra\"
                    ,UTL_RAW.CAST_TO_RAW(CASE WHEN dadosPedido.tipo_parcela = 'R' THEN 'Royalties' WHEN dadosPedido.tipo_parcela = 'G' THEN 'Germoplasma' WHEN dadosPedido.tipo_parcela = 'T' THEN 'TSI' WHEN dadosPedido.tipo_parcela = 'F' THEN 'Frete' ELSE NULL END) AS \"tipo_parcela\"
                    ,TO_CHAR(dadospedido.vencimento_parcela, 'YYYY-MM-DD') AS \"vencimento_parcela\"
                    ,MAX(parc.NUMERO_PARCELA) AS \"numero_parcela\"
                    ,dadospedido.parcela_codigomodalidade AS \"parcela_codigomodalidade\"
                    ,m.DESCRICAO AS \"dsc_modalidade\"
                    ,max(dadospedido.id_tipo_desmembramento) AS \"id_tipo_desmembramento\"
                    ,UTL_RAW.CAST_TO_RAW(max(dadospedido.nome_tipo_desmembramento)) AS \"nome_tipo_desmembramento\"
                    ,TO_CHAR(dadospedido.recebido_em, 'YYYY-MM-DD') AS \"data_pagamento\"
                    ,MAX(parc.preco_parcela) AS \"valor_parcela\"
                    ,dadosPedido.ID_RECEBIMENTO as \"id_recebimento\"
                    ,nvl(SUM(dadospedido.valor), 0) as \"valor_recebido\"
                    ,MAX(dadospedido.juros) AS \"valor_recebido_juros\"
                    ,MAX(dadospedido.desconto) AS \"valor_desconto\"
                    ,(nvl(SUM(dadospedido.valor), 0) + nvl(SUM(dadospedido.juros), 0) - nvl(SUM(dadospedido.desconto), 0)) AS \"valor_liquido\"
                    ,MAX(parc.SALDO)*-1 AS \"saldo_parcela\"
                    ,(CASE WHEN MAX(parc.SALDO) = 0 THEN 'S' ELSE 'N' END) AS \"parcela_paga\"
                    -- germoplasma
                    ,MAX(nvl(dadospedido.preco_total_germoplasma, 0)) AS \"total_germoplasma\"
                    ,SUM(nvl(dadospedido.valor_germoplasma,0)) AS \"recebido_germoplasma\"
                    -- royalties
                    ,MAX(nvl(dadospedido.preco_total_royalties, 0)) AS \"total_royalties\"
                    ,SUM(nvl(dadospedido.valor_royalties,0)) AS \"recebido_royalties\"
                    -- tsi
                    ,MAX(nvl(dadospedido.preco_total_tsi, 0)) AS \"total_tsi\"
                    ,SUM(nvl(dadospedido.valor_tsi,0)) AS \"recebido_tsi\"
                    -- frete
                    ,MAX(nvl(dadospedido.preco_total_frete, 0)) AS \"total_frete\"
                    ,SUM(nvl(dadospedido.valor_frete,0)) AS \"recebido_frete\"
                    ,MAX(parc.boleto_emitido) AS \"boleto_emitido\"
                    ,MAX(parc.duplicata_emitida) AS \"duplicata_emitida\"
                FROM (   
                    SELECT 
                        p.ID
                        ,p.CODIGO
                        ,p.CODIGOSAFRA
                        ,pedidoMae.codigo AS MAE_PEDIDO_ID
                        ,td.id AS id_tipo_desmembramento
                        ,td.nome AS nome_tipo_desmembramento
                        ,(CASE WHEN (ps.status_base = 'Aguardando') THEN ps.status_base || ' ' || nvl(ps.autorizacao_setor_aguardando, '?') || ps.autorizacao_edicao
                            WHEN (ps.status_base = 'Reprovado') THEN ps.status_base || ' por ' || nvl(ps.autorizacao_setor_reprovou, '?') || ps.autorizacao_edicao
                            WHEN (ps.status_base = 'Editando' OR ps.status_base = 'Aprovado') THEN ps.status_base
                            ELSE ps.status_base || ps.autorizacao_edicao END
                        ) AS status 
                        ,CASE WHEN p.TIPO_FRETE = 'c' THEN 'CIF' WHEN p.TIPO_FRETE = 'f' THEN 'FOB' ELSE NULL END AS TIPO_FRETE
                        ,p.CODIGOLOCAL AS CLIENTE_ID
                        ,cli.NOME AS NOME_CLIENTE
                        ,p.RTV_USER_ID AS VENDEDOR_ID
                        ,vend.NAME AS NOME_VENDEDOR
                        ,p.TIPO_VENDA_ID
                        ,tv.NOME AS TIPO_VENDA
                        ,p.AGENTE_CODIGOCLIFOR AS AGENTE_ID
                        ,agt.NOME AS NOME_AGENTE
                        ,p.GRUPO_COMPRA_CODIGOCLIFOR AS GRUPO_COMPRAS_ID
                        ,gc.NOME AS NOME_GRUPO_COMPRA
                        ,vp.tipo_parcela
                        ,r.id as ID_RECEBIMENTO
                        ,r.valor
                        ,r.juros
                        ,r.desconto
                        ,r.obs
                        ,trunc(r.recebido_em) AS recebido_em
                        ,vp.vencimento_parcela
                        ,vp.parcela_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN ip.preco_total_germoplasma ELSE NULL END) AS preco_total_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN r.valor ELSE NULL END) AS valor_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN vp.parcela_codigomodalidade ELSE NULL END) AS germoplasma_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN ip.preco_total_royalties ELSE NULL END) AS preco_total_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN r.valor ELSE NULL END) AS valor_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN vp.parcela_codigomodalidade ELSE NULL END) AS royalties_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN ip.preco_total_tsi ELSE NULL END) AS preco_total_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN r.valor ELSE NULL END) AS valor_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN vp.parcela_codigomodalidade ELSE NULL END) AS tsi_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN p.preco_total_frete ELSE NULL END) AS preco_total_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN r.valor ELSE NULL END) AS valor_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN vp.parcela_codigomodalidade ELSE NULL END) AS frete_codigomodalidade
                    FROM (
                        SELECT 
                            p1.id,
                            trunc(p1.vencimento_germoplasma) AS vencimento_parcela,
                            p1.germoplasma_codigomodalidade AS parcela_codigomodalidade,
                            'G' AS tipo_parcela
                        FROM web.pedidos_v2 p1
                        UNION
                        SELECT 
                            p2.id,
                            trunc(p2.vencimento_royalties) AS vencimento_parcela,
                            p2.royalties_codigomodalidade AS parcela_codigomodalidade,
                            'R' AS tipo_parcela
                        FROM web.pedidos_v2 p2
                        UNION
                        SELECT 
                            p3.id,
                            trunc(p3.vencimento_tsi) AS vencimento_parcela,
                            p3.tsi_codigomodalidade AS parcela_codigomodalidade,
                            'T' AS tipo_parcela
                        FROM web.pedidos_v2 p3
                        UNION
                        SELECT 
                            p4.id,
                            trunc(p4.vencimento_frete) AS vencimento_parcela,
                            p4.frete_codigomodalidade AS parcela_codigomodalidade,
                            'F' AS tipo_parcela
                        FROM web.pedidos_v2 p4
                    ) vp --subquery pega todos os vencimentos do pedido.
                    INNER JOIN web.pedidos_v2 p ON vp.id = p.id --JOIN PARA PEGAR TODAS INFORMAÇÕES PEDIDO
                    LEFT JOIN web.pedidos_v2 pedidoMae ON pedidoMae.id = p.MAE_PEDIDO_ID
                    INNER JOIN (
                        SELECT i.pedido_id,
                        nvl(SUM(i.preco_total_germoplasma), 0) AS preco_total_germoplasma,
                        nvl(SUM(i.preco_total_royalties), 0) AS preco_total_royalties,
                        nvl(SUM(i.preco_total_tsi), 0) AS preco_total_tsi
                        FROM web.itens_pedido_v2 i
                        GROUP BY i.pedido_id
                    ) ip ON p.id = ip.pedido_id -- JOIN PARA PEGAR INFORMAÇÕES DE ITENS DO PEDIDO
                    LEFT JOIN web.recebimentos r ON (vp.id = r.pedido_id AND vp.tipo_parcela = r.tipo) -- JOIN PARA PEGAR INFORMAÇÕES DE RECEBIMENTO
                    LEFT JOIN web.view_vencimentos_por_data vvpd ON (r.pedido_id = vvpd.pedido_id AND vp.vencimento_parcela = vvpd.vencimento_parcela AND vp.parcela_codigomodalidade = vvpd.parcela_codigomodalidade)
                    LEFT JOIN EMPRESA.CLIFOR cli ON cli.CODIGOCLIFOR = p.CODIGOLOCAL
                    LEFT JOIN EMPRESA.CLIFOR agt ON agt.CODIGOCLIFOR = p.AGENTE_CODIGOCLIFOR 
                    LEFT JOIN EMPRESA.CLIFOR gc ON gc.CODIGOCLIFOR = p.GRUPO_COMPRA_CODIGOCLIFOR 
                    LEFT JOIN WEB.USERS vend ON vend.ID = p.RTV_USER_ID 
                    LEFT JOIN WEB.TIPOS_VENDA tv ON tv.ID  = p.TIPO_VENDA_ID
                    INNER JOIN (
                        SELECT
                            p3.id
                            ,p4.status_base
                            ,(CASE WHEN (status_base = 'Aguardando' AND venda_autorizou IS NULL ) THEN 'Venda'
                                WHEN (status_base = 'Aguardando' AND nvl(t2.slug, '_') != 'mae' AND tipo_frete = 'c' AND logistica_autorizou IS NULL) THEN 'Logística'
                                WHEN (status_base = 'Aguardando' AND (nvl(t2.slug, '_') = 'mae' OR (tipo_frete = 'c' AND logistica_autorizou = '1') OR tipo_frete = 'f') AND diretoria_autorizou IS NULL) THEN 'Gerente Comercial'
                                WHEN (status_base = 'Aguardando' AND (nvl(t2.slug, '_') = 'mae' OR (tipo_frete = 'c' AND logistica_autorizou = '1') OR tipo_frete = 'f') AND diretoria_autorizou = '1' AND comercial_autorizou IS NULL) THEN 'Adm. Comercial'
                            ELSE NULL END
                            ) AS autorizacao_setor_aguardando
                            ,(CASE WHEN (status_base = 'Reprovado' AND nvl(t2.slug, '_') != 'mae' AND tipo_frete = 'c' AND logistica_autorizou = '0') THEN 'Logística'
                                WHEN (status_base = 'Reprovado' AND diretoria_autorizou = '0') THEN 'Gerente Comercial'
                                WHEN (status_base = 'Reprovado' AND comercial_autorizou = '0') THEN 'Adm. Comercial'
                            ELSE NULL END
                            ) AS autorizacao_setor_reprovou,
                            (CASE WHEN (aprovou = '1') THEN ' (Edit.)' ELSE NULL END) AS autorizacao_edicao
                        FROM web.pedidos_v2 p3 
                        INNER JOIN web.view_status_pedidos_v2 p4 ON p3.id = p4.id
                        LEFT JOIN web.tipos_desmembramento t2 ON p3.tipo_desmembramento_id = t2.id
                    ) ps ON ps.id = p.id
                    LEFT JOIN WEB.TIPOS_DESMEMBRAMENTO TD ON TD.ID = P.TIPO_DESMEMBRAMENTO_ID
                    WHERE (
                        (vp.tipo_parcela = 'G' 
                        AND nvl(ip.preco_total_germoplasma, 0) > 0) OR (vp.tipo_parcela = 'R' 
                        AND nvl(ip.preco_total_royalties, 0) > 0) OR (vp.tipo_parcela = 'T' 
                        AND nvl(ip.preco_total_tsi, 0) > 0) OR (vp.tipo_parcela = 'F' 
                        AND nvl(p.preco_total_frete, 0) > 0)
                    )
                    --AND P.CREATED_AT BETWEEN '01/12/2024' AND '30/03/2025'
                    {$wheres}
                    --AND p.id IN (28089,21688)
                    --AND P.CODIGO in (24410074, 24430316)
                    --AND vp.vencimento_parcela >= '25/02/2025'
                ) dadosPedido
                INNER JOIN web.view_vencimentos_por_data parc ON (dadosPedido.id = parc.pedido_id AND dadosPedido.vencimento_parcela = parc.vencimento_parcela AND dadosPedido.parcela_codigomodalidade = parc.parcela_codigomodalidade)
                left join EMPRESA.MODALIDADES m on m.CODIGOMODALIDADE = dadosPedido.parcela_codigomodalidade
                GROUP BY dadosPedido.ID
                    ,dadosPedido.CODIGO
                    ,dadosPedido.CODIGOSAFRA
                    ,dadosPedido.MAE_PEDIDO_ID
                    ,dadosPedido.STATUS
                    ,dadosPedido.TIPO_FRETE
                    ,dadosPedido.CLIENTE_ID	
                    ,dadosPedido.NOME_CLIENTE
                    ,dadosPedido.VENDEDOR_ID
                    ,dadosPedido.NOME_VENDEDOR
                    ,dadosPedido.TIPO_VENDA
                    ,dadosPedido.AGENTE_ID
                    ,dadosPedido.NOME_AGENTE
                    ,dadosPedido.GRUPO_COMPRAS_ID
                    ,dadosPedido.NOME_GRUPO_COMPRA
                    ,dadosPedido.tipo_parcela 
                    ,dadosPedido.VENCIMENTO_PARCELA
                    ,dadospedido.parcela_codigomodalidade
                    ,m.descricao
                    ,dadospedido.recebido_em
                    ,dadosPedido.ID_RECEBIMENTO
                ORDER BY dadosPedido.CLIENTE_ID, dadosPedido.VENCIMENTO_PARCELA, dadospedido.recebido_em ASC";  
    }
    public function getDadosSoftsulDataPagamentoQuery($codigoSafra, $pagamento_inicio = null, $pagamento_fim = null)
    {
        $wheres = "";
        
        if (!empty($codigoSafra)) {
            $wheres .= " AND dadospedido.CODIGOSAFRA = {$codigoSafra}";
        }
        if (!empty($pagamento_inicio)) {
            $pagamento_fim = !empty($pagamento_fim) ? $pagamento_fim : date('Y-m-d');
            $wheres .= " AND dadospedido.recebido_em BETWEEN TO_DATE('{$pagamento_inicio}', 'YYYY-MM-DD') AND TO_DATE('{$pagamento_fim}', 'YYYY-MM-DD')";
        }

       
        
        return "SELECT 
                    dadospedido.id AS \"id\"
                    ,dadospedido.codigo AS \"codigo\"
                    ,dadospedido.CODIGOSAFRA AS \"codigosafra\"
                    ,dadospedido.mae_pedido_id AS \"mae_pedido_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.status) AS \"status\"
                    ,dadospedido.tipo_frete AS \"tipo_frete\"
                    ,dadospedido.cliente_id AS \"cliente_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_cliente) AS \"nome_cliente\"
                    ,dadospedido.vendedor_id AS \"vendedor_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_vendedor) AS \"nome_vendedor\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.tipo_venda) AS \"tipo_venda\"
                    ,dadospedido.agente_id AS \"agente_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_agente) AS \"nome_agente\"
                    ,dadospedido.grupo_compras_id AS \"grupo_compras_id\"
                    ,UTL_RAW.CAST_TO_RAW(dadospedido.nome_grupo_compra) AS \"nome_grupo_compra\"
                    ,UTL_RAW.CAST_TO_RAW(CASE WHEN dadosPedido.tipo_parcela = 'R' THEN 'Royalties' WHEN dadosPedido.tipo_parcela = 'G' THEN 'Germoplasma' WHEN dadosPedido.tipo_parcela = 'T' THEN 'TSI' WHEN dadosPedido.tipo_parcela = 'F' THEN 'Frete' ELSE NULL END) AS \"tipo_parcela\"
                    ,TO_CHAR(dadospedido.vencimento_parcela, 'YYYY-MM-DD') AS \"vencimento_parcela\"
                    ,MAX(parc.NUMERO_PARCELA) AS \"numero_parcela\"
                    ,dadospedido.parcela_codigomodalidade AS \"parcela_codigomodalidade\"
                    ,max(dadospedido.id_tipo_desmembramento) AS \"id_tipo_desmembramento\"
                    ,UTL_RAW.CAST_TO_RAW(max(dadospedido.nome_tipo_desmembramento)) AS \"nome_tipo_desmembramento\"
                    ,TO_CHAR(dadospedido.recebido_em, 'YYYY-MM-DD') AS \"data_pagamento\"
                    ,MAX(parc.preco_parcela) AS \"valor_parcela\"
                    ,dadosPedido.ID_RECEBIMENTO as \"id_recebimento\"
                    ,nvl(SUM(dadospedido.valor), 0) as \"valor_recebido\"
                    ,MAX(dadospedido.juros) AS \"valor_recebido_juros\"
                    ,MAX(dadospedido.desconto) AS \"valor_desconto\"
                    ,(nvl(SUM(dadospedido.valor), 0) + nvl(SUM(dadospedido.juros), 0) - nvl(SUM(dadospedido.desconto), 0)) AS \"valor_liquido\"
                    ,MAX(parc.SALDO)*-1 AS \"saldo_parcela\"
                    ,(CASE WHEN MAX(parc.SALDO) = 0 THEN 'S' ELSE 'N' END) AS \"parcela_paga\"
                    -- germoplasma
                    ,MAX(nvl(dadospedido.preco_total_germoplasma, 0)) AS \"total_germoplasma\"
                    ,SUM(nvl(dadospedido.valor_germoplasma,0)) AS \"recebido_germoplasma\"
                    -- royalties
                    ,MAX(nvl(dadospedido.preco_total_royalties, 0)) AS \"total_royalties\"
                    ,SUM(nvl(dadospedido.valor_royalties,0)) AS \"recebido_royalties\"
                    -- tsi
                    ,MAX(nvl(dadospedido.preco_total_tsi, 0)) AS \"total_tsi\"
                    ,SUM(nvl(dadospedido.valor_tsi,0)) AS \"recebido_tsi\"
                    -- frete
                    ,MAX(nvl(dadospedido.preco_total_frete, 0)) AS \"total_frete\"
                    ,SUM(nvl(dadospedido.valor_frete,0)) AS \"recebido_frete\"
                    ,MAX(parc.boleto_emitido) AS \"boleto_emitido\"
                    ,MAX(parc.duplicata_emitida) AS \"duplicata_emitida\"
                FROM (   
                    SELECT 
                        p.ID
                        ,p.CODIGO
                        ,p.CODIGOSAFRA
                        ,pedidoMae.codigo AS MAE_PEDIDO_ID
                        ,td.id AS id_tipo_desmembramento
                        ,td.nome AS nome_tipo_desmembramento
                        ,(CASE WHEN (ps.status_base = 'Aguardando') THEN ps.status_base || ' ' || nvl(ps.autorizacao_setor_aguardando, '?') || ps.autorizacao_edicao
                            WHEN (ps.status_base = 'Reprovado') THEN ps.status_base || ' por ' || nvl(ps.autorizacao_setor_reprovou, '?') || ps.autorizacao_edicao
                            WHEN (ps.status_base = 'Editando' OR ps.status_base = 'Aprovado') THEN ps.status_base
                            ELSE ps.status_base || ps.autorizacao_edicao END
                        ) AS status 
                        ,CASE WHEN p.TIPO_FRETE = 'c' THEN 'CIF' WHEN p.TIPO_FRETE = 'f' THEN 'FOB' ELSE NULL END AS TIPO_FRETE
                        ,p.CODIGOLOCAL AS CLIENTE_ID
                        ,cli.NOME AS NOME_CLIENTE
                        ,p.RTV_USER_ID AS VENDEDOR_ID
                        ,vend.NAME AS NOME_VENDEDOR
                        ,p.TIPO_VENDA_ID
                        ,tv.NOME AS TIPO_VENDA
                        ,p.AGENTE_CODIGOCLIFOR AS AGENTE_ID
                        ,agt.NOME AS NOME_AGENTE
                        ,p.GRUPO_COMPRA_CODIGOCLIFOR AS GRUPO_COMPRAS_ID
                        ,gc.NOME AS NOME_GRUPO_COMPRA
                        ,vp.tipo_parcela
                        ,r.id as ID_RECEBIMENTO
                        ,r.valor
                        ,r.juros
                        ,r.desconto
                        ,r.obs
                        ,trunc(r.recebido_em) AS recebido_em
                        ,vp.vencimento_parcela
                        ,vp.parcela_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN ip.preco_total_germoplasma ELSE NULL END) AS preco_total_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN r.valor ELSE NULL END) AS valor_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_germoplasma
                        ,(CASE WHEN vp.tipo_parcela = 'G' THEN vp.parcela_codigomodalidade ELSE NULL END) AS germoplasma_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN ip.preco_total_royalties ELSE NULL END) AS preco_total_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN r.valor ELSE NULL END) AS valor_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_royalties
                        ,(CASE WHEN vp.tipo_parcela = 'R' THEN vp.parcela_codigomodalidade ELSE NULL END) AS royalties_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN ip.preco_total_tsi ELSE NULL END) AS preco_total_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN r.valor ELSE NULL END) AS valor_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_tsi
                        ,(CASE WHEN vp.tipo_parcela = 'T' THEN vp.parcela_codigomodalidade ELSE NULL END) AS tsi_codigomodalidade
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN p.preco_total_frete ELSE NULL END) AS preco_total_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN r.valor ELSE NULL END) AS valor_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN vp.vencimento_parcela ELSE NULL END) AS vencimento_frete
                        ,(CASE WHEN vp.tipo_parcela = 'F' THEN vp.parcela_codigomodalidade ELSE NULL END) AS frete_codigomodalidade
                    FROM (
                        SELECT 
                            p1.id,
                            trunc(p1.vencimento_germoplasma) AS vencimento_parcela,
                            p1.germoplasma_codigomodalidade AS parcela_codigomodalidade,
                            'G' AS tipo_parcela
                        FROM web.pedidos_v2 p1
                        UNION
                        SELECT 
                            p2.id,
                            trunc(p2.vencimento_royalties) AS vencimento_parcela,
                            p2.royalties_codigomodalidade AS parcela_codigomodalidade,
                            'R' AS tipo_parcela
                        FROM web.pedidos_v2 p2
                        UNION
                        SELECT 
                            p3.id,
                            trunc(p3.vencimento_tsi) AS vencimento_parcela,
                            p3.tsi_codigomodalidade AS parcela_codigomodalidade,
                            'T' AS tipo_parcela
                        FROM web.pedidos_v2 p3
                        UNION
                        SELECT 
                            p4.id,
                            trunc(p4.vencimento_frete) AS vencimento_parcela,
                            p4.frete_codigomodalidade AS parcela_codigomodalidade,
                            'F' AS tipo_parcela
                        FROM web.pedidos_v2 p4
                    ) vp --subquery pega todos os vencimentos do pedido.
                    INNER JOIN web.pedidos_v2 p ON vp.id = p.id --JOIN PARA PEGAR TODAS INFORMAÇÕES PEDIDO
                    LEFT JOIN web.pedidos_v2 pedidoMae ON pedidoMae.id = p.MAE_PEDIDO_ID
                    INNER JOIN (
                        SELECT i.pedido_id,
                        nvl(SUM(i.preco_total_germoplasma), 0) AS preco_total_germoplasma,
                        nvl(SUM(i.preco_total_royalties), 0) AS preco_total_royalties,
                        nvl(SUM(i.preco_total_tsi), 0) AS preco_total_tsi
                        FROM web.itens_pedido_v2 i
                        GROUP BY i.pedido_id
                    ) ip ON p.id = ip.pedido_id -- JOIN PARA PEGAR INFORMAÇÕES DE ITENS DO PEDIDO
                    LEFT JOIN web.recebimentos r ON (vp.id = r.pedido_id AND vp.tipo_parcela = r.tipo) -- JOIN PARA PEGAR INFORMAÇÕES DE RECEBIMENTO
                    LEFT JOIN web.view_vencimentos_por_data vvpd ON (r.pedido_id = vvpd.pedido_id AND vp.vencimento_parcela = vvpd.vencimento_parcela AND vp.parcela_codigomodalidade = vvpd.parcela_codigomodalidade)
                    LEFT JOIN EMPRESA.CLIFOR cli ON cli.CODIGOCLIFOR = p.CODIGOLOCAL
                    LEFT JOIN EMPRESA.CLIFOR agt ON agt.CODIGOCLIFOR = p.AGENTE_CODIGOCLIFOR 
                    LEFT JOIN EMPRESA.CLIFOR gc ON gc.CODIGOCLIFOR = p.GRUPO_COMPRA_CODIGOCLIFOR 
                    LEFT JOIN WEB.USERS vend ON vend.ID = p.RTV_USER_ID 
                    LEFT JOIN WEB.TIPOS_VENDA tv ON tv.ID  = p.TIPO_VENDA_ID
                    INNER JOIN (
                        SELECT
                            p3.id
                            ,p4.status_base
                            ,(CASE WHEN (status_base = 'Aguardando' AND venda_autorizou IS NULL ) THEN 'Venda'
                                WHEN (status_base = 'Aguardando' AND nvl(t2.slug, '_') != 'mae' AND tipo_frete = 'c' AND logistica_autorizou IS NULL) THEN 'Logística'
                                WHEN (status_base = 'Aguardando' AND (nvl(t2.slug, '_') = 'mae' OR (tipo_frete = 'c' AND logistica_autorizou = '1') OR tipo_frete = 'f') AND diretoria_autorizou IS NULL) THEN 'Gerente Comercial'
                                WHEN (status_base = 'Aguardando' AND (nvl(t2.slug, '_') = 'mae' OR (tipo_frete = 'c' AND logistica_autorizou = '1') OR tipo_frete = 'f') AND diretoria_autorizou = '1' AND comercial_autorizou IS NULL) THEN 'Adm. Comercial'
                            ELSE NULL END
                            ) AS autorizacao_setor_aguardando
                            ,(CASE WHEN (status_base = 'Reprovado' AND nvl(t2.slug, '_') != 'mae' AND tipo_frete = 'c' AND logistica_autorizou = '0') THEN 'Logística'
                                WHEN (status_base = 'Reprovado' AND diretoria_autorizou = '0') THEN 'Gerente Comercial'
                                WHEN (status_base = 'Reprovado' AND comercial_autorizou = '0') THEN 'Adm. Comercial'
                            ELSE NULL END
                            ) AS autorizacao_setor_reprovou,
                            (CASE WHEN (aprovou = '1') THEN ' (Edit.)' ELSE NULL END) AS autorizacao_edicao
                        FROM web.pedidos_v2 p3 
                        INNER JOIN web.view_status_pedidos_v2 p4 ON p3.id = p4.id
                        LEFT JOIN web.tipos_desmembramento t2 ON p3.tipo_desmembramento_id = t2.id
                    ) ps ON ps.id = p.id
                    LEFT JOIN WEB.TIPOS_DESMEMBRAMENTO TD ON TD.ID = P.TIPO_DESMEMBRAMENTO_ID
                    WHERE (
                        (vp.tipo_parcela = 'G' 
                        AND nvl(ip.preco_total_germoplasma, 0) > 0) OR (vp.tipo_parcela = 'R' 
                        AND nvl(ip.preco_total_royalties, 0) > 0) OR (vp.tipo_parcela = 'T' 
                        AND nvl(ip.preco_total_tsi, 0) > 0) OR (vp.tipo_parcela = 'F' 
                        AND nvl(p.preco_total_frete, 0) > 0)
                    )
                ) dadosPedido
                INNER JOIN web.view_vencimentos_por_data parc ON (dadosPedido.id = parc.pedido_id AND dadosPedido.vencimento_parcela = parc.vencimento_parcela AND dadosPedido.parcela_codigomodalidade = parc.parcela_codigomodalidade)
                where 1 = 1
                {$wheres}
                GROUP BY dadosPedido.ID
                    ,dadosPedido.CODIGO
                    ,dadosPedido.CODIGOSAFRA
                    ,dadosPedido.MAE_PEDIDO_ID
                    ,dadosPedido.STATUS
                    ,dadosPedido.TIPO_FRETE
                    ,dadosPedido.CLIENTE_ID	
                    ,dadosPedido.NOME_CLIENTE
                    ,dadosPedido.VENDEDOR_ID
                    ,dadosPedido.NOME_VENDEDOR
                    ,dadosPedido.TIPO_VENDA
                    ,dadosPedido.AGENTE_ID
                    ,dadosPedido.NOME_AGENTE
                    ,dadosPedido.GRUPO_COMPRAS_ID
                    ,dadosPedido.NOME_GRUPO_COMPRA
                    ,dadosPedido.tipo_parcela 
                    ,dadosPedido.VENCIMENTO_PARCELA
                    ,dadospedido.parcela_codigomodalidade
                    ,dadospedido.recebido_em
                    ,dadosPedido.ID_RECEBIMENTO";  
    }
    public function getDadosControlRecebimentoDataPagamentoQuery($codigoSafra, $pagamento_inicio, $pagamento_fim)
    {
        $wheres = "";
        
        if (!empty($codigoSafra)) {
            $wheres .= " AND cr.codigosafra = {$codigoSafra}";
        }
        if (!empty($pagamento_inicio)) {
            $pagamento_fim = !empty($pagamento_fim) ? $pagamento_fim : date('Y-m-d');
            $wheres .= " AND cr.data_pagamento BETWEEN TO_DATE('{$pagamento_inicio}', 'YYYY-MM-DD') AND TO_DATE('{$pagamento_fim}', 'YYYY-MM-DD')";
        }

        return "SELECT * 
                FROM controle_recebimento cr
                where 1 = 1
                {$wheres}"; 
    }
    public function getLookupSafraQuery()
    {
        return "SELECT 
                    S.CODIGOSAFRA AS \"codigosafra\", 
                    UTL_RAW.CAST_TO_RAW(CAST(S.CODIGOSAFRA AS VARCHAR(255)) || ' - ' || S.ANO || ' - ' || REGEXP_REPLACE(C.DESCRICAO, '\s+', ' ') || ' - ' || REGEXP_REPLACE(CLIFOR.NOME, '\s+', ' '))  AS \"dsc\"
                FROM ALMOX.SAFRAS S
                INNER JOIN ALMOX.CULTURAS C ON C.CODIGOCULTURA = S.CODIGOCULTURA
                INNER JOIN EMPRESA.CLIFOR CLIFOR ON CLIFOR.CODIGOCLIFOR  = S.CODIGOCLIFOR 
                ORDER BY CODIGOSAFRA"; 
    }
}
