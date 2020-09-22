<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

$app->get("/admin/orders/:idorder/status", function($idorder) {

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$page = new PageAdmin();

	$page->setTpl("order", [
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError()
	]);
});

$app->post("/admin/orders/:idorder/status", function($idorder) {

	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		Order::setError("Informe o status atual");
		header("Location: /admin/orders/".$idorder."status");
		exit;
	}

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->setidstatus((int)$_POST['idstatus']);

	$order->save();

	Order::setSuccess("Status Atualizado.");

	header("Location: /admin/orders/".$idorder."status");
	exit;


});

$app->get("/admin/orders", function() {

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("orders", [
		"orders"=>Order::ListAll()
	]);

});

$app->get("/admin/orders/:idorder/delete", function($idorder) {

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->delete();

	header("Location /admin/orders");
	exit;

});

$app->get("/admin/orders/:idorder", function($idorder) {

	$cart = (isset($_GET['cart'])) ? $_GET['cart'] : "";

	User:: verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	//método pra pegar o carrinho do pedido
	$cart = $order->getCart();

	$page = new PageAdmin();

	$page->setTpl("order", [
	'order'=>$order->getValues(),
	'cart'=>$cart->getValues(),
	'products'=>$cart->getProducs()
	]);
});

$app->get("/admin/orders", function() {

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("orders", [
		"orders"=>Order::listAll()
	]);
});


?>