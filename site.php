<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


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

	//finalizar Compra GET
	$app->get("/checkout", function(){

		User::verifyLogin(false);
		
		$address = new Address();

		$cart = Cart::getFromSession();

		if (isset($_GET['zipcode'])) {

			$_GET['zipcode'] = $cart->getdeszipcode();
		}

		//detectar se o cep foi enviado
		if (isset($_GET['zipcode'])) {
			
			//objeto carregado com o endereço *******
			$address->loadFromCEP($_GET['zipcode']);

			$cart->setdeszipcode($_GET['zipcode']);

			//salva pra ir pro banco
			$cart->save();

			$cart->getCalculateTotal();
		}

		if (!$address->getdesaddress()) $address->setdesaddress('');
		if (!$address->getdescomplement()) $address->setdescomplement('');
		if (!$address->getdesdistrict()) $address->setdesdistrict('');
		if (!$address->getdescity()) $address->setdescity('');
		if (!$address->getdesstate()) $address->setdesstate('');
		if (!$address->getdescountry()) $address->setdescountry('');
		if (!$address->getdeszipcode()) $address->setdeszipcode('');

		//irá para o template
		$page = new Page();

		$page->setTpl("checkout", [
			'cart'=>$cart->getValues(),
			'address'=>$address->getValues(),
			'products'=>$cart->getProducts(),
			'error'=>Address::getMsgError()
		]);
	});

	//POST
	$app->post("/checkout", function() {

		User::verifyLogin(false);

		if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
			Address::setMsgError("Informe o CEP.");

			header("Location: /checkout");
			exit;
		}

		if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
			Address::setMsgError("Informe o Endereço.");

			header("Location: /checkout");
			exit;
		}

		if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
			Address::setMsgError("Informe o Bairro.");

			header("Location: /checkout");
			exit;
		}

		if (!isset($_POST['descity']) || $_POST['descity'] === '') {
			Address::setMsgError("Informe o Cidade.");

			header("Location: /checkout");
			exit;
		}

		if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
			Address::setMsgError("Informe o UF.");

			header("Location: /checkout");
			exit;
		}

		if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
			Address::setMsgError("Informe o seu País.");

			header("Location: /checkout");
			exit;
		}

		$user = User::getFromSession();

		$address = new Address();

		$_POST['deszipcode'] = $_POST['zipcode'];
		$_POST['idperson'] = $user->getidperson();

		$address->setData($_POST);

		$address->save();

		$cart = Cart::getFromSession();

		$cart->getCalculateTotal();

		$order = new Order();

		$order->setData([
	        "idcart"=>$cart->getidcart(),
	        "idaddress"=>$address->getidaddress(),
	        "iduser"=>$user->getiduser(),
	        "idstatus"=>OrderStatus::EM_ABERTO,
	        "vltotal"=>$cart->vltotal()
    	]);

	//salva o pedido
	$order->save();

	//o pedido ganhará um ID
	header("Location: /order/".$order->getidorder());
	exit;

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

	//ROTA PARA ACESSAR A CONTA
	//GET
	$app->get("/profile", function() {

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl("profile", [
			'user'=>$user->getValues(),
			'profileMsg'=>User::getSuccess(),
			'profileError'=>User::getError()
		]);

	});

	//POST
	$app->post('/profile', function(){

		User::verifyLogin(false);

		//validando erros
		if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		
		User::setError("Preencha o seu nome.");
		
		header('Location: /profile');
		exit;
		}

		if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
			
			User::setError("Preencha o seu e-mail.");
			
			header('Location: /profile');
			exit;
		}

		$user = User::getFromSession();

		//verificação se ja tem outro usuario utlizando o mesmo login
		if ($_POST['desemail'] !== $user->getdesemail()) {

			if (User::checkLoginExists($_POST['desemail']) === true) {

				
				User::setError("Este endereço de e-mail já está cadastrado.");
				
				header('Location: /profile');
				exit;

			}
		}
		

		$_POST['inadmin'] = $user->getinadmin();
		$_POST['despassword'] = $user->getdespassword();
		$_POST['deslogin'] = $_POST['desemail'];

		$user->setData($_POST);

		//para que os dados do usuário sejam atualizados e não crie um usuário novo
		$user->update();

		User::setSuccess("Dados alterados com sucesso!");

		header('Location: /profile');
		exit;
	});

	//pra carregar o pedido que ira ser mostrado precisará receber o :idorder
	$app->get("/order/:idorder", function($idorder) {

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		$page = new Page();

		//chamando o template
		$page->setTpl("payment", [
			'order'=>$order->getValues()
		]);
	});

	$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	
	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");

});

	$app->get("/profile/orders", function() {

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl("profile-orders", [

			'orders'=>$user->getOrders()
		]);
	});

	$app->get("/profile/orders/:idorder", function($idorder) {

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		$cart = new Cart();

		$cart->get((int)$order->getidcart());

		$cart->getCalculateTotal();

		$page = new Page();

		$page->setTpl("profile-orders-details", [
			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()
		]);

	});

	$app->get("/profile/change-password",function() {

		User::verifyLogin(false);

		$page = new Page();

		$page->setTpl("profile-change-password", [
			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()
		]);
	});

	$app->post("/profile/change-password", function() {

		User::verifyLogin(false);
	//verifica se digitou a senha
		if(!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
			
			User::setError("Digite a senha atual");

			header("Location: /profile/change-password");
			exit;
		}

		if(!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
			
			User::setError("Digite a nova senha");

			header("Location: /profile/change-password");
			exit;
		}

		if(!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
			
			User::setError("Confirme a nova senha");

			header("Location: /profile/change-password");
			exit;
		}

		//verifica se a senha é diferente da nova
		if($_POST['current_pass'] === $_POST['new_pass']) {
		
			User::setError("A sua nova senha deve ser diferente");
			
			header("Location: /profile/change-password");
			exit;
		}

		$user = User::getFromSession();

		if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
			
			User::setError("A sua senha está inválida");
			
			header("Location: /profile/change-password");
			exit;
		}

		$user->setdespassword($_POST['new_pass']);

		$user->update();

		User::setSuccess("Senha alterada com sucesso");

		header("Location: /profile/change-password");
		exit;
	});
	/* BEGIN 
    INSERT INTO tb_usuarios(deslogin, dssenha) VALUES(pdeslogin, pdessenha);
    SET @vidusuario = (SELECT idusuario FROM tb_usuarios ORDER BY idusuario DESC LIMIT 1);
    SELECT * FROM tb_usuarios WHERE idusuario = @vidusuario;
	END*/

?>