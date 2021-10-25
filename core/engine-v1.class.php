<?php

// Módulos do composer
require_once __DIR__ . "/vendor/autoload.php";
// Constantes para conexão com banco de dados e outras configurações gerais
include __DIR__ . "/../defines.php";

// Métodos de requisição
define("rGET", 2);          // Para obter informações
define("rPOST", 4);         // Para incluir um novo dado
define("rPUT", 8);          // Para atualizar algum dado
define("rDELETE", 16);      // Para remover algum registro
define("rPATCH", 32);       // Atualiza algumas colunas de um registro

define("VERSION_LOG_FROM_ENGINE", "v1.00");

// Para instanciar banco de dados usados para a conexão durante a execução do endpoint
class APIDataBase
{
    // Para conexão com o banco de dados
    private $DataBaseConnection = null;
    // Variável responsável por dizer se está ou não conectado ao banco de dados
    private $isConnectedDB = false;
    // Contagem de querys que já foi consumida a conexão com o banco
    public $usedQuerys = 0;
    // Tipo de banco de dados
    public $DataBaseType = 0; // 0=MySQL, 1=Postgres
    // Log interno volta para o banco de dados
    public $logConnection = "";
    // Salvando a string do define para futurar reconexões
    private $dbStringConnect = "";


    // Contrutor
    public function __construct($__db_define)
    {
        // Concatenando o que construiu uma nova base de dados
        $this->logConnection .= " | CONSTRUTOR";
        // Atribuindo a String na classe para futuras reconexões
        $this->dbStringConnect = $__db_define;
    }

    // Função que conecta a base de dados independente do tipo de banco
    public function ConnectDB($__is_reconnect)
    {
        $this->logConnection .= " | Chamou o ConnectDB";
        
        /*
        // Caso for uma reconexão
        if($this->usedQuerys > 0)
        {
            // Fecha a conexão para abrir uma nova posteriormente a essa função
            $this->Close();
        }*/

        //! if($this->usedQuerys == -1) $this->usedQuerys = 0;

        $this->logConnection .= " | @usedQuerys:".$this->usedQuerys;
        
        // Fazendo um split para saber qual o tipo de banco conectar
        $DataDB = explode("|",$this->dbStringConnect);
        // Quebra a string do DEFINE para conexão com o banco de dados

        // Verificando qual base de dados conectar
        switch($DataDB[count($DataDB)-1])
        {
            // Banco de dados relacionais
            case "mysql": $tipoBancoDeDados = 0; break;         // MySQL
            case "postgres": $tipoBancoDeDados = 1; break;      // Postgres
            // Banco de dados não relacionais
            case "postgres": $tipoBancoDeDados = 2; break;      // MongoDB
        }

        $this->logConnection .= " | Tipo de banco: ".$DataDB[count($DataDB)-1];


        
        // Switch para verificação de qual banco conectar de acordo com sua systax
        switch($tipoBancoDeDados)
        {
            // MYSQL
            case 0:

                // Conectando ao banco, caso dê tudo certo...
                if($this->DataBaseConnection = mysqli_connect($DataDB[0],$DataDB[1],$DataDB[2],$DataDB[3]))
                {
                    // Atribui as variáveis internas da classe
                    $this->isConnectedDB = true;    // Seta banco conectado
                    $this->usedQuerys = 0;
                    $this->DataBaseType = $tipoBancoDeDados;

                    $this->logConnection .= " | Thread: " . mysqli_thread_id($this->DataBaseConnection);
                }
                else
                {
                    $this->logConnection .= " | Erro de conexão: " . mysqli_error($this->DataBaseConnection);
                }

            break;
            
            // POSTGRES
            case 1:

                $queryConnectPG = "host=".$DataDB[0]." port=".$DataDB[4]." dbname=".$DataDB[3]." user=".$DataDB[1]." password=".$DataDB[2];

                // Conectando ao banco, caso dê tudo certo...
                if($this->DataBaseConnection = pg_connect($queryConnectPG))
                {
                    // Atribui as variáveis internas da classe
                    $this->isConnectedDB = true;    // Seta banco conectado
                    $this->usedQuerys = 0;
                    $this->DataBaseType = $tipoBancoDeDados;

                    $this->logConnection .= " | Thread: " . pg_get_pid($this->DataBaseConnection);
                }
                else
                {
                    $this->logConnection .= " | Erro de conexão: " . pg_errormessage($this->DataBaseConnection);

                    
                }

            break;

            // MONGODB
            case 1:
                
                // Atribuindo o valor para usar o SSL
                $isUseSSL = "false";

                // Caso o define indica "use_ssl" atribui o valor a variável para usar o SSL
                if($DataDB[5] == "use_ssl") $isUseSSL = "true";

                // Montando a conexão com o MongoDB
                $conMongoDB  = "mongodb://";
                $conMongoDB .= $DataDB[1].":";
                $conMongoDB .= $DataDB[2]."@";
                $conMongoDB .= $DataDB[0].":";
                $conMongoDB .= $DataDB[4]."/?authSource=";
                $conMongoDB .= $DataDB[3];
                $conMongoDB .= "&readPreference=primary&directConnection=true&ssl=".$isUseSSL;

                // $con = (new MongoDB\Client("mongodb://afjsys01:eisten123@mongo71-farm1.kinghost.net:27017/?authSource=afjsys01&readPreference=primary&directConnection=true&ssl=false"));

            break;
        }
        
        // Destroe a variável que quebrou para usar a conexão com o banco de dados
        unset($DataDB);
    }

