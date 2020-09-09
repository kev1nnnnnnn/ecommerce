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
			'error'=>User::getError()
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

?>