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

//Rota para o LOGIN ************************
$app->post('/admin/login', function() {

	User::login($_POST["login"], $_POST["password"]);

	header("location: /admin");
	exit;

});

//Rota para o LOGOUT ************************
$app->get("/admin/logout", function() {

	User::logout();

	header("Location: /admin/login");
	exit;
});


$app->get("/admin/users", function() {

	User::verifyLogin();

	$users = User::listAll(); //método estático

	$page = new PageAdmin();

	$page->setTpl("users", array (
		"users"=>$users
	));

});

//Rotas de tela *************************

$app->get("/admin/users/create", function() {

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl('users-create');

});

$app->get("/admin/users/:iduser/delete", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("location: /admin/users");

	exit;

});

//rota de edição *************************
$app->get('/admin/users/:iduser', function($iduser){
 
   User::verifyLogin();
 
   $user = new User();
 
   $user->get((int)$iduser);
 
   $page = new PageAdmin();
 
   $page ->setTpl("users-update", array(
        "user"=>$user->getValues()
    ));
 
});

//rota pra salvar *************************
$app->post("/admin/users/create", function() {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0; //se for definido o valor é 1, se nao for o valor é 0

	$user->setData($_POST);

	//executar o insert no banco
	$user->save();

	header("location: /admin/users");
	exit;

});

$app->post("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("location: /admin/users");
	exit;
});

//Rota do FORGOT
$app->get("/admin/forgot", function() {

	$page = new PageAdmin([

		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");

});

//pegar o email que o usuário envio via post
$app->post("/admin/forgot", function() {

	$user = User::getForgot($_POST["email"]);
});



$app->run();