    public function Close()
    {
        $this->logConnection .= " | Closing... ";

        if($this->isConnectedDB && $this->usedQuerys > 0)
        {
            $this->logConnection .= "Close Connection";
            switch($this->DataBaseType)
            {
                case 0: mysqli_close($this->DataBaseConnection); break;
                case 1: pg_close($this->DataBaseConnection); break;
            }

            $this->usedQuerys = 0;
        }
    }

    public function EndQuery()
    {
        $this->Close();
    }

    public function UseQuery()
    {
        if($this->isConnectedDB)
        {
            $this->usedQuerys++;
            return $this->DataBaseConnection;
        }
        else return false;
    }

    public function getConnection()
    {
        return $this->DataBaseConnection;
    }

    public function getStateConnection()
    {
        return $this->isConnectedDB;
    }
}

class APIcURL
{
    public $ch;
    public $statusProtocol = 0;

    public function setHeader($key, $value)
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $key.": ".$value);
    }

    public function __construct($url, $body = null)
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL,$url);
        curl_setopt($this->ch, CURLOPT_POST,1);
        if($body!=null)curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);
    }

    public function send()
    {
        $result = curl_exec ($this->ch);
        $this->statusProtocol = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        curl_close ($this->ch);

        return $result;
    }
}


// Classe principal para o bom funcionamento da API
class APIConnect
{
    // Variável que indica que deu certo a query consultada com o banco de dados corrente
    public $isQueryWorked = false;

    // Variáveis privadas para o funcionamento interno da classe
    private $isUseLog = false;              // Armazena o valor para printar ou não o log no retorno da requisição
    private $logName = "";                  // Nome que estará como identificação no log
    private $prefixLogName = "";                  // Nome que estará como identificação no log
    private $methodRequest = "";            // Métodos de requisição: POST, PUT, DELETE...

    private $isErrorStart = 0;              // Se caso der algum erro antes de iniciar de fato o uso da API
    private $msgErrorStart = false;         // Mensagem do erro de inicialização
    
    private $sqlQuery = "";                 // Armazena a query para executá-la na função posterior, aonde será contatenado todas as strings
    private $countParamQuery = 0;           // Contagem de parâmetros da procedure da query
    private $resultQuery = null;            // Retorno do resultado obtido no mysqli_query
    
    private $dataReturn = array();          // Dados de retorno
    private $logRuntime = "";               // Quando o endpoint retornar alguns dados durante o desenvolvimento do endpoint
    private $protocolReturn = 500;          // Protocolo de retorno
    private $descricaoRetorn = "";          // Descrição de retorno do endpoint
    private $bearerToken = "";              // String de autenticação Bearer

    public $requestHeaders = null;         // Cabeçalho da requisição

    // Armazena o que foi enviado na requisição para usar no consumo do endpoint
    private $parametersPost = null;

