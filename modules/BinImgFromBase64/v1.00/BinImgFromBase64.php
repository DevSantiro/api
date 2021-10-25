<?php

include "../../../core/engine-v1.class.php";

$dbIntegra = new APIDataBase(DB_PRIMARIO);

$img = new APIImage();

$ws = new APIConnect("", rGET);


$ws->prepareQuery("SELECT mfoto FROM cadremprofoto WHERE ccodproduto='".$_GET['c']."'", $dbIntegra, true);
$dadosImg = $ws->execSingleQuery();
if($ws->isQueryWorked)
{
    if(strlen($dadosImg['mfoto']) > 10)
        $img->renderImageFromBase64($dadosImg['mfoto']);
    else
        $img->renderImageFromURL("no_image.jpg");
}

$dbIntegra->Close();



?>