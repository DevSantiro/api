<?php

  include "../../../core/engine-v1.class.php";
  
  $DBPrimario = new APIDataBase(DB_PRIMARIO);

  $ws = new APIAuthToken(rGET | rPOST | rPUT | rPATCH | rDELETE);

  $ws->setDataReturn(401, "Erro ao retornar os dados");

  switch ($ws->getMethodRequest()) 
  {
    case rGET: 
      $arrayUsuarios = array();
      $countUsuarios = 0;

      if (isset($_GET['id']))
        $where = "WHERE id = ".$_GET['id'];
      else
        $where = '';

      $ws->prepareQuery("SELECT * FROM usuarios_rodrigo ".$where.";", $DBPrimario, true);
      $ws->execQuery();

      if($ws->isQueryWorked)
      {      
        while($row = $ws->getRowsAssoc())
        {
            $arrayUsuarios[$countUsuarios] = array(
                "id"    => $row["id"],
                "nome"  => utf8_encode($row['nome']),
                "login" => $row['login'],
            ); 
    
            $countUsuarios++;
        }
    
        $ws->setDataReturn(100, "Dados do usuário retornados com sucesso!", array(
            "usuarios" => $arrayUsuarios
        ));
      }
    break;

    case rPOST:
      $ws->prepareQuery("INSERT INTO usuarios_rodrigo (nome, login, senha) VALUES('".$ws->getRequest("nome")."', '".$ws->getRequest("login")."', '".$ws->getRequest("senha")."');", $DBPrimario, true);
      $ws->execQuery();
  
      if($ws->isQueryWorked)
        $ws->setDataReturn(200, "Usuario inserido com sucesso!");
      else
        $ws->setDataReturn(402, "Erro ao inserir usuário");      
      break;

    case rPUT:
      $ws->prepareQuery("UPDATE usuarios_rodrigo SET nome= '".$ws->getRequest("nome")."', login = '".$ws->getRequest("login")."', senha = '".$ws->getRequest("senha")."' WHERE id = ".$_GET['id'].";", $DBPrimario, true);
      $ws->execQuery();
  
      if($ws->isQueryWorked)
        $ws->setDataReturn(200, "Usuario atualizado com sucesso!");
      else
        $ws->setDataReturn(402, "Erro ao atualizar usuario");
      break;

    case rDELETE:
      $ws->prepareQuery("DELETE FROM usuarios_rodrigo WHERE id = ".$_GET['id'].";", $DBPrimario, true);
      $ws->execQuery();
  
      if($ws->isQueryWorked)
        $ws->setDataReturn(300, "Usuario excluido com sucesso!");
      else
        $ws->setDataReturn(403, "Erro ao excluir usuario");
      break;

    case rPATCH:
      // $query  = "UPDATE usuarios_rodrigo SET ";
      // $campos = "";

      // if ($ws->getRequest("nome"))
      // {
      //   $query += $query . "nome = ".$ws->getRequest("nome");
      // }
    
      // if ($ws->getRequest("login"))
      // {
      //   $query += $query . ", login = ".$ws->getRequest("login");
      // }
 
      // if ($ws->getRequest("senha"))
      // {
      //   $query += $query . ", senha = ".$ws->getRequest("senha");
      // }

      $ws->setDataReturn(400, "Função de atualização campos individuais não implementada!");
      break;
  }
 
  // Retornando dados
  $ws->Response();
  // Fechando conexões
  $DBPrimario->Close();
  
?>