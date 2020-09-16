<?php
	
	use \Hcode\PageAdmin;
	use \Hcode\Model\User;

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

		$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
		"cost"=>12
		]);

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



?>