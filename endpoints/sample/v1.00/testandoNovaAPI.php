<?php

// Engine
include "../../../core/engine-v1.class.php";

// Instâncias de base de dados
$DataBase1 = new APIDataBase(DB_CLOUD);

// Instância da classe principal
$ws = new APIConnect();

// Início do tratamento do endpoint
$ws->prepareQuery("SELECT * FROM pedidos WHERE tipo_pedido=2 LIMIT 5", $DataBase1, true);
$ws->execQuery();

if($ws->isQueryWorked)
{
    $arrayItens = array();
    $countItens = 0;

    while($row = $ws->getRowsAssoc())
    {
        $arrayItens[$countItens] = utf8_encode($row['nome_vendedor_pedido']);
        $countItens++;
    }

    $ws->prepareQuery("SELECT * FROM categorias_produtos WHERE ativo_categoria_produto=1 LIMIT 1", $DataBase1, true);
    
    $IDUsuario = (int)$ws->execSingleQuery()['id_categoria_produto'];
    
    if($ws->isQueryWorked)
    {
        $ws->setDataReturn(100, "Dados retornados com sucesso!", array(
            "primeiro_dados" => $arrayItens,
            "segundo_dados" => $IDUsuario
        ));
    }
}
else
{
    $ws->setDataReturn(401, "Erro ao retornar os dados");
}


// Retornando dados
$ws->Response();
// Fechando conexões
$DataBase1->Close();

?>