    // Responsável por armazenar a conexão do banco corrente para executar a query posteriormente
    private $connectionDBCurrent = null;
    private $isSimpleQuery = false;
    
    // Construtor principal da classe
    public function __construct($__methods = rPOST, $__log_name = "", $__prefix_log = "")
    {
        if(!$this->validateMethodRequest($__methods))
        {
            $this->isErrorStart = 1;
            $this->msgErrorStart = "Método de requisição não suportado";
        }

        if($__log_name != "")
        {
            $this->logName = $__log_name;
            $this->prefixLogName = $__prefix_log;
        }
        
        $this->parametersPost = json_decode(file_get_contents('php://input'), true);

        $headers = null;

        if (isset($_SERVER['Authorization'])) $headers = trim($_SERVER["Authorization"]);
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) $headers = trim($_SERVER["HTTP_AUTHORIZATION"]); 
        else if (function_exists('apache_request_headers'))
        {
            $rHeaders = apache_request_headers();

            $this->requestHeaders = array();
            foreach ($rHeaders as $header => $value)
            {
                $this->requestHeaders[$header] = $value;
            }

            $rHeaders = array_combine(array_map('ucwords', array_keys($rHeaders)), array_values($rHeaders));
            if (isset($rHeaders['Authorization'])) $headers = trim($rHeaders['Authorization']);
        }
        
