<?php 
session_start();
require_once("vendor/autoload.php");

use\Slim\slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

//facilitação das rotas
$app = new Slim();

$app->config('debug', true);

//quando chamarem via get, uma chamada padrao a pasta raiz, ou seja o site sem nenhuma rota, executa essa função;
$app->get('/', function() {
    
    //chamando o construct e carregando o header
    $page = new Page();
    
    //Vai adicionar o arquivo index
    $page->setTpl("Index");
});

$app->get('/admin', function() {

	User::verifyLogin();

    //chamando o construct e carregando o header
    $page = new PageAdmin();
    
    //Vai adicionar o arquivo index
    $page->setTpl("Index");
});

$app->get('/admin/login', function() {

	$page =  new PageAdmin([
		//desabilitar a chamada automatica
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");

});

//validando o LOGIN ************************
$app->post('/admin/login', function() {

	User::login($_POST["login"], $_POST["password"]);

	header("location: /admin");
	exit;

});

$app->get('/admin/logout', function() {
	User::logout();
	header("Location: /admin/login");
	exit;
});

$app->run();

 ?>