<?php

// ----------------------------------------------------------------------------------------------------
// DATABASES:

// Banco primário principal
define("DB_PRIMARIO","rds-homolog-dev.cr3cyrrb6kt8.us-east-1.rds.amazonaws.com|dev-hmg|Pe##knn876Yha|database_hmg|3306|mysql");
define("DB_LOJAVIRTUAL","rds-homolog-dev.cr3cyrrb6kt8.us-east-1.rds.amazonaws.com|dev-hmg2|BugC0d12#21$09jh1|database2_hmg|3306|mysql");
define("DB_BASE3","rds-homolog-dev.cr3cyrrb6kt8.us-east-1.rds.amazonaws.com|dev-hmg3|20145>>PesElkk1ck|database3_hmg|3306|mysql");
define("DB_BASE4","rds-homolog-dev.cr3cyrrb6kt8.us-east-1.rds.amazonaws.com|dev-hmg4|G0Wa!@qazbnmRrCCTY|database4_hmg|3306|mysql");


// ----------------------------------------------------------------------------------------------------
// CONFIGS:

// Url para envio de logs em um servidor estático
define("URL_LOG", "https://estaticos.nweb.com.br/logdb/genlog.php");

// Chave da API de notificações Push
define("FIREBASE_API_ANDROID", "");
define("FIREBASE_API_IOS", "");


// ----------------------------------------------------------------------------------------------------
// VARIÁVEIS:

// Conexões por consultas no banco
define("MAX_QUERY_CONNECTION", 1);
// O retorno em array feito pela função setDataReturn ficará dentro do bloco "return"
define("USE_BLOCK_RETURN", 1);

// Servidor de binários estáticos
define("URL_FBIN_1", "#");

?>