        $__headers = $headers;
        if (!empty($__headers)) if (preg_match('/Bearer\s(\S+)/', $__headers, $matches)) $this->bearerToken = $matches[1];
    }

    // Função que valida o método da requisição
    public function validateMethodRequest($__methods)
    {
        // Variável local que será enviado no retorno
        $isOkPassMethosRequest = false;

        if($__methods & rGET)
        {
            if($this->methodRequest != "") $this->methodRequest .= ", ";
            $this->methodRequest .= "GET";
            if($_SERVER['REQUEST_METHOD'] == "GET") $isOkPassMethosRequest = true;
        }

        if($__methods & rPOST)
        {
            if($this->methodRequest != "") $this->methodRequest .= ", ";
            $this->methodRequest .= "POST";
            if($_SERVER['REQUEST_METHOD'] == "POST") $isOkPassMethosRequest = true;
        }

        if($__methods & rPUT)
        {
            if($this->methodRequest != "") $this->methodRequest .= ", ";
            $this->methodRequest .= "PUT";
            if($_SERVER['REQUEST_METHOD'] == "PUT") $isOkPassMethosRequest = true;
        }

        if($__methods & rPATCH)
        {
            if($this->methodRequest != "") $this->methodRequest .= ", ";
            $this->methodRequest .= "PATCH";
            if($_SERVER['REQUEST_METHOD'] == "PATCH") $isOkPassMethosRequest = true;
        }

        if($__methods & rDELETE)
        {
            if($this->methodRequest != "") $this->methodRequest .= ", ";
            $this->methodRequest .= "DELETE";
            if($_SERVER['REQUEST_METHOD'] == "DELETE") $isOkPassMethosRequest = true;
        }

        return $isOkPassMethosRequest;
    }

    public function forceErrorStart($__msg_report = "Falha na inicialização")
    {
        if($this->isErrorStart > 0) return;

        $this->isErrorStart = 2;
        $this->msgErrorStart = $__msg_report;
    }

    public function getMethodRequest()
    {
        if($this->isErrorStart > 0) return;

        $flagMethod = NULL;

        switch($_SERVER['REQUEST_METHOD'])
        {
            case "GET": $flagMethod = rGET; break;
            case "POST": $flagMethod = rPOST; break;
            case "PUT": $flagMethod = rPUT; break;
            case "DELETE": $flagMethod = rDELETE; break;
            case "PATCH": $flagMethod = rPATCH; break;
            default: $flagMethod = rPOST; break;
        }

        return $flagMethod;
    }

    public function getRequest($key = null)
    {
        if($this->isErrorStart > 0) return;

        if($_SERVER['REQUEST_METHOD'] == "GET")
        {
            if($key != null)
            {
                return $_GET[$key];
            }
            else
            {
                $this->isErrorStart = 3;
                $this->msgErrorStart = "A requisição é do tipo 'GET', expectativa do comando getRequest seja diferente de nulo.";
            }
        }
        else
        {
            
            if($key != null)
            {
                return $this->parametersPost[$key];
            }
            else
            {
                return $this->parametersPost;
            }
        }
    }

    public function forceDebugLog() { $this->isUseLog = true; }

    public function getBearerToken() { return $this->bearerToken; }

    public function prepareQuery($__object, &$__db, $__is_simple_query = false)
    {
        if($this->isErrorStart > 0) return;

        $__db->ConnectDB(true);

        $this->debugLog($__db->logConnection);

        $this->connectionDBCurrent = $__db;

        if($this->connectionDBCurrent->DataBaseType == 0)
        {
            if(!$this->connectionDBCurrent->getStateConnection()) $this->logRuntime .= " | Banco de dados não conectado (".mysqli_error($this->connectionDBCurrent->getConnection()).")";
        }
        else if($this->connectionDBCurrent->DataBaseType == 1)
        {
            if(!$this->connectionDBCurrent->getStateConnection()) $this->logRuntime .= " | Banco de dados não conectado (".pg_errormessage($this->connectionDBCurrent->getConnection()).")";
        }
        
        
        $this->countParamQuery = 0;

        if(!$__is_simple_query) $this->sqlQuery = "CALL ".$__object."(";
        else $this->sqlQuery = $__object;

        $this->isSimpleQuery = $__is_simple_query;
    }

    public function appendStringQuery($__value)
    {
        if($this->isErrorStart > 0) return;

        if($this->countParamQuery > 0) { $this->sqlQuery .= ","; }
        $this->sqlQuery .= "'".utf8_decode(str_replace("'","\'",$__value))."'";
        $this->countParamQuery++;
    }

    public function appendNumericQuery($__value)
    {
        if($this->isErrorStart > 0) return;

        if($this->countParamQuery > 0) { $this->sqlQuery .= ","; }
        $this->sqlQuery .= $__value;
        $this->countParamQuery++;
    }

    public function execSingleQuery()
    {
        if($this->isErrorStart > 0) return;

        $errorQuery = "";
        $this->isQueryWorked = false;

        if(!$this->isSimpleQuery) $this->sqlQuery .= ");";

        if($this->connectionDBCurrent->DataBaseType == 0)
        {
            if($this->resultQuery = mysqli_query($this->connectionDBCurrent->UseQuery(),$this->sqlQuery)) $this->isQueryWorked = true;
            else
            {
                $this->isErrorStart = 4;
                $this->msgErrorStart = "Erro query";
                $errorQuery = mysqli_error($this->connectionDBCurrent->getConnection());
            }
    
            $this->logRuntime .= " | ".$this->sqlQuery;
    
            if($errorQuery != "") $this->logRuntime .= " | Erro Query: " . $errorQuery;

            $result = mysqli_fetch_assoc($this->resultQuery);

            $this->connectionDBCurrent->Close();
    
            return $result;
        }
        else if($this->connectionDBCurrent->DataBaseType == 1)
        {
            if($this->resultQuery = pg_query($this->connectionDBCurrent->UseQuery(),$this->sqlQuery)) $this->isQueryWorked = true;
            else
            {
                $this->isErrorStart = 4;
                $this->msgErrorStart = "Erro query";
                $errorQuery = pg_errormessage($this->connectionDBCurrent->getConnection());
            }
    
            $this->logRuntime .= " | ".$this->sqlQuery;
    
            if($errorQuery != "") $this->logRuntime .= " | Erro Query: " . $errorQuery;
    
            $result = pg_fetch_assoc($this->resultQuery);

            $this->connectionDBCurrent->Close();
    
            return $result;
        }
    }

    public function execQuery()
    {
        if($this->isErrorStart > 0) return;

        $errorQuery = "";
        $this->isQueryWorked = false;

        if(!$this->isSimpleQuery) $this->sqlQuery .= ");";

        if($this->connectionDBCurrent->DataBaseType == 0)
        {
            if($this->resultQuery = mysqli_query($this->connectionDBCurrent->UseQuery(),$this->sqlQuery)) $this->isQueryWorked = true;
            else
            {
                $this->isErrorStart = 4;
                $this->msgErrorStart = "Erro query";
                $errorQuery = mysqli_error($this->connectionDBCurrent->getConnection());
            }
    
            $this->logRuntime .= " | ".$this->sqlQuery;
    
            if($errorQuery != "") $this->logRuntime .= " | Erro Query: " . $errorQuery;
        }
        if($this->connectionDBCurrent->DataBaseType == 1)
        {
            if($this->resultQuery = pg_query($this->connectionDBCurrent->UseQuery(),$this->sqlQuery)) $this->isQueryWorked = true;
            else
            {
                $this->isErrorStart = 4;
                $this->msgErrorStart = "Erro query";
                $errorQuery = pg_errormessage($this->connectionDBCurrent->getConnection());
            }
    
            $this->logRuntime .= " | ".$this->sqlQuery;
    
            if($errorQuery != "") $this->logRuntime .= " | Erro Query: " . $errorQuery;
        }
    }

    public function getNumRows()
    {
        if($this->isErrorStart > 0) return;

        if($this->connectionDBCurrent->DataBaseType == 0)
        {
            return (int)mysqli_num_rows($this->resultQuery);
        }
        else if($this->connectionDBCurrent->DataBaseType == 1)
        {
            return (int)pg_num_rows($this->resultQuery);
        }
    }

    public function getRowsAssoc()
    {
        if($this->isErrorStart > 0) return;

        if($this->connectionDBCurrent->DataBaseType == 0)
        {
            return mysqli_fetch_assoc($this->resultQuery);
        }
        else if($this->connectionDBCurrent->DataBaseType == 1)
        {
            return pg_fetch_assoc($this->resultQuery);
        }
    }

    public function debugLog($__value) { $this->logRuntime .= " | ".$__value; }

    public function getLogRuntime() { return $this->logRuntime; }

    public function setDataReturn($__protocol, $__descricao, $__data = array())
    {
        if($this->isErrorStart > 0) return;

        $this->protocolReturn = $__protocol;
        $this->descricaoRetorn = $__descricao;
        $this->dataReturn = $__data;
    }

    public function Response($__protocol_http = 200)
    {
        if($this->isErrorStart > 0)
        {
            $this->protocolReturn = 999;
            $this->descricaoRetorn = $this->msgErrorStart." | ".$this->logRuntime;
        }

        //header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header("Access-Control-Allow-Methods: ".$this->methodRequest);
        //header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        header("Access-Control-Allow-Credentials: true");
        header('Content-type: application/json; charset=utf-8');

        $returnJson = $this->dataReturn;
        $cabecalhoRetorno = array("cod" => $this->protocolReturn, "logname" => $this->logName, "msg" => $this->descricaoRetorn);
        if(USE_BLOCK_RETURN == 0) $returnJson = array_merge($cabecalhoRetorno,$returnJson);
        else $returnJson = array_merge($cabecalhoRetorno,array("return" => $returnJson));
        if($this->isUseLog == true || isset($_GET['debug'])) $returnJson = array_merge(array("log_runtime" => $this->logRuntime, "methods" => $this->methodRequest),$returnJson);
        
        $jsonResponseEncode = json_encode($returnJson,JSON_UNESCAPED_UNICODE);

        if($this->logName != "" && URL_LOG != "#" && URL_LOG != "")
        {
            $urlArray = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', $urlArray);
            $numSegments = count($segments); 
            $currentSegment = $segments[$numSegments - 1];

            $contentLog = file_get_contents('php://input')."\n\n".$jsonResponseEncode;

            $postRequestLog  = "v=".VERSION_LOG_FROM_ENGINE;
            $postRequestLog .= "&m=".$this->logName;
            $postRequestLog .= "&p=".$this->prefixLogName;
            $postRequestLog .= "&e=".$currentSegment;
            $postRequestLog .= "&h=".$this->getClientIP();
            $postRequestLog .= "&c=".$contentLog;

            $chlog = curl_init();
            curl_setopt($chlog, CURLOPT_URL, URL_LOG);
            curl_setopt($chlog, CURLOPT_POST,1);
            curl_setopt($chlog, CURLOPT_POSTFIELDS, $postRequestLog);
            curl_exec($chlog);
            curl_close($chlog);
        }

        http_response_code($__protocol_http);
        echo $jsonResponseEncode;
    }

    public function getClientIP()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR')) $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED')) $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR')) $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))  $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR')) $ipaddress = getenv('REMOTE_ADDR');
        else $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function sendPushNotification($__token_dispositivo, $__titulo, $__mensagem, $__channel="basic_channel", $__large_icon = "", $__token_api = "")
    {
        if($this->isErrorStart > 0) return;

        $tokenAPI = FIREBASE_API_ANDROID;

        if($__token_api != "") $tokenAPI = $__token_api;

        $contentNotification = array();

        $contentNotification["id"] = 100;
        $contentNotification["channelKey"] = $__channel;
        $contentNotification["title"] = $__titulo;
        $contentNotification["body"] = $__mensagem;
        if($__large_icon != "") $contentNotification["largeIcon"] = URL_FBIN_1+$__large_icon;

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array(
            'Authorization: key='.$tokenAPI,
            'Content-Type: application/json'
            )
        );
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode(array(
            'to' => $__token_dispositivo,
            "content_available" => true,
            "data" => array(
                "content" => $contentNotification
            )
        ),JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        $retorno = curl_exec($ch);
        curl_close($ch);

        return $retorno;
    }
}


