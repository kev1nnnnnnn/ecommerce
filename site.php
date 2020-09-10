<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;


	//quando chamarem via get, uma chamada padrao a pasta raiz, ou seja o site sem nenhuma rota, executa essa função
	$app->get('/', function() {

		//passa os produtos do banco
		$products = Product::listAll();
	    
	    //chamando o construct e carregando o header
	    $page = new Page();
	    
	    //Vai adicionar o arquivo index
	    $page->setTpl("index", [
	    	'products'=>Product::checkList($products)
	    ]);
	});

	$app->get("/categories/:idcategory", function($idcategory) {
		
		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

		$category = new Category();

		$category->get((int)$idcategory);

		$pagination = $category->getProductsPage($page);

		$pages = [];

		for ($i=1; $i <= $pagination['pages']; $i++) { 
			array_push($pages, [
				'link'=>'/categories/' .$category->getidcategory(). '?page=' . $i,
				'page'=>$i
			]);
		}

		$page = new Page();

		$page->setTpl("category", [
			'category'=>$category->getValues(),
			'products'=>$pagination["data"],
			'pages'=>$pages
		]);

	});

	$app->get("/products/:desurl", function($desurl) {

		$product = new Product();

		$product->getFromURL($desurl);

		$page = new Page();

		$page->setTpl("product-detail", [
			'product'=>$product->getValues(),
			'categories'=>$product->getCategories()
		]);
	});

	$app->get("/cart", function() {

		$cart = Cart::getFromSession();

		$page = new Page();

		$page->setTpl("cart", [
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts(),
			 //passa a variavel cart com as info do carrinho
			'error'=>Cart::getMsgError()
		]);
	});

	//rota para adicionar o item no carrinho
	$app->get("/cart/:idproduct/add", function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		//recupera o carrinho pega da sessao ou criar um novo
		$cart = Cart::getFromSession();

		$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

		for ($i= 0; $i <$qtd; $i++) { 
			//recebe a instancia da classe product, isso adiciona o produto no carrinho
			$cart->addProduct($product);
		}
		
		header("Location: /cart");
		exit;

	});

	//rota para remover apenas um item do carrinho
	$app->get("/cart/:idproduct/minus", function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		//recupera o carrinho pega da sessao ou criar um novo
		$cart = Cart::getFromSession();

		//recebe a instancia da classe product, isso adiciona o produto no carrinho
		$cart->removeProduct($product);

		header("Location: /cart");
		exit;

	});

	//rota para remover o produto do carrinho
	$app->get("/cart/:idproduct/remove", function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		//recupera o carrinho pega da sessao ou criar um novo
		$cart = Cart::getFromSession();

		//recebe a instancia da classe product, isso adiciona o produto no carrinho, passa true para remover todos
		$cart->removeProduct($product, true);

		header("Location: /cart");
		exit;

	});

	//configurar a rota que vai receber a chamada do envio do formulario com o cep para calcular
	$app->post("/cart/freight", function(){

		//pega o carrinho que esta na sessao
		$cart = Cart::getFromSession();

		//metodo para passar o cep
		$cart->setFreight($_POST['zipcode']);

		header("Location: /cart");
		exit;
	});

	//finalizar Compra
	$app->get("/checkout", function(){

		User::verifyLogin(false);

		$cart = Cart::getFromSession();

		$address = new Address();

		$page = new Page();

		$page->setTpl("checkout", [
			'cart'=>$cart->getValues(),
			'address'=>$address->getValues()
		]);
	});

	$app->get("/login", function(){

		$page = new Page();

		$page->setTpl("login", [
			'error'=>User::getError(),
			'errorRegister'=>User::getErrorRegister(),
			'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
		]);
	});

	$app->post("/login", function(){

		try {

			User::login($_POST['login'], $_POST['password']);

		} catch(Exception $e) {

			User::setError($e->getMessage());
		}

		

		header("Location: /checkout");
		exit;
	});

	$app->Get("/logout", function() {

		User::logout();

		header("Location: /login");
		exit;
	});

	//Rotas para preenchimento de campo obrigatorio
	$app->post("/register", function () {

		$_SESSION['registerValues'] = $_POST;

		if (!isset($_POST['name']) || $_POST['name'] == '') {

			User::setErrorRegister("Preencha o seu nome.");
			header("Location: /login");
			exit;
		}

		if (!isset($_POST['email']) || $_POST['email'] == '') {

			User::setErrorRegister("Preencha o seu Email.");
			header("Location: /login");
			exit;
		}

		if (!isset($_POST['password']) || $_POST['password'] == '') {

			User::setErrorRegister("Preencha a sua senha.");
			header("Location: /login");
			exit;
		}

		//impedem que dois login utilizem o mesmo email 
		if(User::checkLoginExist($_POST['email']) === true) {
			User::setErrorRegister("Este endereço de email ja está sendo usado por outro usuário.");
			header("Location: /login");
			exit;
		}

		$user = new User();

		$user->setData([
			'inadmin'=>0,
			'deslogin'=>$_POST['email'],
			'desperson'=>$_POST['name'],
			'desemail'=>$_POST['email'],
			'despassword'=>$_POST['password'],
			'nrphone'=>$_POST['phone']
		]);

		$user->save();

		User::login($_POST['email'], $_POST['password']);

		header('Location: /checkout');
		exit;
	});
//	****************ESQUECEU SUA SENHA ****************
	//GET
	$app->get("/forgot", function() {

		$page = new Page();

		$page->setTpl("forgot");
	});

	//POST
	$app->post("/forgot", function() {

		$user = User::getForgot($_POST['email'], false); //false pra entender que nao esta na adm

		header("Location: /forgot/sent");
		exit;
	});

	$app->get("/forgot/sent", function() {

		$page = new Page();

		$page->setTpl("forgot-sent");
	});

	$app->get("/forgot/reset", function() {

		$user = Uer::validForgotDecrypt($_GET['code']);

		$page = new Page();

		$page->setTpl("forgot-reset", array(
			"name"=>$user['desperson'],
			"code"=>$_GET['code']

		));
	});

	//POST
	$app->post("/forgot/reset", function() {

		$forgot = User::validForgotDecrypt($_POST['code']);

		User::setForgotUsed($forgot["idrecovery"]);

		$user = new User();

		$user->get((int)$forgot["iduser"]);

		$password = User::getPasswordHash($_POST["password"]);

		$user->setPassword($password);

		$page = new Page();

		$page->setTpl("forgot-reset-success");
	});

	//FIM DO ESQUECEU SUA SENHA

?>