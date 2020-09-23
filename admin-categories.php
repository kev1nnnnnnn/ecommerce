<?php
	
	use \Hcode\PageAdmin;
	use \Hcode\Model\User;
	use \Hcode\Model\Category;
	use \Hcode\Model\Product;
	
	$app->get("/admin/categories", function() {

		User::verifyLogin();

		$search = (isset($_GET['search'])) ? $_GET['search'] : "";
		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

		if($search != "") {

			$pagination = Category::getPageSearch($search,$page, 1); 

		} else {

			$pagination = Category::getPage($page, 1); 
		}

		$pages = [];

		for ($x=0; $x < $pagination['pages']; $x++) { 
			array_push($pages, [
				'href'=>'/admin/categories?'.http_build_query([
					'page'=>$x+1,
					'search'=>$search
				]),
				'text'=>$x+1
			]);
		}

		//$users = User::listAll(); //método estático

		$page = new PageAdmin();

		$page->setTpl("categories", array (
			"categories"=>$pagination['data'],
			"search"=>$search,
			"pages"=>$pages
		));
	});
	//end


	$app->get("/admin/categories/create", function(){

		User::verifyLogin();

		$page = new PageAdmin();

		$page->setTpl("categories-create");
	});
	//end


	$app->post("/admin/categories/create", function(){

		$category = new Category();

		$category->setData($_POST);

		$category->save();

		header("Location: /admin/categories");
		exit();
	});
	//end


	$app->get("/admin/categories/:idcategory/delete", function($idcategory) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$category->delete();

		header("Location: /admin/categories");
		exit;
	});
	//end

	$app->get("/admin/categories/:idcategory", function($idcategory){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$page->setTpl("categories-update", [
			'category'=>$category->getValues()
		]);
	});
	//end

	$app->post("/admin/categories/:idcategory", function($idcategory){
		
		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$category->setData($_POST);

		$category->save();

		header("Location: /admin/categories");
		exit;
	});
	//end

	//PRODUTOS VS CATEGORIAS

	$app->get("/admin/categories/:idcategory/products", function($idcategory) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$page->setTpl("categories-products", [
			'category'=>$category->getValues(),
			'productsRelated'=>$category->getProducts(),
			'productsNotRelated'=>$category->getProducts(false)
		]);

	});
	//end

	$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$product = new Product();

		$product->get((int)$idproduct);

		$category->addProduct($product);

		header("Location: /admin/categories/".$idcategory."/products");
		exit;

	});
	//end

	$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$product = new Product();

		$product->get((int)$idproduct);

		$category->removeProduct($product);

		header("Location: /admin/categories/".$idcategory."/products");
		exit;
	});
	//end


?>