class APIImage
{
    public function __construct()
    {
        
    }

    public function renderImageFromURL($__url_image)
    {
        header("content-type: image/jpeg");
        $nova_imagem_pequena = imagecreatefromstring(file_get_contents($__url_image));
        imagejpeg($nova_imagem_pequena);
    }

    public function renderImageFromBase64($__data_img)
    {
        header("content-type: image/jpeg");
        $imgDataB64 = "data:image/jpeg;base64,".$__data_img;
        $imgDataB64 = substr($imgDataB64, 1+strrpos($imgDataB64, ','));

        $imageData = base64_decode($imgDataB64);
        $source = imagecreatefromstring($imageData);
        imagejpeg($source);
    }
}

class APIAuthToken extends APIConnect
{
    private $conAPI;

    // Variáveis que auxiliam nas requisições
    public $grupoCliente = 0;       // ID do grupo
    public $lojaCliente = 0;        // ID da loja através do CNPJ
    
    // Contrutor
    public function __construct($__methods = rPOST, $__log_name = "", $__prefix_log = "")
    {
        $this->grupoCliente = 1;
        $this->lojaCliente = 1;

        $this->conAPI = new APIConnect($__methods, $__log_name, $__prefix_log);
       
        if($this->validateMethodRequest($__methods))
        {
             /*
            $DBBearer = new APIDataBase(DB_PRIMARIO);
            $conBearerIdentifier = new APIConnect($__methods, $__log_name, $__prefix_log);

            $conBearerIdentifier->prepareQuery("pValidaTokenWS_v1_00", $DBBearer);
            $conBearerIdentifier->appendStringQuery($conBearerIdentifier->getBearerToken());
            $conBearerIdentifier->appendStringQuery($conBearerIdentifier->requestHeaders["cnpj"]);
            $data = $conBearerIdentifier->execSingleQuery();

            if($conBearerIdentifier->isQueryWorked)
            {
                if((int)$data['is_valid'] == 1)
                {
                    $this->grupoCliente = (int)$data['grupo_id'];

                    if(strlen($conBearerIdentifier->requestHeaders["cnpj"]) > 8)
                    {
                        if($data['loja_id'] != "" && $data['loja_id'] != null)
                        {
                            $this->lojaCliente = (int)$data['loja_id'];
                        }
                        {
                            $this->forceErrorStart("Erro de autenticação");
                        }
                    }
                    else
                    {
                        $this->lojaCliente = $conBearerIdentifier->requestHeaders["loja"];
                    }
                }
                else
                {
                    $this->forceErrorStart("Erro de autenticação");
                }
            }
            else
            {
                $this->forceErrorStart("Não foi possível conectar a base de dados >> " . $this->getLogRuntime());
            }

            $DBBearer->Close(); */
        }
        else
        {
            $this->forceErrorStart("Método de requisição não suportado");
        }
       
    }

    // Obtém o retorno vindo do body do request
    public function getRequest($key = null){ return $this->conAPI->getRequest($key); }
}

?>