<?php

use \Hcode\Page;
use \Hcode\Model\Product;

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



?>