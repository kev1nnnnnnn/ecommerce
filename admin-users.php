<?php
	
	use \Hcode\PageAdmin;
	use \Hcode\Model\User;

	$app->get("/admin/users", function() {
	
		User::verifyLogin();

		$search = (isset($_GET['search'])) ? $_GET['search'] : "";
		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

		if($search != "") {

			$pagination = User::getPageSearch($search,$page, 1); 

		} else {

			$pagination = User::getPage($page, 1); 
		}

		

		$pages = [];

		for ($x=0; $x < $pagination['pages']; $x++) { 
			array_push($pages, [
				'href'=>'/admin/users?'.http_build_query([
					'page'=>$x+1,
					'search'=>$search
				]),
				'text'=>$x+1
			]);
		}

		$users = User::listAll(); //método estático

		$page = new PageAdmin();

		$page->setTpl("users", array (
			"users"=>$pagination['data'],
			"search"=>$search,
			"pages"=>$pages
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

		$_POST['despassword'] = User::getPasswordHash($_POST['despassword']);

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