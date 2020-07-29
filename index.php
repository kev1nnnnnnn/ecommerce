<?php 

require_once("vendor/autoload.php");

use\Slim\slim;
use \Hcode\Page;

//facilitação das rotas
$app = new \Slim\Slim();

$app->config('debug', true);

//quando chamarem via get, uma chamada padrao a pasta raiz, ou seja o site sem nenhuma rota, executa essa função;
$app->get('/', function() {
    
    //chamando o construct e carregando o header
    $page = new Page();
    
    //Vai adicionar o arquivo index
    $page->setTpl("Index");

});

$app->run();

 ?>