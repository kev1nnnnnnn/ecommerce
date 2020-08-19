<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

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


//Rota do FORGOT *****************
$app->get("/admin/forgot", function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");	

});

$app->post("/admin/forgot", function(){

	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;

});

$app->get("/admin/forgot/sent", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");	

});

$app->get("/admin/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setFogotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = User::getPasswordHash($_POST["password"]);

	$user->setPassword($password);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});


?>