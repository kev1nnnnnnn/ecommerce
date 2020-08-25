<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\User;

	//quando chamarem via get, uma chamada padrao a pasta raiz, ou seja o site sem nenhuma rota, executa essa função;
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
			'categories'=>[]
		]);
	});


?>