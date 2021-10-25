<?php

include "../../../core/engine-v1.class.php";

$DBPrimario = new APIDataBase(DB_PRIMARIO);
$DBLojaVirtual = new APIDataBase(DB_LOJAVIRTUAL);

$ws = new APIAuthToken();

$arrayUsuarios = array();
$countUsuarios = 0;

$ws->setDataReturn(401, "Erro ao retornar os dados");

$ws->prepareQuery("pTesteProcedure", $DBPrimario);
    $ws->appendNumericQuery($ws->grupoCliente);
    $ws->appendNumericQuery($ws->getRequest("usuario"));
$ws->execQuery();
if($ws->isQueryWorked)
{
    while($row = $ws->getRowsAssoc())
    {
        $arrayUsuarios[$countUsuarios] = array(
            "nome" => utf8_encode($row['nome']),
            "cpf" => $row['cpf'],
        );

        $countUsuarios++;
    }

    $ws->setDataReturn(100, "Dados do usuário retornados com sucesso!", array(
        "usuarios" => $arrayUsuarios
    ));
}

$ws->Response();

$DBPrimario->Close();
$DBLojaVirtual->Close();

?>